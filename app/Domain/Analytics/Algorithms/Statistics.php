<?php

namespace App\Domain\Analytics\Algorithms;

/**
 * Pure-math statistical helpers — déterministes, testables iso (TDD-friendly).
 * Aucune dépendance Eloquent ni Laravel. Inputs = arrays de floats.
 */
final class Statistics
{
    public static function mean(array $values): float
    {
        $count = count($values);
        return $count === 0 ? 0.0 : array_sum($values) / $count;
    }

    public static function median(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        sort($values);
        $middle = (int) floor($count / 2);

        return $count % 2 === 0
            ? ($values[$middle - 1] + $values[$middle]) / 2
            : (float) $values[$middle];
    }

    public static function standardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean = self::mean($values);
        $variance = array_sum(array_map(fn ($x) => ($x - $mean) ** 2, $values)) / $count;

        return sqrt($variance);
    }

    /**
     * Z-score d'une valeur par rapport à un échantillon.
     * Retourne 0 si l'échantillon est trop petit ou de variance nulle.
     */
    public static function zScore(float $value, array $sample): float
    {
        $stdDev = self::standardDeviation($sample);
        if ($stdDev <= 0.0) {
            return 0.0;
        }

        return ($value - self::mean($sample)) / $stdDev;
    }
}
