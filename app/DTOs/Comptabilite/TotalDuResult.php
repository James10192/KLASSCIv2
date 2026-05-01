<?php

namespace App\DTOs\Comptabilite;

final class TotalDuResult
{
    public function __construct(
        public readonly float $totalDue,
        public readonly int $countDue,
    ) {}

    public static function empty(): self
    {
        return new self(totalDue: 0.0, countDue: 0);
    }

    public function toArray(): array
    {
        return ['totalDue' => $this->totalDue, 'countDue' => $this->countDue];
    }
}
