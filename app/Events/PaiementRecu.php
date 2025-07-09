<?php

namespace App\Events;

use App\Models\ESBTPPaiement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaiementRecu
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $paiement;

    /**
     * Create a new event instance.
     */
    public function __construct(ESBTPPaiement $paiement)
    {
        $this->paiement = $paiement;
    }
}
