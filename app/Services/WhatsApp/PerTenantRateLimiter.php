<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rate limiter par tenant respectant les tiers Meta Cloud API (Phase 4 Plan v4).
 *
 * Tiers Meta business-initiated conversations / 24h glissantes :
 *   - Tier 1 (default) : 1 000
 *   - Tier 2 : 10 000
 *   - Tier 3 : 100 000
 *   - Unlimited
 *
 * Documentation Meta :
 * https://developers.facebook.com/docs/whatsapp/cloud-api/messages/rate-limits
 *
 * Stratégie :
 * 1. Tracking par tenant via Cache::increment (clé glissante par fenêtre 24h)
 * 2. Vérif avant chaque envoi : current_24h_count < tier_limit ?
 * 3. Si dépassement → throw RateLimitExceededException (caller skip + log + alert)
 *
 * Source de vérité tier : parent_notification_logs.cost_fcfa SUM sur 24h ÷ coût unitaire
 * OU compteur Cache séparé. On utilise Cache pour latence < 1ms.
 */
class PerTenantRateLimiter
{
    private const CACHE_KEY_PREFIX = 'whatsapp_rate_';
    private const WINDOW_SECONDS = 86400; // 24h

    /**
     * Tier limits Meta (configurables par tenant via settings DB future Phase 4 complete).
     */
    private const DEFAULT_TIER_LIMITS = [
        1 => 1000,
        2 => 10000,
        3 => 100000,
        4 => null, // unlimited
    ];

    /**
     * Vérifie si le tenant peut envoyer 1 message supplémentaire dans la fenêtre 24h.
     *
     * @throws RateLimitExceededException si tier atteint
     */
    public function check(string $tenantCode, int $currentTier = 1): void
    {
        $limit = self::DEFAULT_TIER_LIMITS[$currentTier] ?? null;

        if ($limit === null) {
            return; // Tier 4 unlimited
        }

        $count = $this->currentCount($tenantCode);

        if ($count >= $limit) {
            throw new RateLimitExceededException(
                "Tenant '{$tenantCode}' rate limit reached : {$count}/{$limit} messages in 24h (Tier {$currentTier})"
            );
        }
    }

    /**
     * Incrémente le compteur après envoi réussi.
     */
    public function increment(string $tenantCode): int
    {
        $key = $this->cacheKey($tenantCode);

        $count = Cache::increment($key);

        if ($count === 1) {
            // Première écriture — set le TTL window
            Cache::put($key, 1, self::WINDOW_SECONDS);
        }

        return $count;
    }

    public function currentCount(string $tenantCode): int
    {
        return (int) Cache::get($this->cacheKey($tenantCode), 0);
    }

    public function reset(string $tenantCode): bool
    {
        return Cache::forget($this->cacheKey($tenantCode));
    }

    /**
     * Snapshot statistiques pour dashboard monitoring (Phase 16).
     *
     * @return array{tier: int, limit: int|null, used: int, percentage: float, window_resets_at: string}
     */
    public function snapshot(string $tenantCode, int $currentTier = 1): array
    {
        $limit = self::DEFAULT_TIER_LIMITS[$currentTier] ?? null;
        $used = $this->currentCount($tenantCode);

        return [
            'tier' => $currentTier,
            'limit' => $limit,
            'used' => $used,
            'percentage' => $limit ? round(($used / $limit) * 100, 2) : 0,
            'window_resets_at' => now()->addSeconds(self::WINDOW_SECONDS)->toIso8601String(),
        ];
    }

    private function cacheKey(string $tenantCode): string
    {
        return self::CACHE_KEY_PREFIX . $tenantCode;
    }
}
