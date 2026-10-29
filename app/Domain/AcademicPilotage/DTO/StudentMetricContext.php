<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\DTO;

use InvalidArgumentException;

final readonly class StudentMetricContext
{
    public function __construct(
        public int $studentId,
        public int $classId,
        public int $academicYearId,
        public string $academicSystem,
        public string $period,
    ) {
        if ($studentId <= 0 || $classId <= 0 || $academicYearId <= 0) {
            throw new InvalidArgumentException('Les identifiants du contexte académique doivent être strictement positifs.');
        }

        if ($academicSystem === '' || $academicSystem !== trim($academicSystem)) {
            throw new InvalidArgumentException('Le système académique est obligatoire.');
        }

        if ($period === '' || $period !== trim($period)) {
            throw new InvalidArgumentException('La période académique est obligatoire.');
        }
    }

    public function toArray(): array
    {
        return [
            'student_id' => $this->studentId,
            'class_id' => $this->classId,
            'academic_year_id' => $this->academicYearId,
            'academic_system' => $this->academicSystem,
            'period' => $this->period,
        ];
    }
}
