<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fire-and-forget notification to adminKlassci master that a tenant state change
 * occurred (paiement validated, inscription created), so the group portal cache
 * refreshes immediately instead of waiting for the 2-5min TTL.
 *
 * Non-blocking: a failed master API call is logged but never fails the user flow.
 * If MASTER_API_URL or MASTER_API_TOKEN is missing, the call is skipped silently.
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

        try {
            Http::withToken($tenantToken)
                ->connectTimeout(2)
                ->timeout(3)
                ->acceptJson()
                ->post($url, ['trigger' => $trigger]);
        } catch (\Exception $e) {
            Log::info("GroupCacheInvalidator failed (non-blocking): {$e->getMessage()}");
        }
    }
}
