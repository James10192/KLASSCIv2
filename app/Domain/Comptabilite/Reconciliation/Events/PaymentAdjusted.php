<?php

namespace App\Domain\Comptabilite\Reconciliation\Events;

use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Models\ESBTPPaiement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentAdjusted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ESBTPPaiement $paiement,
        public ReconciliationSession $session,
        public array $delta
    ) {}
}
