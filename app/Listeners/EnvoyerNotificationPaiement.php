<?php

namespace App\Listeners;

use App\Domain\Notifications\Notifiers\PaiementNotifier;
use App\Events\PaiementRecu;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class EnvoyerNotificationPaiement implements ShouldQueue
{
    use InteractsWithQueue;

    protected PaiementNotifier $paiementNotifier;

    /**
     * Create the event listener.
     */
    public function __construct(PaiementNotifier $paiementNotifier)
    {
        $this->paiementNotifier = $paiementNotifier;
    }

    /**
     * Handle the event.
     */
    public function handle(PaiementRecu $event): void
    {
        try {
            Log::info("Envoi notification paiement reçu ID: {$event->paiement->id}");

            // Phase 8b strangler fig via PaiementNotifier
            $resultat = $this->paiementNotifier->paiementRecu($event->paiement);

            if ($resultat['success']) {
                Log::info("Notification paiement envoyée avec succès", [
                    'paiement_id' => $event->paiement->id,
                    'etudiant_id' => $event->paiement->etudiant_id
                ]);
            } else {
                Log::warning("Échec envoi notification paiement", [
                    'paiement_id' => $event->paiement->id,
                    'error' => $resultat['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de notification paiement", [
                'paiement_id' => $event->paiement->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaiementRecu $event, \Throwable $exception): void
    {
        Log::error("Listener EnvoyerNotificationPaiement failed", [
            'paiement_id' => $event->paiement->id,
            'error' => $exception->getMessage()
        ]);
    }
}
