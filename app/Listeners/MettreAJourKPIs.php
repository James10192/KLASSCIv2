<?php

namespace App\Listeners;

use App\Events\PaiementRecu;
use App\Jobs\CalculerKPIsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class MettreAJourKPIs implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaiementRecu $event): void
    {
        try {
            Log::info("Mise à jour des KPIs suite au paiement ID: {$event->paiement->id}");

            // Lancer le calcul des KPIs en asynchrone
            CalculerKPIsJob::dispatch('jour', $event->paiement->annee_universitaire_id)
                ->delay(now()->addMinutes(2)); // Délai pour éviter la surcharge

            // KPIs mensuels si c'est le premier paiement du mois
            if ($this->estPremierPaiementDuMois($event->paiement)) {
                CalculerKPIsJob::dispatch('mois', $event->paiement->annee_universitaire_id)
                    ->delay(now()->addMinutes(5));
            }

        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour des KPIs", [
                'paiement_id' => $event->paiement->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifie si c'est le premier paiement du mois
     */
    private function estPremierPaiementDuMois($paiement)
    {
        return \App\Models\ESBTPPaiement::whereMonth('date_paiement', $paiement->date_paiement->month)
            ->whereYear('date_paiement', $paiement->date_paiement->year)
            ->count() === 1;
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaiementRecu $event, \Throwable $exception): void
    {
        Log::error("Listener MettreAJourKPIs failed", [
            'paiement_id' => $event->paiement->id,
            'error' => $exception->getMessage()
        ]);
    }
}
