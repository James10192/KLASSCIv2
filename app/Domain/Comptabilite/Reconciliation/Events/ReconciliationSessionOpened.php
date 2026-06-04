<?php

namespace App\Domain\Comptabilite\Reconciliation\Events;

use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReconciliationSessionOpened
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReconciliationSession $session) {}
}
