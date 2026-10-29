<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\DTO;

use InvalidArgumentException;

final readonly class AcademicHealthResult
{
    private const LEVELS = ['healthy', 'watch', 'at_risk', 'critical', 'insufficient_data'];

    public function __construct(
        public ?float $score,
        public int $coveragePct,
        public int $confidencePct,
        public string $level,
        public AcademicMetricSet $metrics,
        public array $factors,
        public array $explanations,
    ) {
        if ($score !== null && (! is_finite($score) || $score < 0 || $score > 100)) {
            throw new InvalidArgumentException('Le score académique doit être compris entre 0 et 100.');
        }

        if ($coveragePct < 0 || $coveragePct > 100 || $confidencePct < 0 || $confidencePct > 100) {
            throw new InvalidArgumentException('La couverture et la confiance doivent être comprises entre 0 et 100.');
        }

        if ($confidencePct > $coveragePct) {
            throw new InvalidArgumentException('La confiance ne peut pas dépasser la couverture.');
        }

        if (! in_array($level, self::LEVELS, true)) {
            throw new InvalidArgumentException('Le niveau de santé académique est invalide.');
        }

        if ($level === 'insufficient_data' xor $score === null) {
            throw new InvalidArgumentException('Un score absent doit correspondre au niveau insufficient_data.');
        }

        if (
            $explanations === []
            || ! array_is_list($explanations)
            || array_filter($explanations, static fn ($value): bool => is_string($value) && trim($value) !== '') !== $explanations
        ) {
            throw new InvalidArgumentException('Le résultat doit fournir des explications textuelles.');
        }

        foreach ($factors as $key => $factor) {
            if (! is_string($key) || ! is_array($factor)) {
                throw new InvalidArgumentException('Les facteurs du résultat académique sont invalides.');
            }
        }
    }

    public function hasSufficientData(): bool
    {
        return $this->score !== null;
    }

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'coverage_pct' => $this->coveragePct,
            'confidence_pct' => $this->confidencePct,
            'level' => $this->level,
            'metrics' => $this->metrics->toArray(),
            'factors' => $this->factors,
            'explanations' => $this->explanations,
        ];
    }
}
