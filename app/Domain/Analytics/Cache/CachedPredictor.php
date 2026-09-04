<?php

namespace App\Domain\Analytics\Cache;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\PredictionResult;
use App\Domain\Analytics\Predictors\PredictorInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Décorateur cache autour d'un PredictorInterface. Cache key dérive du
 * predictor.name() + context.hash(). TTL configurable (défaut 1h).
 *
 * Usage : new CachedPredictor($cashFlow, ttlSeconds: 3600)
 *
 * Le cache mémorise aussi l'heure du calcul. Sans elle, une prévision d'il y a
 * cinquante-neuf minutes s'affiche exactement comme une prévision de l'instant :
 * le comptable qui vient d'encaisser cherche son versement dans le chiffre et
 * conclut que l'écran ment. `lastComputedAt()` permet de le dire à l'écran.
 */
class CachedPredictor implements PredictorInterface
{
    // v4 : l'entrée porte desormais la date du calcul. Les entrees v3 ne l'ont
    // pas ; changer de version evite d'avoir a deviner leur age.
    private const CACHE_VERSION = 4;

    private ?CarbonImmutable $lastComputedAt = null;

    public function __construct(
        private readonly PredictorInterface $inner,
        private readonly int $ttlSeconds = 3600,
    ) {}

    /**
     * Heure a laquelle le resultat rendu par le dernier `predict()` a ete
     * calcule — celle du calcul d'origine s'il vient du cache. `null` tant
     * qu'aucune prediction n'a ete demandee.
     */
    public function lastComputedAt(): ?CarbonImmutable
    {
        return $this->lastComputedAt;
    }

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
        if (is_array($cached)
            && ($cached['result'] ?? null) instanceof PredictionResult
            && ($cached['computed_at'] ?? null) instanceof CarbonImmutable
        ) {
            $this->lastComputedAt = $cached['computed_at'];

            return $cached['result'];
        }

        $result = $this->inner->predict($context);
        $this->lastComputedAt = CarbonImmutable::now();
        Cache::put($key, ['result' => $result, 'computed_at' => $this->lastComputedAt], $this->ttlSeconds);

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
