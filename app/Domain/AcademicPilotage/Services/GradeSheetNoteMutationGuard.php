<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Models\ESBTPNote;

final class GradeSheetNoteMutationGuard
{
    public function assertMutable(ESBTPNote $note): void
    {
        if ($note->evaluation_id === null) {
            return;
        }

        $this->assertEvaluationMutable((int) $note->evaluation_id);
    }

    public function assertEvaluationMutable(int $evaluationId, bool $lockForUpdate = false): void
    {
        $query = GradeSheet::query()
            ->where('evaluation_id', $evaluationId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $sheet = $query->first();

        if ($sheet?->status === GradeSheetStatus::VALIDATED) {
            throw AcademicPilotageException::validatedNoteLocked((int) $sheet->id);
        }
    }
}
