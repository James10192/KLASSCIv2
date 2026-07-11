<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Services\GradeSheetNoteMutationGuard;
use App\Models\ESBTPNote;

class GradeSheetNoteMutationGuardTest extends AcademicPilotageDatabaseTestCase
{
    public function test_validated_sheet_locks_its_notes_until_reopened(): void
    {
        $sheet = $this->createGradeSheet([
            'evaluation_id' => 77,
            'status' => GradeSheetStatus::VALIDATED->value,
        ]);
        $note = new ESBTPNote;
        $note->setRawAttributes(['evaluation_id' => 77], true);

        try {
            (new GradeSheetNoteMutationGuard)->assertMutable($note);
            $this->fail('A validated grade sheet must lock its notes.');
        } catch (AcademicPilotageException $exception) {
            $this->assertSame(
                AcademicPilotageException::VALIDATED_NOTE_LOCKED,
                $exception->errorCode,
            );
            $this->assertSame(['grade_sheet_id' => $sheet->id], $exception->details);
        }
    }

    public function test_non_validated_sheet_keeps_note_editable(): void
    {
        $this->createGradeSheet([
            'evaluation_id' => 77,
            'status' => GradeSheetStatus::CONTROLLED->value,
        ]);
        $note = new ESBTPNote;
        $note->setRawAttributes(['evaluation_id' => 77], true);

        (new GradeSheetNoteMutationGuard)->assertMutable($note);

        $this->assertTrue(true);
    }
}
