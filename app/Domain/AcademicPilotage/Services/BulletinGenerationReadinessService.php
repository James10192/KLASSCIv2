<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;
use App\Models\User;

final class BulletinGenerationReadinessService
{
    public function __construct(
        private readonly BulletinPreparationResolver $preparations,
        private readonly IncompleteBulletinGenerationGuard $guard,
    ) {}

    public function assertReady(
        string $academicSystem,
        int $studentId,
        int $classId,
        int $academicYearId,
        string $period,
        ?User $actor,
        ?string $overrideReason,
    ): void {
        $preparation = $this->inspect(
            $academicSystem,
            $studentId,
            $classId,
            $academicYearId,
            $period
        );

        $this->guard->assertCanGenerate(
            $preparation,
            $actor?->can('bulletins.generate_incomplete') ?? false,
            $overrideReason,
            $actor?->id,
        );
    }

    public function inspect(
        string $academicSystem,
        int $studentId,
        int $classId,
        int $academicYearId,
        string $period,
    ): BulletinPreparationResult {
        return $this->preparations->forSystem($academicSystem)->prepare(
            $studentId,
            $classId,
            $academicYearId,
            $period
        );
    }
}
