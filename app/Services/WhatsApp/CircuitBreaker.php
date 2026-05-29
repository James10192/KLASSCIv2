<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit breaker autour Meta API (Phase 4 Plan v4 hardening).
 *
 * Pattern : si N échecs consécutifs en T secondes, ouvre le circuit pendant U secondes
 * (skip tous les envois). Évite de hammerer Meta lors d'une panne et préserve les
 * quotas.
 *
 * États :
 *  - CLOSED : envois normaux
 *  - OPEN : envois suspendus (panne détectée)
 *  - HALF-OPEN : test 1 envoi pour voir si l'API est revenue
 *
 * Seuils par défaut : 5 échecs en 60s → ouverture 5 min.
 *
 * @see https://martinfowler.com/bliki/CircuitBreaker.html
 */
class CircuitBreaker
{
    private const CACHE_KEY_PREFIX = 'whatsapp_breaker_';
    private const FAILURE_THRESHOLD = 5;
    private const FAILURE_WINDOW = 60;
    private const OPEN_DURATION = 300; // 5 min

    public function isOpen(string $tenantCode): bool
    {
        return Cache::has($this->openKey($tenantCode));
    }

    public function recordSuccess(string $tenantCode): void
    {
        Cache::forget($this->failuresKey($tenantCode));
        Cache::forget($this->openKey($tenantCode));
    }

    public function recordFailure(string $tenantCode, string $reason = ''): void
    {
        $key = $this->failuresKey($tenantCode);
        $count = Cache::increment($key);

        if ($count === 1) {
            Cache::put($key, 1, self::FAILURE_WINDOW);
        }

        if ($count >= self::FAILURE_THRESHOLD) {
            $this->open($tenantCode, $reason);
        }
    }

    /**
     * Force ouverture du circuit (utile pour maintenance planifiée).
     */
    public function open(string $tenantCode, string $reason = 'manual'): void
    {
        Cache::put($this->openKey($tenantCode), [
            'opened_at' => now()->toIso8601String(),
            'reason' => $reason,
        ], self::OPEN_DURATION);

        Log::warning('[whatsapp-circuit] Circuit OPEN', [
            'tenant' => $tenantCode,
            'reason' => $reason,
            'duration_seconds' => self::OPEN_DURATION,
        ]);
    }

    public function close(string $tenantCode): void
    {
        Cache::forget($this->openKey($tenantCode));
        Cache::forget($this->failuresKey($tenantCode));

        Log::info('[whatsapp-circuit] Circuit CLOSED manually', ['tenant' => $tenantCode]);
    }

    public function status(string $tenantCode): array
    {
        $isOpen = $this->isOpen($tenantCode);
        $failures = (int) Cache::get($this->failuresKey($tenantCode), 0);

        return [
            'state' => $isOpen ? 'OPEN' : 'CLOSED',
            'failures_in_window' => $failures,
            'failure_threshold' => self::FAILURE_THRESHOLD,
            'failure_window_seconds' => self::FAILURE_WINDOW,
            'open_data' => $isOpen ? Cache::get($this->openKey($tenantCode)) : null,
        ];
    }

    private function openKey(string $tenantCode): string
    {
        return self::CACHE_KEY_PREFIX . 'open_' . $tenantCode;
    }

    private function failuresKey(string $tenantCode): string
    {
        return self::CACHE_KEY_PREFIX . 'failures_' . $tenantCode;
    }
}
