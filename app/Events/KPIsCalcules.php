<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KPIsCalcules implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $kpis;
    public $periode;
    public $anneeUniversitaireId;
    public $dateCalcul;

    /**
     * Create a new event instance.
     */
    public function __construct(array $kpis, string $periode, ?int $anneeUniversitaireId = null)
    {
        $this->kpis = $kpis;
        $this->periode = $periode;
        $this->anneeUniversitaireId = $anneeUniversitaireId;
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
        return 'kpis.calcules';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'kpis' => $this->kpis,
            'periode' => $this->periode,
            'annee_universitaire_id' => $this->anneeUniversitaireId,
            'total_recettes' => $this->kpis['total_recettes'] ?? 0,
            'total_depenses' => $this->kpis['total_depenses'] ?? 0,
            'resultat_net' => $this->kpis['resultat_net'] ?? 0,
            'taux_recouvrement' => $this->kpis['taux_recouvrement'] ?? 0,
            'nombre_paiements' => $this->kpis['nombre_paiements'] ?? 0,
            'date_calcul' => $this->dateCalcul->toIso8601String(),
            'timestamp' => now()->toIso8601String()
        ];
    }
}
