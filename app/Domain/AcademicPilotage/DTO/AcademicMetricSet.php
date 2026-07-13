<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\DTO;

use InvalidArgumentException;

final readonly class AcademicMetricSet
{
    /** @var array<string, AcademicMetricValue> */
    public array $metrics;

    /** @param list<AcademicMetricValue> $metrics */
    public function __construct(array $metrics)
    {
        $indexed = [];

        foreach ($metrics as $metric) {
            if (! $metric instanceof AcademicMetricValue) {
                throw new InvalidArgumentException('Chaque métrique doit être une instance de AcademicMetricValue.');
            }

            if (isset($indexed[$metric->key])) {
                throw new InvalidArgumentException("La métrique {$metric->key} est dupliquée.");
            }

            $indexed[$metric->key] = $metric;
        }

        $this->metrics = $indexed;
    }

    public function get(string $key): ?AcademicMetricValue
    {
        return $this->metrics[$key] ?? null;
    }

    /** @return array<string, array> */
    public function toArray(): array
    {
        return array_map(
            static fn (AcademicMetricValue $metric): array => $metric->toArray(),
            $this->metrics,
        );
    }
}
