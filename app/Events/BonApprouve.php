<?php

namespace App\Events;

use App\Models\ESBTPBonSortie;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BonApprouve implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bonSortie;
    public $approbateur;

    /**
     * Create a new event instance.
     */
    public function __construct(ESBTPBonSortie $bonSortie, User $approbateur)
    {
        $this->bonSortie = $bonSortie;
        $this->approbateur = $approbateur;
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
        return 'bon.approuve';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'bon_id' => $this->bonSortie->id,
            'numero_bon' => $this->bonSortie->numero_bon,
            'montant_total' => $this->bonSortie->montant_total,
            'demandeur' => $this->bonSortie->demandeur->name ?? 'Inconnu',
            'approbateur' => $this->approbateur->name,
            'timestamp' => now()->toIso8601String()
        ];
    }
}
