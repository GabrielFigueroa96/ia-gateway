<?php

namespace App\Http\Controllers;

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

            // Reenviar el payload completo al API del negocio
            $response = Http::withToken($tenant->api_secret)
                ->timeout(30)
                ->post($tenant->api_url, $body);

            if (!$response->successful()) {
                Log::error("Gateway: API del tenant [{$tenant->nombre}] respondió {$response->status()}", [
                    'url'  => $tenant->api_url,
                    'body' => $response->body(),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error("Gateway error: {$e->getMessage()}", ['exception' => $e]);
        }

        // Siempre 200 a WhatsApp para que no reintente
        return response()->json(['status' => 'ok']);
    }
}
