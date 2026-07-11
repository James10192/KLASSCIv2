<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\GradeSheetNoteMutationGuard;
use App\Models\ESBTPNote;

class ESBTPNoteAcademicPilotageObserver
{
    public function __construct(
        private readonly GradeSheetNoteMutationGuard $guard,
    ) {}

    public function updating(ESBTPNote $note): void
    {
        $this->guard->assertMutable($note);
    }

    public function creating(ESBTPNote $note): void
    {
        $this->guard->assertMutable($note);
    }

    public function deleting(ESBTPNote $note): void
    {
        $this->guard->assertMutable($note);
    }

    public function restoring(ESBTPNote $note): void
    {
        $this->guard->assertMutable($note);
    }
}
