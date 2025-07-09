<?php

namespace App\Listeners;

use App\Events\BonApprouve;
use App\Services\NotificationService;
use App\Models\User;
use App\Notifications\ESBTPNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifierBonApprouve implements ShouldQueue
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
    public function handle(BonApprouve $event): void
    {
        try {
            Log::info("Envoi notification bon approuvé ID: {$event->bonSortie->id}");

            // Notifier le demandeur
            if ($event->bonSortie->demandeur) {
                $this->notificationService->createNotification(
                    $event->bonSortie->demandeur,
                    'Bon de sortie approuvé',
                    "Votre bon de sortie n° {$event->bonSortie->numero_bon} d'un montant de {$event->bonSortie->montant_total} FCFA a été approuvé par {$event->approbateur->name}.",
                    'success',
                    route('esbtp.comptabilite.bons-sortie.show', $event->bonSortie->id),
                    $event->approbateur
                );
            }

            // Notifier les gestionnaires de comptabilité
            $comptables = User::permission('comptabilite.bons.manage')->get();
            foreach ($comptables as $comptable) {
                if ($comptable->id !== $event->approbateur->id) {
                    $this->notificationService->createNotification(
                        $comptable,
                        'Bon de sortie approuvé',
                        "Le bon de sortie n° {$event->bonSortie->numero_bon} a été approuvé et peut maintenant être traité.",
                        'info',
                        route('esbtp.comptabilite.bons-sortie.show', $event->bonSortie->id),
                        $event->approbateur
                    );
                }
            }

            Log::info("Notifications bon approuvé envoyées avec succès", [
                'bon_id' => $event->bonSortie->id,
                'approbateur' => $event->approbateur->name
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi des notifications bon approuvé", [
                'bon_id' => $event->bonSortie->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(BonApprouve $event, \Throwable $exception): void
    {
        Log::error("Listener NotifierBonApprouve failed", [
            'bon_id' => $event->bonSortie->id,
            'error' => $exception->getMessage()
        ]);
    }
}
