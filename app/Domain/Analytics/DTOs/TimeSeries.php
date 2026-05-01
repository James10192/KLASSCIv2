<?php

namespace App\Domain\Analytics\DTOs;

/**
 * Série temporelle : suite ordonnée de (date, value). Utilisée comme input
 * pour les Algorithms (LinearRegression, ExponentialSmoothing, ZScoreAnomaly).
 */
final class TimeSeries
{
    /**
     * @param  array<int, array{date: \DateTimeInterface, value: float}>  $points
     */
    public function __construct(
        public readonly array $points,
        public readonly string $frequency = 'monthly',
    ) {}

    public function values(): array
    {
        return array_map(fn ($p) => $p['value'], $this->points);
    }

    public function dates(): array
    {
        return array_map(fn ($p) => $p['date'], $this->points);
    }

    public function count(): int
    {
        return count($this->points);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}
