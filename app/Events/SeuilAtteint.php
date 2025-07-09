<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SeuilAtteint implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $typeKPI;
    public $valeurActuelle;
    public $seuil;
    public $message;
    public $niveau; // 'critique', 'warning', 'info'
    public $dateCalcul;

    /**
     * Create a new event instance.
     */
    public function __construct(string $typeKPI, float $valeurActuelle, float $seuil, string $message, string $niveau = 'warning')
    {
        $this->typeKPI = $typeKPI;
        $this->valeurActuelle = $valeurActuelle;
        $this->seuil = $seuil;
        $this->message = $message;
        $this->niveau = $niveau;
        $this->dateCalcul = now();
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
        return 'seuil.atteint';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'type_kpi' => $this->typeKPI,
            'valeur_actuelle' => $this->valeurActuelle,
            'seuil' => $this->seuil,
            'message' => $this->message,
            'niveau' => $this->niveau,
            'pourcentage' => round(($this->valeurActuelle / $this->seuil) * 100, 2),
            'timestamp' => $this->dateCalcul->toIso8601String()
        ];
    }
}
