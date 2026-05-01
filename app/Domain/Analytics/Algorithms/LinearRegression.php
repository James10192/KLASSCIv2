<?php

namespace App\Domain\Analytics\Algorithms;

/**
 * Régression linéaire simple (méthode des moindres carrés). Pure math,
 * déterministe, O(n). Pente positive = tendance croissante.
 */
final class LinearRegression
{
    /**
     * Ajuste y = slope * x + intercept (x indexés 1..n).
     *
     * @param  array<int, float>  $values
     * @return array{slope: float, intercept: float}
     */
    public static function fit(array $values): array
    {
        $n = count($values);
        if ($n < 2) {
            return ['slope' => 0.0, 'intercept' => $n === 1 ? (float) $values[0] : 0.0];
        }

        $sumX = $sumY = $sumXY = $sumXX = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = (float) $values[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
        }

        $denominator = $n * $sumXX - $sumX * $sumX;
        $slope = $denominator == 0.0 ? 0.0 : ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        return ['slope' => $slope, 'intercept' => $intercept];
    }

    public static function predict(array $fit, int $x): float
    {
        return $fit['slope'] * $x + $fit['intercept'];
    }
}
