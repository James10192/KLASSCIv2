<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;
use PHPUnit\Framework\TestCase;

class BulletinPreparationResultTest extends TestCase
{
    public function test_ready_result_can_generate_without_override(): void
    {
        $result = new BulletinPreparationResult(
            system: 'BTS',
            studentId: 1,
            classId: 2,
            academicYearId: 3,
            period: 'semestre1',
            ready: true,
            coveragePct: 100,
        );

        $this->assertTrue($result->canGenerate(false));
        $this->assertTrue($result->toArray()['ready']);
    }

    public function test_blocked_result_requires_override(): void
    {
        $result = new BulletinPreparationResult(
            system: 'LMD',
            studentId: 1,
            classId: 2,
            academicYearId: 3,
            period: 'semestre1',
            ready: false,
            coveragePct: 60,
            blockingIssues: [[
                'code' => 'missing_grade_entries',
                'message' => 'Des notes sont manquantes.',
                'severity' => 'blocking',
            ]],
        );

        $this->assertFalse($result->canGenerate(false));
        $this->assertTrue($result->canGenerate(true));
    }
}
