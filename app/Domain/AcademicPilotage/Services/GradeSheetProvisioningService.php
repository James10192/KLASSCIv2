<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\GradeSheetProvisionResult;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class GradeSheetProvisioningService
{
    public function __construct(
        private readonly GradeSheetFactory $factory,
        private readonly ExpectedGradeSheetEntrySynchronizer $entrySynchronizer,
    ) {}

    public function provision(
        ESBTPEvaluation $evaluation,
        GradeSheetEntryMode $entryMode,
        User $actor,
        ?string $expectedAt = null,
    ): GradeSheetProvisionResult {
        return DB::transaction(function () use (
            $evaluation,
            $entryMode,
            $actor,
            $expectedAt,
        ): GradeSheetProvisionResult {
            $sheet = $this->factory->createFromEvaluation(
                $evaluation,
                $entryMode,
                $actor,
                $expectedAt,
            );
            $created = $sheet->wasRecentlyCreated;
            $sync = $created
                ? $this->entrySynchronizer->sync($sheet, $sheet->lock_version, $actor)
                : null;

            return new GradeSheetProvisionResult($sheet->refresh(), $sync, $created);
        });
    }
}
