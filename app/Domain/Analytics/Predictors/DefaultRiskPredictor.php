<?php

namespace App\Domain\Analytics\Predictors;

use App\Domain\Analytics\Algorithms\LogisticScoring;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\DTOs\PredictionResult;
use App\Domain\Analytics\DTOs\StudentRiskFeatures;
use App\Domain\Analytics\Repositories\StudentRiskRepository;
use App\Helpers\SettingsHelper;
use App\Services\EcheancierReadinessService;

/**
 * Évalue le risque de défaut de paiement par inscription. Pour chaque étudiant
 * actif du périmètre, normalise 4 features financières dans [0,1] puis calcule
 * un score logistique. Aggrège par niveau (haut/moyen/bas) et expose le top-N
 * via metadata pour rendu UI.
 *
 * Poids et seuils configurables via /esbtp/comptabilite/analytics/settings (B4).
 */
class DefaultRiskPredictor implements PredictorInterface
{
    private const NAME = 'default_risk';

    public const DEFAULT_WEIGHT_SOLDE = 3.0;
    public const DEFAULT_WEIGHT_RETARD = 2.5;
    public const DEFAULT_WEIGHT_ENGAGEMENT = 1.0;
    public const DEFAULT_WEIGHT_MONTANT = 0.5;
    public const DEFAULT_BIAS = -2.5;
    public const DEFAULT_THRESHOLD_HIGH = 0.66;
    public const DEFAULT_THRESHOLD_MEDIUM = 0.33;
    public const DEFAULT_TOP_N = 50;

    public const RETARD_NORMALIZATION_DAYS = 90;
    public const MONTANT_NORMALIZATION_FCFA = 2_000_000.0;

