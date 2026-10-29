<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Models\ESBTPAttendance;

final class ESBTPAttendanceAcademicPilotageObserver
{
    public function __construct(
        private readonly AcademicMetricSnapshotInvalidationService $invalidation,
    ) {}

    public function saved(ESBTPAttendance $attendance): void
    {
        $this->invalidation->fromAttendance($attendance);
    }

    public function deleted(ESBTPAttendance $attendance): void
    {
        $this->invalidation->fromAttendance($attendance);
    }

    public function restored(ESBTPAttendance $attendance): void
    {
        $this->invalidation->fromAttendance($attendance);
    }
}
