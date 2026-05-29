<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Résolveur de configuration WhatsApp côté tenant (Phase 1 Plan v4).
 *
 * Récupère les credentials Meta Cloud API depuis l'API master adminKlassci
 * via GET /api/tenants/{code}/whatsapp-config (Bearer token Sanctum).
 *
 * Cache 5min — pattern identique à PaywallMiddleware (réduit la charge sur
 * adminKlassci, tolérant aux pannes courtes du master).
 *
 * Fallback : si API master inaccessible, retourne config disabled (les notifications
 * WhatsApp seront skipées sans casser l'application — les autres canaux fonctionnent).
 *
 * @see adminKlassci/app/Http/Controllers/API/TenantWhatsAppConfigController.php
 * @see app/Http/Middleware/PaywallMiddleware.php (pattern référence cache + fallback)
 */
class TenantConfigResolver
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Retourne la config WhatsApp du tenant courant.
     *
     * @return array{
     *     enabled: bool,
     *     phone_number_id?: string,
     *     access_token?: string,
     *     business_account_id?: string,
     *     webhook_verify_token?: string,
     *     configured_at?: string|null,
     *     reason?: string,
     * }
     */
    public function getConfig(): array
    {
        $tenantCode = config('app.tenant_code') ?? env('TENANT_CODE');

        if (empty($tenantCode)) {
            return ['enabled' => false, 'reason' => 'TENANT_CODE missing in .env'];
        }

        return Cache::remember(
            "whatsapp_config_{$tenantCode}",
            self::CACHE_TTL,
            fn () => $this->fetchFromMaster($tenantCode),
        );
    }

    /**
     * Invalide le cache (utile post-update credentials côté master).
     */
    public function invalidateCache(): bool
    {
        $tenantCode = config('app.tenant_code') ?? env('TENANT_CODE');

        if (empty($tenantCode)) {
            return false;
        }

        return Cache::forget("whatsapp_config_{$tenantCode}");
    }

    private function fetchFromMaster(string $tenantCode): array
    {
        $masterUrl = config('services.master.url') ?? env('MASTER_API_URL');
        $masterToken = config('services.master.token') ?? env('MASTER_API_TOKEN');

        if (empty($masterUrl) || empty($masterToken)) {
            Log::warning('[whatsapp-config] MASTER_API_URL ou MASTER_API_TOKEN manquant', [
                'tenant_code' => $tenantCode,
            ]);

            return ['enabled' => false, 'reason' => 'Master API credentials missing'];
        }

        try {
            $response = Http::withToken($masterToken)
                ->acceptJson()
                ->timeout(5)
                ->get("{$masterUrl}/tenants/{$tenantCode}/whatsapp-config");

            if (! $response->successful()) {
                Log::warning('[whatsapp-config] Master API non-2xx response', [
                    'tenant_code' => $tenantCode,
                    'status' => $response->status(),
                ]);

                return ['enabled' => false, 'reason' => "Master API status {$response->status()}"];
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::error('[whatsapp-config] Master API unreachable', [
                'tenant_code' => $tenantCode,
                'error' => $e->getMessage(),
            ]);

            // Fallback : disabled, les notifications WhatsApp seront skipées proprement
            return ['enabled' => false, 'reason' => 'Master API unreachable: ' . $e->getMessage()];
        }
    }
}
