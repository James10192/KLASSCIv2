<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\DTO;

use InvalidArgumentException;

final readonly class BulletinPreparationResult
{
    /**
     * @param  list<array{code: string, message: string, severity: string}>  $blockingIssues
     * @param  list<array{code: string, message: string, severity: string}>  $warnings
     */
    public function __construct(
        public string $system,
        public int $studentId,
        public int $classId,
        public int $academicYearId,
        public string $period,
        public bool $ready,
        public int $coveragePct,
        public array $blockingIssues = [],
        public array $warnings = [],
        public array $evidence = [],
    ) {
        if (! in_array($system, ['BTS', 'LMD'], true)) {
            throw new InvalidArgumentException('Le système de bulletin est invalide.');
        }

        if ($studentId <= 0 || $classId <= 0 || $academicYearId <= 0 || trim($period) === '') {
            throw new InvalidArgumentException('Le contexte de préparation du bulletin est invalide.');
        }

        if ($coveragePct < 0 || $coveragePct > 100) {
            throw new InvalidArgumentException('La couverture de préparation du bulletin est invalide.');
        }
    }

    public function canGenerate(bool $hasIncompleteOverride): bool
    {
        return $this->ready || ($hasIncompleteOverride && $this->blockingIssues !== []);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'system' => $this->system,
            'student_id' => $this->studentId,
            'class_id' => $this->classId,
            'academic_year_id' => $this->academicYearId,
            'period' => $this->period,
            'ready' => $this->ready,
            'coverage_pct' => $this->coveragePct,
            'blocking_issues' => $this->blockingIssues,
            'warnings' => $this->warnings,
            'evidence' => $this->evidence,
        ];
    }
}
