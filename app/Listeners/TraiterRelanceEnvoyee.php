<?php

namespace App\Listeners;

use App\Events\RelanceEnvoyee;
use App\Services\NotificationService;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TraiterRelanceEnvoyee implements ShouldQueue
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
    public function handle(RelanceEnvoyee $event): void
    {
        try {
            Log::info("Traitement relance envoyée ID: {$event->relance->id}");

            // Mettre à jour les statistiques de relances
            $this->mettreAJourStatistiques($event->relance);

            // Notifier les responsables selon le niveau de relance
            $this->notifierResponsables($event->relance);

            // Programmer la prochaine relance si nécessaire
            $this->programmerProchaineRelance($event->relance);

            Log::info("Relance traitée avec succès", [
                'relance_id' => $event->relance->id,
                'niveau' => $event->relance->niveau,
                'statut' => $event->relance->statut
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors du traitement de la relance envoyée", [
                'relance_id' => $event->relance->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mettre à jour les statistiques de relances
     */
    private function mettreAJourStatistiques($relance): void
    {
        // Ici on pourrait implémenter une mise à jour des KPIs de relances
        // Par exemple, incrémenter un compteur de relances envoyées
        Log::info("Statistiques de relances mises à jour", [
            'relance_id' => $relance->id,
            'niveau' => $relance->niveau
        ]);
    }

    /**
     * Notifier les responsables selon le niveau de relance
     */
    private function notifierResponsables($relance): void
    {
        $utilisateursANotifier = [];
        $typeNotification = 'info';

        switch ($relance->niveau) {
            case 1:
                // Premier niveau: notifier seulement les comptables
                $utilisateursANotifier = User::role(['comptable'])->get();
                $typeNotification = 'info';
                break;

            case 2:
                // Deuxième niveau: notifier comptables et directeurs
                $utilisateursANotifier = User::role(['comptable', 'directeur'])->get();
                $typeNotification = 'warning';
                break;

            case 3:
            default:
                // Troisième niveau et plus: notifier tous les responsables
                $utilisateursANotifier = User::role(['comptable', 'directeur', 'superAdmin'])->get();
                $typeNotification = 'error';
                break;
        }

        $message = "Une relance de niveau {$relance->niveau} a été envoyée à {$relance->etudiant->nom_complet} pour un montant de {$relance->montant_du} FCFA.";

        foreach ($utilisateursANotifier as $utilisateur) {
            $this->notificationService->createNotification(
                $utilisateur,
                "Relance niveau {$relance->niveau} envoyée",
                $message,
                $typeNotification,
                route('esbtp.comptabilite.relances.index'),
                null
            );
        }
    }

    /**
     * Programmer la prochaine relance si nécessaire
     */
    private function programmerProchaineRelance($relance): void
    {
        // Si ce n'est pas la relance finale, programmer la suivante
        if ($relance->niveau < 3 && $relance->statut === 'envoyee') {
            Log::info("Programmation de la prochaine relance", [
                'relance_actuelle_id' => $relance->id,
                'niveau_suivant' => $relance->niveau + 1
            ]);

            // Ici on pourrait créer un job pour programmer automatiquement la prochaine relance
            // Par exemple après 7 jours pour le niveau 2, 14 jours pour le niveau 3
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(RelanceEnvoyee $event, \Throwable $exception): void
    {
        Log::error("Listener TraiterRelanceEnvoyee failed", [
            'relance_id' => $event->relance->id,
            'error' => $exception->getMessage()
        ]);
    }
}
