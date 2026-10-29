<?php

namespace App\Domain\Analytics\Cache;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\PredictionResult;
use App\Domain\Analytics\Predictors\PredictorInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Décorateur cache autour d'un PredictorInterface. Cache key dérive du
 * predictor.name() + context.hash(). TTL configurable (défaut 1h).
 *
 * Usage : new CachedPredictor($cashFlow, ttlSeconds: 3600)
 */
class CachedPredictor implements PredictorInterface
{
    private const CACHE_VERSION = 3;

    public function __construct(
        private readonly PredictorInterface $inner,
        private readonly int $ttlSeconds = 3600,
    ) {}

    public function name(): string
    {
        return $this->inner->name();
    }

    public function minimumHistoryMonths(): int
    {
        return $this->inner->minimumHistoryMonths();
    }

    public function predict(AnalyticsContext $context): PredictionResult
    {
        $key = $this->cacheKey($context);

        $cached = Cache::get($key);
        if ($cached instanceof PredictionResult) {
            return $cached;
        }

        $result = $this->inner->predict($context);
        Cache::put($key, $result, $this->ttlSeconds);

        return $result;
    }

    public function forget(AnalyticsContext $context): void
    {
        Cache::forget($this->cacheKey($context));
    }

    private function cacheKey(AnalyticsContext $context): string
    {
        return sprintf('analytics:v%d:%s:%s', self::CACHE_VERSION, $this->name(), $context->hash());
    }
}
