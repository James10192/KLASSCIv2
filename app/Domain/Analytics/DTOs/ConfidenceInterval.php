<?php

namespace App\Domain\Analytics\DTOs;

/**
 * Intervalle de confiance d'une prédiction numérique.
 * Le label texte ("Très fiable" / "Fiable" / "Indicatif") est dérivé de la
 * largeur relative de l'intervalle — comptable lambda lit le mot, pas le %.
 */
final class ConfidenceInterval
{
    public function __construct(
        public readonly float $lower,
        public readonly float $upper,
        public readonly int $percentile = 95,
    ) {}

    /**
     * Label français lambda-friendly basé sur la largeur relative
     * (upper - lower) / max(value, 1).
     */
    public function labelForValue(float $value): string
    {
        if ($value <= 0) {
            return 'indicatif';
        }

        $width = ($this->upper - $this->lower) / max($value, 1.0);

        return match (true) {
            $width <= 0.20 => 'tres_fiable',
            $width <= 0.50 => 'fiable',
            default => 'indicatif',
        };
    }

    public function toArray(): array
    {
        return [
            'lower' => $this->lower,
            'upper' => $this->upper,
            'percentile' => $this->percentile,
        ];
    }
}
