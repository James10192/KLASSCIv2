<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Models\ESBTPEvaluation;

final class ESBTPEvaluationAcademicPilotageObserver
{
    public function __construct(
        private readonly AcademicMetricSnapshotInvalidationService $invalidation,
    ) {}

    public function saved(ESBTPEvaluation $evaluation): void
    {
        $this->invalidation->fromEvaluation($evaluation);
    }

    public function deleted(ESBTPEvaluation $evaluation): void
    {
        $this->invalidation->fromEvaluation($evaluation);
    }

    public function restored(ESBTPEvaluation $evaluation): void
    {
        $this->invalidation->fromEvaluation($evaluation);
    }
}
