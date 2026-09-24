<?php

namespace App\Domain\Analytics\Quality;

use App\Domain\Analytics\Aging\ReceivablesAging;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\Repositories\StudentRiskRepository;
use App\Helpers\SettingsHelper;
use App\Services\Analytics\AnalyticsScanCache;
use App\Services\EcheancierCoverageService;
use App\Services\EcheancierReadinessService;
use Carbon\CarbonImmutable;

/**
 * Ce qu'il faut savoir AVANT de lire une prevision : les donnees sont-elles
 * fiables, l'echeancier est-il configure, et ou en sont les impayes.
 *
 * Source unique : la page analytics et `analytics:diagnose` lisent tous deux
 * ce service, pour qu'ils ne se contredisent jamais.
 */
class AnalyticsReliabilityService
{
    /** Plafond d'inscriptions lues pour l'anciennete (au-dela, le calcul le dit). */
    public const AGING_MAX_STUDENTS = 6000;

    public function __construct(
        private readonly PaymentQualityQuery $query,
        private readonly PaymentQualityEvaluator $evaluator,
        private readonly ReceivablesAging $aging,
        private readonly StudentRiskRepository $students,
        private readonly EcheancierReadinessService $readiness,
        private readonly EcheancierCoverageService $coverage,
        private readonly AnalyticsScanCache $cache,
    ) {
    }

    /** Fiabilite des paiements saisis, sans l'anciennete (rapide). */
    public function dataQuality(?CarbonImmutable $now = null): array
    {
        $seuils = SettingsHelper::getAnalyticsSettings()['fiabilite'];
        $now ??= CarbonImmutable::now();
        $since = $now->subMonths(max(1, $seuils['lookback_months']))->startOfDay();

        $quality = $this->evaluator->evaluate(
            $this->query->entryGroups($since, $seuils['catchup_lag_days']),
            $this->query->lastEntryAt(),
            $now,
            $seuils
        );
        $quality['periode_analysee'] = ['depuis' => $since->toDateString(), 'mois' => $seuils['lookback_months']];

        return $quality;
    }

    public function build(AnalyticsContext $context): array
    {
        $mode = $this->readiness->mode();
        $aging = $this->cache->remember('aging', md5(json_encode($context->toArray())), function () use ($context, $mode) {
            $students = $this->students->activeStudents($context, self::AGING_MAX_STUDENTS);

            return $this->aging->build($students, $mode === EcheancierReadinessService::MODE_FALLBACK)
                + ['tronque' => count($students) >= self::AGING_MAX_STUDENTS];
        });

        return [
            'fiabilite' => $this->dataQuality(),
            'echeancier' => [
                'mode' => $mode,
                'mode_degrade' => $mode === EcheancierReadinessService::MODE_FALLBACK,
                // noteForMode() relancerait la requete de mode() : on reutilise $mode.
                'note' => $mode === EcheancierReadinessService::MODE_FALLBACK ? $this->readiness->noteForMode() : null,
                'couverture' => $this->coverage->summary($context->anneeId),
            ],
            'anciennete' => $aging['data'] + ['calcule_le' => $aging['computed_at']->toIso8601String()],
        ];
    }
}
