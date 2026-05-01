<?php

namespace App\Domain\Analytics;

use App\Models\AnalyticsPrediction;
use App\Models\ESBTPPaiement;
use Illuminate\Support\Carbon;

/**
 * Évalue la précision a posteriori des prédictions Analytics. Compare la
 * valeur prédite avec la valeur réelle observée et calcule un score [0,1].
 *
 * Le label retourné est en mots compréhensibles par un comptable lambda :
 * - Excellente (>= 0.85)
 * - Bonne (>= 0.70)
 * - À surveiller (< 0.70)
 *
 * Pure compute sauf accès Models. Idempotent : ne retraite pas une prédiction
 * déjà évaluée (actual_value remplie).
 */
class AccuracyEvaluator
{
    public const LABEL_EXCELLENT = 'excellente';
    public const LABEL_GOOD = 'bonne';
    public const LABEL_WATCH = 'a_surveiller';

    public const THRESHOLD_EXCELLENT = 0.85;
    public const THRESHOLD_GOOD = 0.70;

    /**
     * Score normalisé [0,1] : 1 = prédiction parfaite, 0 = écart maximal.
     * Formule : 1 - |predicted - actual| / max(|predicted|, |actual|).
     * Retourne 1.0 si predicted = actual = 0 (pas d'écart à mesurer).
     */
    public static function score(float $predicted, float $actual): float
    {
        $denominator = max(abs($predicted), abs($actual));
        if ($denominator < 0.01) {
            return 1.0;
        }
        $error = abs($predicted - $actual) / $denominator;
        return max(0.0, min(1.0, 1.0 - $error));
    }

    public static function labelForScore(float $score): string
    {
        return match (true) {
            $score >= self::THRESHOLD_EXCELLENT => self::LABEL_EXCELLENT,
            $score >= self::THRESHOLD_GOOD => self::LABEL_GOOD,
            default => self::LABEL_WATCH,
        };
    }

    /**
     * Évalue toutes les prédictions cash_flow dont target_date est passée
     * et actual_value pas encore remplie. Calcule actual via SUM des
     * paiements validés du mois cible.
     *
     * @return array{evaluated: int, skipped: int}
     */
    public function evaluatePendingCashFlow(): array
    {
        $pending = AnalyticsPrediction::query()
            ->where('predictor', 'cash_flow')
            ->whereNotNull('target_date')
            ->whereNotNull('predicted_value')
            ->whereNull('actual_value')
            ->where('target_date', '<', Carbon::now()->startOfMonth())
            ->get();

        $evaluated = 0;
        $skipped = 0;

        foreach ($pending as $prediction) {
            $actual = $this->computeActualCashFlow($prediction);
            if ($actual === null) {
                $skipped++;
                continue;
            }

            $score = self::score((float) $prediction->predicted_value, $actual);
            $prediction->update([
                'actual_value' => $actual,
                'accuracy_score' => $score,
            ]);
            $evaluated++;
        }

        return ['evaluated' => $evaluated, 'skipped' => $skipped];
    }

    /**
     * Précision moyenne sur les N dernières prédictions évaluées d'un predictor.
     */
    public function averageAccuracy(string $predictor, int $lastN = 6): ?float
    {
        $scores = AnalyticsPrediction::query()
            ->where('predictor', $predictor)
            ->whereNotNull('accuracy_score')
            ->orderByDesc('target_date')
            ->limit($lastN)
            ->pluck('accuracy_score')
            ->map(fn ($v) => (float) $v)
            ->all();

        if (empty($scores)) {
            return null;
        }

        return array_sum($scores) / count($scores);
    }

    private function computeActualCashFlow(AnalyticsPrediction $prediction): ?float
    {
        $targetDate = Carbon::parse($prediction->target_date);
        $start = $targetDate->copy()->startOfMonth();
        $end = $targetDate->copy()->endOfMonth();

        $context = $prediction->context_json ?? [];

        $query = ESBTPPaiement::query()
            ->where('status', 'validé')
            ->whereNull('deleted_at')
            ->whereBetween('date_paiement', [$start, $end]);

        if (!empty($context['anneeId'])) {
            $query->whereHas('inscription', fn ($q) => $q->where('annee_universitaire_id', $context['anneeId']));
        }
        if (!empty($context['filiereId'])) {
            $query->whereHas('inscription.classe', fn ($q) => $q->where('filiere_id', $context['filiereId']));
        }
        if (!empty($context['classeId'])) {
            $query->whereHas('inscription', fn ($q) => $q->where('classe_id', $context['classeId']));
        }

        return (float) $query->sum('montant');
    }
}
