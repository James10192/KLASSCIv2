<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\StudentMetricContext;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\GradeCompletionMetricService;

class GradeCompletionMetricServiceScopeTest extends AcademicPilotageDatabaseTestCase
{
    public function test_uses_only_validated_evaluation_sheets_and_distinguishes_uncomputed_from_naq(): void
    {
        $context = new StudentMetricContext(101, 10, 20, 'LMD', 'semestre1');
        $valid = $this->createGradeSheet(['academic_system' => 'LMD', 'semester' => 'semestre1', 'status' => GradeSheetStatus::VALIDATED->value, 'source' => 'evaluation']);
        $this->createGradeSheet(['academic_system' => 'LMD', 'semester' => 'semestre1', 'status' => GradeSheetStatus::CONTROLLED->value, 'source' => 'evaluation']);
        $this->createGradeSheet(['academic_system' => 'LMD', 'semester' => 'semestre1', 'status' => GradeSheetStatus::VALIDATED->value, 'source' => 'manual']);

        $service = new GradeCompletionMetricService(new AcademicPeriodNormalizer);
        $uncomputed = $service->forStudent($context);
        $this->assertSame(0, $uncomputed->confidencePct);
        $this->assertSame(1, $uncomputed->evidence['missing_entries']);

        $this->createEntry($valid, 101, ['status' => GradeSheetEntryStatus::NOT_APPLICABLE->value]);
        $naq = $service->forStudent($context);
        $this->assertSame(0, $naq->confidencePct);
        $this->assertSame(0, $naq->evidence['applicable_entries']);
    }
}
