<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Contracts;

use App\Domain\AcademicPilotage\DTO\AcademicHealthResult;

interface AcademicCohortHealthProvider
{
    /**
     * @param  list<int>  $studentIds
     * @return array<int, AcademicHealthResult>
     */
    public function healthForCohort(
        int $classId,
        int $academicYearId,
        string $academicSystem,
        string $period,
        array $studentIds,
    ): array;
}
