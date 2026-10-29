<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\DTO;

use InvalidArgumentException;

final readonly class ClassAcademicHealthResult
{
    private const LEVELS = ['healthy', 'watch', 'at_risk', 'critical', 'insufficient_data'];

    /** @param array<int, AcademicHealthResult> $studentResults */
    public function __construct(
        public int $classId,
        public ?float $score,
        public int $coveragePct,
        public int $confidencePct,
        public string $level,
        public int $studentCount,
        public int $scoredStudentCount,
        public ?float $operationalScore,
        public int $operationalCoveragePct,
        public array $operationalMetrics = [],
        public array $reasons = [],
        public array $studentResults = [],
    ) {
        if ($classId <= 0 || $studentCount < 0 || $scoredStudentCount < 0 || $scoredStudentCount > $studentCount) {
            throw new InvalidArgumentException('Le contexte de santé académique de la classe est invalide.');
        }

        if ($score !== null && (! is_finite($score) || $score < 0 || $score > 100)) {
            throw new InvalidArgumentException('Le score de classe doit être compris entre 0 et 100.');
        }

        if ($coveragePct < 0 || $coveragePct > 100 || $confidencePct < 0 || $confidencePct > $coveragePct) {
            throw new InvalidArgumentException('La couverture ou la confiance de classe est invalide.');
        }

        if ($operationalScore !== null && (! is_finite($operationalScore) || $operationalScore < 0 || $operationalScore > 100)) {
            throw new InvalidArgumentException('Le score operationnel de classe est invalide.');
        }

        if ($operationalCoveragePct < 0 || $operationalCoveragePct > 100) {
            throw new InvalidArgumentException('La couverture operationnelle de classe est invalide.');
        }

        if (! in_array($level, self::LEVELS, true)) {
            throw new InvalidArgumentException('Le niveau de santé académique de classe est invalide.');
        }

        if ($level === 'insufficient_data' xor $score === null) {
            throw new InvalidArgumentException('Un score de classe absent doit correspondre au niveau insufficient_data.');
        }

        foreach ($studentResults as $studentId => $result) {
            if (! is_int($studentId) || $studentId <= 0 || ! $result instanceof AcademicHealthResult) {
                throw new InvalidArgumentException('Les résultats étudiants de la classe sont invalides.');
            }
        }
    }

    public function toArray(): array
    {
        return [
            'class_id' => $this->classId,
            'score' => $this->score,
            'coverage_pct' => $this->coveragePct,
            'confidence_pct' => $this->confidencePct,
            'level' => $this->level,
            'student_count' => $this->studentCount,
            'scored_student_count' => $this->scoredStudentCount,
            'operational_score' => $this->operationalScore,
            'operational_coverage_pct' => $this->operationalCoveragePct,
            'operational_metrics' => $this->operationalMetrics,
            'reasons' => $this->reasons,
            'student_results' => array_map(
                static fn (AcademicHealthResult $result): array => $result->toArray(),
                $this->studentResults,
            ),
        ];
    }
}
