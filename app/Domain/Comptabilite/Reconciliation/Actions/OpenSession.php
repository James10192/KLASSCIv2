<?php

namespace App\Domain\Comptabilite\Reconciliation\Actions;

use App\Domain\Comptabilite\Reconciliation\Events\ReconciliationSessionOpened;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Domain\Comptabilite\Reconciliation\Services\ReconciliationSessionService;
use App\Models\User;

class OpenSession
{
    public function __construct(private ReconciliationSessionService $service) {}

    public function execute(User $user, ?string $frequency = null, ?string $startDate = null): ReconciliationSession
    {
        $session = $this->service->open($user, $frequency, $startDate);
        event(new ReconciliationSessionOpened($session));
        return $session;
    }
}
