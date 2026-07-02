<?php

namespace App\Services\Scoring;

class PersonnelScoreResult
{
    public function __construct(
        public readonly string $dimension,
        public readonly string $label,
        public readonly int $score,
        public readonly int $weight,
        public readonly array $metrics = [],
        public readonly array $messages = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'dimension' => $this->dimension,
            'label' => $this->label,
            'score' => $this->score,
            'weight' => $this->weight,
            'metrics' => $this->metrics,
            'messages' => $this->messages,
        ];
    }
}
