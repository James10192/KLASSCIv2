<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Domain\Evaluations\EnseignantsDuPlanning;
use App\Models\ESBTPPlanificationAcademique;

final class ESBTPPlanificationAcademicPilotageObserver
{
    public function __construct(
        private readonly AcademicMetricSnapshotInvalidationService $invalidation,
        private readonly EnseignantsDuPlanning $enseignantsDuPlanning,
    ) {}

    public function saved(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
        $this->enseignantsDuPlanning->synchroniserEvaluations($planning);
    }

    public function deleted(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
    }

    public function restored(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
        $this->enseignantsDuPlanning->synchroniserEvaluations($planning);
    }
}
