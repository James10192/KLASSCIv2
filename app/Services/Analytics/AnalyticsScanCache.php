<?php

namespace App\Services\Analytics;

use App\Helpers\SettingsHelper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Mémorisation partagée des balayages analytiques lourds (RecouvrementGapService,
 * CashFlowProjectionService).
 *
 * Pourquoi mémoriser alors que ces chiffres sont comptables : l'écart de
 * recouvrement ne porte QUE sur des mois clos, et la projection ne porte que sur
 * des échéances futures. Aucun des deux n'est un solde vivant qu'un caissier
 * consulte pour encaisser — ce sont des tendances. Un décalage de quelques
 * minutes n'a donc pas de conséquence comptable, À CONDITION que l'écran affiche
 * la fraîcheur de la donnée : c'est pour cela que `computed_at` est mémorisé avec
 * la valeur et remonté jusqu'à la vue.
 *
 * Nuance assumée : l'allocation FIFO fait qu'un encaissement du jour réduit
 * rétroactivement l'écart d'un mois ancien. Le chiffre bouge donc encore, même
 * sur du passé — d'où l'invalidation à la validation d'un paiement
 * (ESBTPPaiementAnalyticsScanObserver) en plus de la durée de vie.
 */
class AnalyticsScanCache
{
    /**
     * Clé de génération. Toute invalidation la remplace par une valeur neuve, ce
     * qui déréférence d'un coup toutes les entrées mémorisées — le pilote de
     * cache par défaut est `file`, qui ne sait pas purger par tag.
     */
    private const GENERATION_KEY = 'analytics:scan:generation';

    private const KEY_PREFIX = 'analytics:scan';

    /** Durée de vie par défaut, en secondes. Modifiable par instance. */
    public const DEFAULT_TTL_SECONDS = 900;

    /**
     * La mémorisation est-elle active sur cette instance ?
     *
     * Mettre `analytics.scan_cache.enabled` à 0 restaure exactement le
     * comportement d'avant : chaque affichage rebalaye toutes les inscriptions.
     */
    public function enabled(): bool
    {
        return (string) SettingsHelper::get('analytics.scan_cache.enabled', '1') === '1'
            && $this->ttlSeconds() > 0;
    }

    /**
     * Une école qui encaisse en continu voudra une fenêtre courte ; une école qui
     * consulte l'analytique une fois par semaine peut monter à plusieurs heures.
     */
    public function ttlSeconds(): int
    {
        return max(0, (int) SettingsHelper::get('analytics.scan_cache.ttl_seconds', self::DEFAULT_TTL_SECONDS));
    }

    /**
     * Renvoie la valeur mémorisée, ou la calcule.
     *
     * @param  string  $bucket  Famille de balayage (ex. « recouvrement_gap »).
     * @param  string  $discriminator  Ce qui distingue deux balayages de la même
     *                                 famille : portée + fenêtre temporelle.
     * @param  \Closure():mixed  $compute
     * @return array{data: mixed, computed_at: \Carbon\CarbonImmutable, from_cache: bool}
     */
    public function remember(string $bucket, string $discriminator, \Closure $compute): array
    {
        if (! $this->enabled()) {
            return [
                'data' => $compute(),
                'computed_at' => CarbonImmutable::now(),
                'from_cache' => false,
            ];
        }

        $key = $this->cacheKey($bucket, $discriminator);
        $cached = Cache::get($key);

        if (is_array($cached) && array_key_exists('data', $cached) && isset($cached['computed_at'])) {
            return [
                'data' => $cached['data'],
                'computed_at' => CarbonImmutable::parse($cached['computed_at']),
                'from_cache' => true,
            ];
        }

        $computedAt = CarbonImmutable::now();
        $data = $compute();

        // L'horodatage est sérialisé en chaîne : un objet Carbon dans un cache
        // `file` dépend de la classe au moment de la relecture, une chaîne non.
        Cache::put($key, [
            'data' => $data,
            'computed_at' => $computedAt->toIso8601String(),
        ], $this->ttlSeconds());

        return [
            'data' => $data,
            'computed_at' => $computedAt,
            'from_cache' => false,
        ];
    }

    /**
     * Déréférence tout ce qui est mémorisé. Appelé quand un paiement validé
     * apparaît, change ou disparaît : l'allocation FIFO peut alors déplacer des
     * montants sur des mois déjà clos.
     */
    public function invalidate(): void
    {
        Cache::forever(self::GENERATION_KEY, $this->freshGeneration());
    }

    private function cacheKey(string $bucket, string $discriminator): string
    {
        return sprintf('%s:%s:%s:%s', self::KEY_PREFIX, $this->generation(), $bucket, sha1($discriminator));
    }

    /**
     * Une génération perdue (cache vidé, fichier effacé) ne doit JAMAIS pouvoir
     * reprendre une valeur déjà utilisée, sinon des entrées périmées seraient
     * ressuscitées. On repart donc d'une valeur horodatée et aléatoire, jamais
     * d'un compteur qui recommencerait à 1.
     */
    private function generation(): string
    {
        $generation = Cache::get(self::GENERATION_KEY);

        if (! is_string($generation) || $generation === '') {
            $generation = $this->freshGeneration();
            Cache::forever(self::GENERATION_KEY, $generation);
        }

        return $generation;
    }

    private function freshGeneration(): string
    {
        return CarbonImmutable::now()->format('YmdHisv') . '-' . Str::random(6);
    }
}
