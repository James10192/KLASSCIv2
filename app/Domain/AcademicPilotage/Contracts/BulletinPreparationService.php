<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Contracts;

use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;

interface BulletinPreparationService
{
    public function prepare(
        int $studentId,
        int $classId,
        int $academicYearId,
        string $period,
    ): BulletinPreparationResult;
}
