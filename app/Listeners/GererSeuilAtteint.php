<?php

namespace App\Listeners;

use App\Events\SeuilAtteint;
use App\Services\NotificationService;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GererSeuilAtteint implements ShouldQueue
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
    public function handle(SeuilAtteint $event): void
    {
        try {
            Log::info("Seuil atteint détecté: {$event->typeKPI}", [
                'type' => $event->typeKPI,
                'valeur' => $event->valeurActuelle,
                'seuil' => $event->seuil,
                'niveau' => $event->niveau
            ]);

            // Déterminer les utilisateurs à notifier selon le niveau de criticité
            $utilisateurs = $this->getUtilisateursANotifier($event->niveau);

            // Déterminer le type de notification selon le niveau
            $typeNotification = $this->getTypeNotification($event->niveau);

            foreach ($utilisateurs as $utilisateur) {
                $this->notificationService->createNotification(
                    $utilisateur,
                    "Alerte {$event->niveau}: {$event->typeKPI}",
                    $event->message,
                    $typeNotification,
                    route('esbtp.comptabilite.dashboard'),
                    null
                );
            }

            // Log supplémentaire pour les seuils critiques
            if ($event->niveau === 'critique') {
                Log::critical("Seuil critique atteint", [
                    'type_kpi' => $event->typeKPI,
                    'valeur_actuelle' => $event->valeurActuelle,
                    'seuil' => $event->seuil,
                    'pourcentage' => round(($event->valeurActuelle / $event->seuil) * 100, 2)
                ]);
            }

            Log::info("Notifications seuil atteint envoyées", [
                'type_kpi' => $event->typeKPI,
                'niveau' => $event->niveau,
                'nombre_notifies' => count($utilisateurs)
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la gestion du seuil atteint", [
                'type_kpi' => $event->typeKPI,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtenir les utilisateurs à notifier selon le niveau
     */
    private function getUtilisateursANotifier(string $niveau): array
    {
        switch ($niveau) {
            case 'critique':
                // Notifier tous les administrateurs et directeurs
                return User::role(['superAdmin', 'directeur', 'comptable'])->get()->toArray();

            case 'warning':
                // Notifier les comptables et directeurs
                return User::role(['directeur', 'comptable'])->get()->toArray();

            case 'info':
            default:
                // Notifier seulement les comptables
                return User::role(['comptable'])->get()->toArray();
        }
    }

    /**
     * Obtenir le type de notification selon le niveau
     */
    private function getTypeNotification(string $niveau): string
    {
        switch ($niveau) {
            case 'critique':
                return 'error';
            case 'warning':
                return 'warning';
            case 'info':
            default:
                return 'info';
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(SeuilAtteint $event, \Throwable $exception): void
    {
        Log::error("Listener GererSeuilAtteint failed", [
            'type_kpi' => $event->typeKPI,
            'niveau' => $event->niveau,
            'error' => $exception->getMessage()
        ]);
    }
}
