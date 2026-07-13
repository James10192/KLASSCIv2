<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Services\IncompleteBulletinGenerationGuard;
use Tests\TestCase;

class IncompleteBulletinGenerationGuardTest extends TestCase
{
    public function test_blocks_incomplete_generation_without_permission(): void
    {
        $guard = new IncompleteBulletinGenerationGuard;

        $this->expectException(AcademicPilotageException::class);

        $guard->assertCanGenerate($this->blockedResult(), false);
    }

    public function test_override_requires_reason(): void
    {
        $guard = new IncompleteBulletinGenerationGuard;

        $this->expectException(AcademicPilotageException::class);

        $guard->assertCanGenerate($this->blockedResult(), true, '');
    }

    public function test_override_with_reason_is_allowed(): void
    {
        $guard = new IncompleteBulletinGenerationGuard;

        $guard->assertCanGenerate($this->blockedResult(), true, 'Décision pédagogique validée.', 7);

        $this->assertTrue(true);
    }

    private function blockedResult(): BulletinPreparationResult
    {
        return new BulletinPreparationResult(
            system: 'BTS',
            studentId: 1,
            classId: 2,
            academicYearId: 3,
            period: 'semestre1',
            ready: false,
            coveragePct: 0,
            blockingIssues: [[
                'code' => 'no_grade_data',
                'message' => 'Aucune note disponible.',
                'severity' => 'blocking',
            ]],
        );
    }
}
