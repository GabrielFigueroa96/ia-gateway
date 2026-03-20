<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * GET — Meta verifica el webhook al registrarlo.
     * Cada negocio tiene su propio webhook_token en la tabla tenants.
     */
    public function verify(Request $request)
    {
        $token     = $request->query('hub_verify_token');
        $mode      = $request->query('hub_mode');
        $challenge = $request->query('hub_challenge');

        if ($mode !== 'subscribe' || !$token) {
            return response()->json(['error' => 'Invalid request'], 403);
        }

        $tenant = Tenant::where('webhook_token', $token)->where('activo', true)->first();

        if (!$tenant) {
            return response()->json(['error' => 'Unknown token'], 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * POST — Llega un mensaje de WhatsApp, Messenger o Instagram.
     * Detecta el canal por el campo "object", identifica el tenant y reenvía al API correspondiente.
     */
    public function handle(Request $request)
    {
        try {
            $body   = $request->json()->all();
            $object = $body['object'] ?? null;

            // ── Messenger / Instagram ────────────────────────────────────────
            if ($object === 'page' || $object === 'instagram') {
                return $this->handleMessenger($body, $object);
            }

            // ── WhatsApp Business ────────────────────────────────────────────
            return $this->handleWhatsapp($body);

        } catch (\Throwable $e) {
            Log::error("Gateway error: {$e->getMessage()}", ['exception' => $e]);
        }

        return response()->json(['status' => 'ok']);
    }

    // ── Log de mensajes salientes (registrado por el tenant) ─────────────────

    /**
     * POST /api/log-outgoing — El tenant registra un mensaje saliente con su wamid y texto.
     */
    public function logOutgoing(Request $request)
    {
        $secret = config('app.gateway_secret');
        if ($secret && $request->bearerToken() !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $phoneNumberId = $request->input('phone_number_id');
        $tenant = Tenant::where('phone_number_id', $phoneNumberId)->where('activo', true)->first();
        if (!$tenant) {
            return response()->json(['error' => 'Unknown tenant'], 404);
        }

        $wamid = $request->input('wamid');

        $existing = $wamid ? MessageLog::where('wamid', $wamid)->first() : null;
        if ($existing) {
            $existing->update(['message' => $request->input('message')]);
        } else {
            MessageLog::create([
                'tenant_id' => $tenant->id,
                'from'      => $request->input('to'),
                'wamid'     => $wamid,
                'type'      => 'outgoing',
                'message'   => $request->input('message'),
                'status'    => 'sent',
                'api_ok'    => true,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    // ── Procesamiento por canal ──────────────────────────────────────────────

    private function handleWhatsapp(array $body): \Illuminate\Http\JsonResponse
    {
        $value         = data_get($body, 'entry.0.changes.0.value');
        $phoneNumberId = data_get($value, 'metadata.phone_number_id');

        if (!$phoneNumberId) {
            return response()->json(['status' => 'ignored']);
        }

        $tenant = Cache::remember("tenant_phone_{$phoneNumberId}", 300, fn() =>
            Tenant::where('phone_number_id', $phoneNumberId)->where('activo', true)->first()
        );

        if (!$tenant) {
            Log::warning("Gateway: phone_number_id sin tenant [{$phoneNumberId}]");
            return response()->json(['status' => 'ignored']);
        }

        // Status updates (sent/delivered/read)
        if (!empty($value['statuses']) && empty($value['messages'])) {
            foreach ($value['statuses'] as $status) {
                $wamidStatus = $status['id']          ?? null;
                $newStatus   = $status['status']      ?? null;
                $recipient   = $status['recipient_id'] ?? null;
                if (!$wamidStatus || !$newStatus) continue;

                $updated = MessageLog::where('wamid', $wamidStatus)->update(['status' => $newStatus]);

                if (!$updated) {
                    MessageLog::create([
                        'tenant_id' => $tenant->id,
                        'from'      => $recipient,
                        'wamid'     => $wamidStatus,
                        'type'      => 'outgoing',
                        'status'    => $newStatus,
                        'api_ok'    => true,
                    ]);
                }
            }
            return response()->json(['status' => 'ok']);
        }

        $from    = data_get($value, 'messages.0.from');
        $msgType = data_get($value, 'messages.0.type', 'unknown');
        $msgText = data_get($value, 'messages.0.text.body');
        $wamid   = data_get($value, 'messages.0.id');

        [$apiOk, $apiStatus] = $this->forwardToTenant($tenant, $body, 'whatsapp');

        $fallbackSent = false;
        if (!$apiOk && $tenant->whatsapp_token && $tenant->mensaje_fallback && $from) {
            $fallbackSent = $this->sendFallback($tenant, $from);
        }

        MessageLog::create([
            'tenant_id'     => $tenant->id,
            'canal'         => 'whatsapp',
            'from'          => $from,
            'wamid'         => $wamid,
            'type'          => $msgType,
            'message'       => $msgText,
            'payload'       => $body,
            'api_ok'        => $apiOk,
            'api_status'    => $apiStatus,
            'fallback_sent' => $fallbackSent,
        ]);

        return response()->json(['status' => 'ok']);
    }

    private function handleMessenger(array $body, string $object): \Illuminate\Http\JsonResponse
    {
        $pageId = data_get($body, 'entry.0.id');

        if (!$pageId) {
            return response()->json(['status' => 'ignored']);
        }

        $tenant = Cache::remember("tenant_page_{$pageId}", 300, fn() =>
            Tenant::where('page_id', $pageId)->where('activo', true)->first()
        );

        if (!$tenant) {
            Log::warning("Gateway: page_id sin tenant [{$pageId}] (canal: {$object})");
            return response()->json(['status' => 'ignored']);
        }

        $canal   = $object === 'instagram' ? 'instagram' : 'messenger';
        $from    = data_get($body, 'entry.0.messaging.0.sender.id');
        $msgText = data_get($body, 'entry.0.messaging.0.message.text');
        $mid     = data_get($body, 'entry.0.messaging.0.message.mid');

        [$apiOk, $apiStatus] = $this->forwardToTenant($tenant, $body, $canal);

        MessageLog::create([
            'tenant_id'  => $tenant->id,
            'canal'      => $canal,
            'from'       => $from,
            'wamid'      => $mid,
            'type'       => 'text',
            'message'    => $msgText,
            'payload'    => $body,
            'api_ok'     => $apiOk,
            'api_status' => $apiStatus,
        ]);

        return response()->json(['status' => 'ok']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Reenvía el payload al API del tenant con el header X-Canal.
     * Devuelve [$apiOk, $apiStatus].
     */
    private function forwardToTenant(object $tenant, array $body, string $canal): array
    {
        try {
            $response = Http::withToken($tenant->api_secret)
                ->withHeaders(['X-Canal' => $canal])
                ->timeout(30)
                ->post($tenant->api_url, $body);

            $apiStatus = $response->status();

            if ($response->successful()) {
                return [true, $apiStatus];
            }

            Log::error("Gateway: API del tenant [{$tenant->nombre}] respondió {$apiStatus}", [
                'url'  => $tenant->api_url,
                'body' => $response->body(),
            ]);
            return [false, $apiStatus];

        } catch (\Throwable $e) {
            Log::error("Gateway: API del tenant [{$tenant->nombre}] no respondió: {$e->getMessage()}");
            return [false, null];
        }
    }

    private function sendFallback(object $tenant, string $to): bool
    {
        try {
            Http::withToken($tenant->whatsapp_token)
                ->timeout(10)
                ->post("https://graph.facebook.com/v19.0/{$tenant->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['body' => $tenant->mensaje_fallback],
                ]);
            return true;
        } catch (\Throwable $e) {
            Log::error("Gateway fallback: no se pudo enviar mensaje a {$to}: {$e->getMessage()}");
            return false;
        }
    }
}
