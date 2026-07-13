<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Models\ESBTPInscription;

final class ESBTPInscriptionAcademicPilotageObserver
{
    public function __construct(private readonly AcademicMetricSnapshotInvalidationService $invalidation) {}

    public function saved(ESBTPInscription $enrollment): void
    {
        $this->invalidation->fromEnrollment($enrollment);
    }

    public function deleted(ESBTPInscription $enrollment): void
    {
        $this->invalidation->fromEnrollment($enrollment);
    }

    public function restored(ESBTPInscription $enrollment): void
    {
        $this->invalidation->fromEnrollment($enrollment);
    }
}
