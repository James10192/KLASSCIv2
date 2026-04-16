<?php

namespace App\Events;

use App\Models\ESBTPRelance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RelanceEnvoyee implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $relance;

    /**
     * Create a new event instance.
     */
    public function __construct(ESBTPRelance $relance)
    {
        $this->relance = $relance;
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
        return 'relance.envoyee';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'relance_id' => $this->relance->id,
            'etudiant_nom' => $this->relance->etudiant->nom_complet ?? 'Inconnu',
            'niveau' => $this->relance->niveau,
            'type' => $this->relance->type,
            'statut' => $this->relance->statut,
            'date_envoi' => $this->relance->date_envoi?->toIso8601String(),
            'timestamp' => now()->toIso8601String()
        ];
    }
}
