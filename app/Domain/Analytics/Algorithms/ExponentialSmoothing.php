<?php

namespace App\Domain\Analytics\Algorithms;

/**
 * Lissage exponentiel simple + composante saisonnière additive. Conçu pour
 * forecasting cash-flow scolaire avec saisonnalité annuelle 12 mois
 * (rentrée septembre, début S2 février, examens juin).
 *
 * Algorithme : niveau lissé exponentiellement (single-pass, alpha=0.3) +
 * delta saisonnier additive (moyenne du mois cible vs moyenne globale).
 * Performance O(n), déterministe, testable iso.
 */
final class ExponentialSmoothing
{
    private const ALPHA = 0.3;

    /**
     * @param  array<int, float>  $values
     */
    public static function smooth(array $values, float $alpha = self::ALPHA): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $level = (float) $values[0];
        $count = count($values);
        for ($i = 1; $i < $count; $i++) {
            $level = $alpha * (float) $values[$i] + (1 - $alpha) * $level;
        }

        return $level;
    }

    /**
     * Forecast saisonnier additive : niveau lissé + delta du mois cible
     * vs moyenne globale.
     *
     * @param  array<int, array{month: int, value: float}>  $historicalSeries
     */
    public static function forecastSeasonal(array $historicalSeries, int $targetMonth): float
    {
        if (empty($historicalSeries)) {
            return 0.0;
        }

        $values = array_column($historicalSeries, 'value');
        $level = self::smooth($values);

        $globalMean = Statistics::mean($values);
        $sameMonthValues = array_column(
            array_filter($historicalSeries, fn ($p) => $p['month'] === $targetMonth),
            'value'
        );

        $seasonalDelta = empty($sameMonthValues)
            ? 0.0
            : Statistics::mean($sameMonthValues) - $globalMean;

        return max(0.0, $level + $seasonalDelta);
    }
}
