<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Models\ESBTPPlanificationAcademique;

final class ESBTPPlanificationAcademicPilotageObserver
{
    public function __construct(private readonly AcademicMetricSnapshotInvalidationService $invalidation) {}

    public function saved(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
    }

    public function deleted(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
    }

    public function restored(ESBTPPlanificationAcademique $planning): void
    {
        $this->invalidation->fromPlanning($planning);
    }
}
