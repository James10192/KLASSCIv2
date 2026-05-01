<?php

namespace App\Domain\Analytics\Algorithms;

/**
 * Scoring logistique sur features pondérées normalisées. Pure math, déterministe,
 * O(n). Sortie ∈ [0, 1] interprétable comme probabilité de défaut.
 */
final class LogisticScoring
{
    /**
     * Sigmoid σ(x) = 1 / (1 + e^-x). Saturée hors [-35, 35] pour éviter overflow.
     */
    public static function sigmoid(float $x): float
    {
        if ($x > 35.0) {
            return 1.0;
        }
        if ($x < -35.0) {
            return 0.0;
        }
        return 1.0 / (1.0 + exp(-$x));
    }

    /**
     * Score = σ(Σ w_i × x_i + bias). Features absentes du tableau de poids
     * contribuent zéro (poids implicite = 0).
     *
     * @param  array<string, float>  $features  feature_name => valeur normalisée [0,1]
     * @param  array<string, float>  $weights   feature_name => poids signé
     */
    public static function score(array $features, array $weights, float $bias = 0.0): float
    {
        $z = $bias;
        foreach ($features as $name => $value) {
            $w = $weights[$name] ?? 0.0;
            $z += $w * (float) $value;
        }
        return self::sigmoid($z);
    }

    /**
     * Classifie un score [0,1] en 3 niveaux. Seuils alignés sur convention
     * "1/3 critique, 1/3 surveillance, 1/3 sain".
     */
    public static function riskLabel(float $score, float $highThreshold = 0.66, float $mediumThreshold = 0.33): string
    {
        return match (true) {
            $score >= $highThreshold => 'haut',
            $score >= $mediumThreshold => 'moyen',
            default => 'bas',
        };
    }
}
