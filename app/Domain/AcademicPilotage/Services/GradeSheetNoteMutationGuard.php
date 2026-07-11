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

        $gradeSheetId = GradeSheet::query()
            ->where('evaluation_id', $note->evaluation_id)
            ->where('status', GradeSheetStatus::VALIDATED->value)
            ->value('id');

        if ($gradeSheetId !== null) {
            throw AcademicPilotageException::validatedNoteLocked((int) $gradeSheetId);
        }
    }
}
