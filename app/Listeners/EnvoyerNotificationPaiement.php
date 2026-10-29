<?php

namespace App\Listeners;

use App\Events\PaiementRecu;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class EnvoyerNotificationPaiement implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(PaiementRecu $event): void
    {
        try {
            Log::info("Envoi notification paiement reçu ID: {$event->paiement->id}");

            // Envoyer la notification de confirmation
            $resultat = $this->notificationService->notifierPaiementRecu($event->paiement);

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
