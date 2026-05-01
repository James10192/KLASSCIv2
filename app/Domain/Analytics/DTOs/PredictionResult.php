<?php

namespace App\Domain\Analytics\DTOs;

/**
 * Résultat d'un Predictor : valeur ou label, intervalle de confiance, et
 * top 3 raisons textuelles compréhensibles par un comptable lambda.
 */
final class PredictionResult
{
    /**
     * @param  array<int, string>  $explanation  Top raisons, ordre d'importance
     */
    public function __construct(
        public readonly string $predictor,
        public readonly ?float $value,
        public readonly ?string $label,
        public readonly ?ConfidenceInterval $confidenceInterval,
        public readonly string $confidenceLabel,
        public readonly array $explanation,
        public readonly ?\DateTimeInterface $targetDate = null,
    ) {}

    public static function unavailable(string $predictor, string $reason): self
    {
        return new self(
            predictor: $predictor,
            value: null,
            label: 'indisponible',
            confidenceInterval: null,
            confidenceLabel: 'indicatif',
            explanation: [$reason],
        );
    }

    public function toArray(): array
    {
        return [
            'predictor' => $this->predictor,
            'value' => $this->value,
            'label' => $this->label,
            'confidence_interval' => $this->confidenceInterval?->toArray(),
            'confidence_label' => $this->confidenceLabel,
            'explanation' => $this->explanation,
            'target_date' => $this->targetDate?->format('Y-m-d'),
        ];
    }
}
