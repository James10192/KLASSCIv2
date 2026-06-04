<?php

namespace App\Domain\Comptabilite\Reconciliation\Actions;

use App\Domain\Comptabilite\Reconciliation\Models\CashCount;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Domain\Comptabilite\Reconciliation\Services\ReconciliationSessionService;
use App\Models\User;

class RecordCashCount
{
    public function __construct(private ReconciliationSessionService $service) {}

    public function execute(
        ReconciliationSession $session,
        User $user,
        string $modePaiement,
        float $montantCompte,
        ?string $notes = null
    ): CashCount {
        return $this->service->recordCashCount($session, $user, $modePaiement, $montantCompte, $notes);
    }
}
