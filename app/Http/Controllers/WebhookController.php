<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
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
     * POST — Llega un mensaje de WhatsApp.
     * Identifica el negocio por phone_number_id y reenvía al API correspondiente.
     */
    public function handle(Request $request)
    {
        try {
            $body          = $request->json()->all();
            $value         = data_get($body, 'entry.0.changes.0.value');
            $phoneNumberId = data_get($value, 'metadata.phone_number_id');

            if (!$phoneNumberId) {
                return response()->json(['status' => 'ignored']);
            }

            $tenant = Tenant::where('phone_number_id', $phoneNumberId)
                ->where('activo', true)
                ->first();

            if (!$tenant) {
                Log::warning("Gateway: phone_number_id sin tenant [{$phoneNumberId}]");
                return response()->json(['status' => 'ignored']);
            }

            $from    = data_get($value, 'messages.0.from');
            $msgType = data_get($value, 'messages.0.type', 'unknown');
            $msgText = data_get($value, 'messages.0.text.body');

            // Reenviar el payload completo al API del negocio
            $apiOk     = false;
            $apiStatus = null;
            try {
                $response  = Http::withToken($tenant->api_secret)
                    ->timeout(30)
                    ->post($tenant->api_url, $body);

                $apiStatus = $response->status();

                if ($response->successful()) {
                    $apiOk = true;
                } else {
                    Log::error("Gateway: API del tenant [{$tenant->nombre}] respondió {$apiStatus}", [
                        'url'  => $tenant->api_url,
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("Gateway: API del tenant [{$tenant->nombre}] no respondió: {$e->getMessage()}");
            }

            // Si el API falló y hay fallback configurado, responder al cliente por WhatsApp
            $fallbackSent = false;
            if (!$apiOk && $tenant->whatsapp_token && $tenant->mensaje_fallback && $from) {
                $fallbackSent = $this->sendFallback($tenant, $from);
            }

            // Guardar log del mensaje
            MessageLog::create([
                'tenant_id'     => $tenant->id,
                'from'          => $from,
                'type'          => $msgType,
                'message'       => $msgText,
                'payload'       => $body,
                'api_ok'        => $apiOk,
                'api_status'    => $apiStatus,
                'fallback_sent' => $fallbackSent,
            ]);

        } catch (\Throwable $e) {
            Log::error("Gateway error: {$e->getMessage()}", ['exception' => $e]);
        }

        // Siempre 200 a WhatsApp para que no reintente
        return response()->json(['status' => 'ok']);
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
