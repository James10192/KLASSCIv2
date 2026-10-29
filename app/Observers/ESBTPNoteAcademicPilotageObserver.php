<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Domain\AcademicPilotage\Services\GradeSheetNoteMutationGuard;
use App\Models\ESBTPNote;

class ESBTPNoteAcademicPilotageObserver
{
    public function __construct(
        private readonly GradeSheetNoteMutationGuard $guard,
        private readonly AcademicMetricSnapshotInvalidationService $invalidation,
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

    public function saved(ESBTPNote $note): void
    {
        $this->invalidation->fromNote($note);
    }

    public function deleted(ESBTPNote $note): void
    {
        $this->invalidation->fromNote($note);
    }

    public function restored(ESBTPNote $note): void
    {
        $this->invalidation->fromNote($note);
    }
}
