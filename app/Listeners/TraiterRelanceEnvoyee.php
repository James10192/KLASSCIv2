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
                $utilisateursANotifier = User::role(['comptable'])->get();
                $typeNotification = 'info';
                break;

            case 2:
                $utilisateursANotifier = User::role(['comptable', 'coordinateur'])->get();
                $typeNotification = 'warning';
                break;

            case 3:
            default:
                $utilisateursANotifier = User::role(['comptable', 'coordinateur', 'superAdmin'])->get();
                $typeNotification = 'error';
                break;
        }

        $message = "Une relance de niveau {$relance->niveau} a été envoyée à {$relance->etudiant->nom_complet}.";

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
        if ($relance->niveau >= 3 || $relance->statut !== 'envoyee') {
            return;
        }

        $prochainNiveau = $relance->niveau + 1;

        // Lire les délais depuis settings, sinon défaut 7j/14j
        $delaiKey = "relances.delai_niveau_{$prochainNiveau}";
        $delaiJours = (int) (\DB::table('settings')->where('key', $delaiKey)->value('value') ?? ($prochainNiveau === 2 ? 7 : 14));

        \App\Models\ESBTPRelance::create([
            'etudiant_id'      => $relance->etudiant_id,
            'facture_id'       => $relance->facture_id,
            'type'             => $relance->type,
            'niveau'           => $prochainNiveau,
            'template_utilise' => "relance_niveau_{$prochainNiveau}",
            'date_envoi'       => now()->addDays($delaiJours),
            'statut'           => 'planifiee',
        ]);

        Log::info("Prochaine relance programmée", [
            'relance_actuelle_id' => $relance->id,
            'niveau_suivant'      => $prochainNiveau,
            'delai_jours'         => $delaiJours,
        ]);
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
