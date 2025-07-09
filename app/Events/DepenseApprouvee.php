<?php

namespace App\Events;

use App\Models\ESBTPDepense;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepenseApprouvee
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $depense;
    public $approbateur;

    /**
     * Create a new event instance.
     */
    public function __construct(ESBTPDepense $depense, User $approbateur)
    {
        $this->depense = $depense;
        $this->approbateur = $approbateur;
    }
}
