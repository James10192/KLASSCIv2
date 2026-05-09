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
    public const DEFAULT_WEIGHT_VELOCITY = -1.5;     // Bonne vélocité (paye en avance) → score réduit
    public const DEFAULT_BIAS = -2.5;
    public const DEFAULT_THRESHOLD_HIGH = 0.66;
    public const DEFAULT_THRESHOLD_MEDIUM = 0.33;
    public const DEFAULT_TOP_N = 50;
    public const DEFAULT_MIN_COHORT_SIZE = 10;       // En dessous, le score perd toute pertinence statistique
    public const DEFAULT_AUTO_CALIBRATE = true;       // Élève dynamiquement threshold_high si > 70% saturent
    public const SATURATION_TRIGGER_PCT = 70.0;       // % au-dessus duquel l'auto-calibration kick in
    public const AUTO_CALIBRATE_TARGET_TOP_PCT = 25.0; // En mode calibré, on garde le top 25% comme "haut"

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

        $minCohortSize = $this->minCohortSize();
        if (count($students) < $minCohortSize) {
            return PredictionResult::unavailable(
                self::NAME,
                sprintf(
                    'Cohorte trop petite (%d étudiants, minimum %d requis pour un score statistiquement pertinent)',
                    count($students),
                    $minCohortSize,
                ),
            );
        }

        $weights = $this->weights();
        $bias = $this->bias();
        $thresholdHigh = $this->thresholdHigh();
        $thresholdMedium = $this->thresholdMedium();

        // Passe 1 : calcule les scores bruts pour tous les étudiants (sans bucketiser).
        $scored = [];
        $bucketCounts = ['haut' => 0, 'moyen' => 0, 'bas' => 0];
        foreach ($students as $student) {
            if ($student->isPaid()) {
                $bucketCounts['bas']++;
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
                    'score' => 0.0,
                    'level' => 'bas',
                    'is_paid' => true,
                ];
                continue;
            }

            $features = $this->extractFeatures($student);
            $score = LogisticScoring::score($features, $weights, $bias);

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
                'level' => null, // assigné en passe 2
                'is_paid' => false,
            ];
        }

        // Passe 2 : auto-calibration optionnelle si saturation détectée
        $totalActifs = count($students);
        $unpaidScored = array_filter($scored, fn ($s) => empty($s['is_paid']));
        $autoCalibrated = false;
        $effectiveThresholdHigh = $thresholdHigh;

        if ($this->autoCalibrateEnabled() && !empty($unpaidScored)) {
            $hautAtDefault = count(array_filter($unpaidScored, fn ($s) => $s['score'] >= $thresholdHigh));
            $hautPctAtDefault = $totalActifs > 0 ? ($hautAtDefault / $totalActifs * 100) : 0.0;

            if ($hautPctAtDefault >= self::SATURATION_TRIGGER_PCT) {
                // Garde uniquement le top N% comme "haut" pour préserver le pouvoir discriminant.
                $sortedScores = collect($unpaidScored)->pluck('score')->sortDesc()->values();
                $cutoffIndex = (int) round(count($sortedScores) * (self::AUTO_CALIBRATE_TARGET_TOP_PCT / 100));
                if ($cutoffIndex > 0 && $cutoffIndex < count($sortedScores)) {
                    $effectiveThresholdHigh = max($thresholdHigh, (float) $sortedScores[$cutoffIndex - 1]);
                    $autoCalibrated = true;
                }
            }
        }

        // Passe 3 : assignation des buckets avec le seuil effectif
        $totalSoldeHaut = 0.0;
        foreach ($scored as &$row) {
            if (!empty($row['is_paid'])) continue;
            $row['level'] = LogisticScoring::riskLabel($row['score'], $effectiveThresholdHigh, $thresholdMedium);
            $bucketCounts[$row['level']]++;
            if ($row['level'] === 'haut') {
                $totalSoldeHaut += $row['montant_echu'];
            }
        }
        unset($row);

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $topN = array_slice($scored, 0, $this->topN());

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
            explanation: $this->buildExplanation($bucketCounts, $totalActifs, $totalSoldeHaut, $tauxRisque, $echeancierMode, $autoCalibrated),
            metadata: [
                'total_actifs' => $totalActifs,
                'buckets' => $bucketCounts,
                'taux_risque_pct' => $tauxRisque,
                'total_solde_haut_risque' => $totalSoldeHaut,
                'top_at_risk' => $topN,
                'thresholds' => [
                    'high' => $thresholdHigh,
                    'high_effective' => $effectiveThresholdHigh,
                    'medium' => $thresholdMedium,
                ],
                'auto_calibrated' => $autoCalibrated,
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

        // Vélocité de paiement : ratio (payé à date) / (attendu à date), clampé [0,1].
        // Étudiant qui a payé 100% de l'attendu à date → velocity=1 → score réduit (poids négatif).
        // Étudiant qui n'a rien payé → velocity=0 → pas de réduction.
        $ratioVelocity = $student->expectedDueToDate > 0
            ? min(1.0, max(0.0, $student->paidDueToDate / $student->expectedDueToDate))
            : 0.0;

        return [
            'solde' => $ratioSolde,
            'retard' => $ratioRetard,
            'engagement' => $ratioEngagement,
            'montant' => $ratioMontant,
            'velocity' => $ratioVelocity,
        ];
    }

    /**
     * @param  array<string, int>  $bucketCounts
     * @return array<int, string>
     */
    private function buildExplanation(array $bucketCounts, int $totalActifs, float $totalSoldeHaut, float $tauxRisque, string $echeancierMode = \App\Services\EcheancierReadinessService::MODE_CONFIGURED, bool $autoCalibrated = false): array
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

        if ($autoCalibrated) {
            $reasons[] = sprintf(
                'Auto-calibration appliquée : seuil "haut" élevé pour préserver la discrimination (cible top %.0f%% en exposition).',
                self::AUTO_CALIBRATE_TARGET_TOP_PCT,
            );
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
            'velocity' => $this->configFloat('weight.velocity', self::DEFAULT_WEIGHT_VELOCITY),
        ];
    }

    private function minCohortSize(): int
    {
        return (int) $this->configFloat('min_cohort_size', (float) self::DEFAULT_MIN_COHORT_SIZE);
    }

    private function autoCalibrateEnabled(): bool
    {
        return (bool) $this->configFloat('auto_calibrate', self::DEFAULT_AUTO_CALIBRATE ? 1.0 : 0.0);
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
