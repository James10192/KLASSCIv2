<?php

namespace App\Events;

use App\Models\ESBTPPaiement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PaiementRecu implements ShouldBroadcast
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

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new PrivateChannel('comptabilite');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'paiement.recu';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->paiement->id,
            'montant' => $this->paiement->montant,
            'etudiant' => $this->paiement->etudiant->nom_complet ?? 'Inconnu',
            'type_paiement' => $this->paiement->type_paiement,
            'date_paiement' => $this->paiement->date_paiement?->toIso8601String(),
            'timestamp' => now()->toIso8601String()
        ];
    }
}
