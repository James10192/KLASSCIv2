<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fire-and-forget notification to adminKlassci master that a tenant state change
 * occurred (paiement validated, inscription created), so the group portal cache
 * refreshes immediately instead of waiting for the 2-5min TTL.
 *
 * Runs after the response is sent (dispatch::afterResponse) so the HTTP call —
 * up to 5s if master is slow — never adds latency to the user-facing request.
 * If MASTER_API_URL / MASTER_API_TOKEN / TENANT_CODE is missing, the call is skipped.
 */
class GroupCacheInvalidator
{
    public function invalidate(string $trigger = 'unknown'): void
    {
        $masterUrl = config('services.master.url') ?: env('MASTER_API_URL');
        $tenantToken = config('services.master.token') ?: env('MASTER_API_TOKEN');
        $tenantCode = config('app.tenant_code') ?: env('TENANT_CODE');

        if (! $masterUrl || ! $tenantToken || ! $tenantCode) {
            return;
        }

        $url = rtrim($masterUrl, '/') . "/tenants/{$tenantCode}/cache/invalidate";

        dispatch(function () use ($url, $tenantToken, $trigger) {
            try {
                Http::withToken($tenantToken)
                    ->connectTimeout(2)
                    ->timeout(3)
                    ->acceptJson()
                    ->post($url, ['trigger' => $trigger]);
            } catch (\Exception $e) {
                Log::warning("GroupCacheInvalidator failed (after response): {$e->getMessage()}");
            }
        })->afterResponse();
    }
}