    /**
     * @param  array<string, float|int>  $configOverrides  Bypass Settings (utile tests)
     */
    public function __construct(
        private readonly StudentRiskRepository $repository,
        private readonly EcheancierReadinessService $echeancierReadiness,
        private readonly array $configOverrides = [],
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function minimumHistoryMonths(): int
    {
        return 0;
    }

    public function predict(AnalyticsContext $context): PredictionResult
    {
        $unavailableReason = $this->echeancierReadiness->unavailableReason();
        if ($unavailableReason !== null) {
            return PredictionResult::unavailable(self::NAME, $unavailableReason);
        }

        $echeancierMode = $this->echeancierReadiness->mode();

        $students = $this->repository->activeStudents($context, max($this->topN() * 4, 200));

        if (empty($students)) {
            return PredictionResult::unavailable(
                self::NAME,
                'Aucun étudiant actif dans ce périmètre',
            );
        }

        $weights = $this->weights();
        $bias = $this->bias();
        $thresholdHigh = $this->thresholdHigh();
        $thresholdMedium = $this->thresholdMedium();

        $scored = [];
        $bucketCounts = ['haut' => 0, 'moyen' => 0, 'bas' => 0];
        $totalSoldeHaut = 0.0;

        foreach ($students as $student) {
            if ($student->isPaid()) {
                $bucketCounts['bas']++;
                continue;
            }

            $features = $this->extractFeatures($student);
            $score = LogisticScoring::score($features, $weights, $bias);
            $level = LogisticScoring::riskLabel($score, $thresholdHigh, $thresholdMedium);
            $bucketCounts[$level]++;

            if ($level === 'haut') {
                $totalSoldeHaut += $student->overdueAmount;
            }

            $scored[] = [
                'inscription_id' => $student->inscriptionId,
                'etudiant_id' => $student->etudiantId,
                'etudiant_nom' => $student->etudiantNom,
                'classe_nom' => $student->classeNom,
                'solde_restant' => $student->soldeRestant,
                'montant_echu' => $student->overdueAmount,
                'attendu_a_date' => $student->expectedDueToDate,
                'paye_a_date' => $student->paidDueToDate,
                'jours_retard' => $student->joursRetard,
                'ratio_paye' => $student->ratioPaye,
                'score' => $score,
                'level' => $level,
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $topN = array_slice($scored, 0, $this->topN());

        $totalActifs = count($students);
        $totalARisque = $bucketCounts['haut'] + $bucketCounts['moyen'];
        $tauxRisque = $totalActifs > 0 ? round($totalARisque / $totalActifs * 100, 1) : 0.0;

        return new PredictionResult(
            predictor: self::NAME,
            value: (float) $bucketCounts['haut'],
            label: 'haut_risque_count',
            confidenceInterval: null,
            confidenceLabel: $echeancierMode === \App\Services\EcheancierReadinessService::MODE_FALLBACK
                ? 'indicatif'
                : $this->confidenceLabel($totalActifs),
            explanation: $this->buildExplanation($bucketCounts, $totalActifs, $totalSoldeHaut, $tauxRisque, $echeancierMode),
            metadata: [
                'total_actifs' => $totalActifs,
                'buckets' => $bucketCounts,
                'taux_risque_pct' => $tauxRisque,
                'total_solde_haut_risque' => $totalSoldeHaut,
                'top_at_risk' => $topN,
                'thresholds' => [
                    'high' => $thresholdHigh,
                    'medium' => $thresholdMedium,
                ],
                'echeancier_mode' => $echeancierMode,
                'echeancier_mode_note' => $this->echeancierReadiness->noteForMode(),
            ],
        );
    }

    /**
     * @return array<string, float>
     */
    private function extractFeatures(StudentRiskFeatures $student): array
    {
        $ratioSolde = $student->expectedDueToDate > 0
            ? min(1.0, $student->overdueAmount / $student->expectedDueToDate)
            : 0.0;
        $ratioRetard = min(1.0, $student->joursRetard / self::RETARD_NORMALIZATION_DAYS);
        $ratioEngagement = match (true) {
            $student->nbPaiements >= 2 => 0.0,
            $student->nbPaiements === 1 => 0.5,
            default => 1.0,
        };
        $ratioMontant = min(1.0, $student->totalAttendu / self::MONTANT_NORMALIZATION_FCFA);

        return [
            'solde' => $ratioSolde,
            'retard' => $ratioRetard,
            'engagement' => $ratioEngagement,
            'montant' => $ratioMontant,
        ];
    }

    /**
     * @param  array<string, int>  $bucketCounts
     * @return array<int, string>
     */
    private function buildExplanation(array $bucketCounts, int $totalActifs, float $totalSoldeHaut, float $tauxRisque, string $echeancierMode = \App\Services\EcheancierReadinessService::MODE_CONFIGURED): array
    {
        $reasons = [
            sprintf(
                '%d étudiants à haut risque sur %d actifs (%.1f%% à risque cumulé)',
                $bucketCounts['haut'],
                $totalActifs,
                $tauxRisque,
            ),
        ];

        if ($totalSoldeHaut > 0) {
            $reasons[] = sprintf(
                'Exposition haut risque : %s FCFA non recouvrés',
                number_format($totalSoldeHaut, 0, ',', ' '),
            );
        }

        if ($bucketCounts['moyen'] > 0) {
            $reasons[] = sprintf(
                '%d étudiants en surveillance — relances ciblées recommandées',
                $bucketCounts['moyen'],
            );
        } elseif ($tauxRisque < 5.0) {
            $reasons[] = 'Cohorte saine — situation financière sous contrôle';
        }

        if ($echeancierMode === \App\Services\EcheancierReadinessService::MODE_FALLBACK) {
            $reasons[] = "Mode dégradé : pas de règle d'échéancier configurée — l'échéance par défaut de chaque catégorie est utilisée.";
        }

        return $reasons;
    }

    private function confidenceLabel(int $totalActifs): string
    {
        return match (true) {
            $totalActifs >= 200 => 'tres_fiable',
            $totalActifs >= 50 => 'fiable',
            default => 'indicatif',
        };
    }

    /**
     * @return array<string, float>
     */
    private function weights(): array
    {
        return [
            'solde' => $this->configFloat('weight.solde', self::DEFAULT_WEIGHT_SOLDE),
            'retard' => $this->configFloat('weight.retard', self::DEFAULT_WEIGHT_RETARD),
            'engagement' => $this->configFloat('weight.engagement', self::DEFAULT_WEIGHT_ENGAGEMENT),
            'montant' => $this->configFloat('weight.montant', self::DEFAULT_WEIGHT_MONTANT),
        ];
    }

    private function bias(): float
    {
        return $this->configFloat('bias', self::DEFAULT_BIAS);
    }

    private function thresholdHigh(): float
    {
        return $this->configFloat('threshold_high', self::DEFAULT_THRESHOLD_HIGH);
    }

    private function thresholdMedium(): float
    {
        return $this->configFloat('threshold_medium', self::DEFAULT_THRESHOLD_MEDIUM);
    }

    private function topN(): int
    {
        return (int) $this->configFloat('top_n', (float) self::DEFAULT_TOP_N);
    }

    private function configFloat(string $key, float $default): float
    {
        if (array_key_exists($key, $this->configOverrides)) {
            return (float) $this->configOverrides[$key];
        }
        return (float) SettingsHelper::get("analytics.default_risk.{$key}", $default);
    }
}
