<?php

namespace App\Observers;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Models\ESBTPLMDBulletin;

final class ESBTPLMDBulletinAcademicPilotageObserver
{
    public function __construct(private readonly AcademicMetricSnapshotInvalidationService $invalidation) {}

    public function saved(ESBTPLMDBulletin $bulletin): void
    {
        $this->invalidation->fromLmdBulletin($bulletin);
    }

    public function deleted(ESBTPLMDBulletin $bulletin): void
    {
        $this->invalidation->fromLmdBulletin($bulletin);
    }

    public function restored(ESBTPLMDBulletin $bulletin): void
    {
        $this->invalidation->fromLmdBulletin($bulletin);
    }
}
