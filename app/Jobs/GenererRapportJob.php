<?php

namespace App\Jobs;

use App\Services\ReportingService;
use App\Services\PDFService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenererRapportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $parametres;
    protected $userId;
    protected $formatExport;

    /**
     * Create a new job instance.
     */
    public function __construct($parametres, $userId = null, $formatExport = 'pdf')
    {
        $this->parametres = $parametres;
        $this->userId = $userId;
        $this->formatExport = $formatExport;

        // File spécialisée pour les rapports (traitement long)
        $this->onQueue('reports');
    }

    /**
     * Execute the job.
     */
    public function handle(ReportingService $reportingService, PDFService $pdfService): void
    {
        try {
            Log::info("Début génération rapport", [
                'user_id' => $this->userId,
                'type' => $this->parametres['type'] ?? 'general',
                'format' => $this->formatExport
            ]);

            // Génération du rapport
            $donnees = $reportingService->genererRapportPersonnalise($this->parametres);

            // Export selon le format demandé
            $resultat = $this->exporterRapport($donnees, $reportingService, $pdfService);

            if ($resultat['success']) {
                // Notification à l'utilisateur (si implémenté)
                $this->notifierUtilisateur($resultat);

                Log::info("Rapport généré avec succès", [
                    'user_id' => $this->userId,
                    'fichier' => $resultat['filename'],
                    'path' => $resultat['path']
                ]);
            } else {
                throw new \Exception("Erreur lors de l'export: " . $resultat['message']);
            }

        } catch (\Exception $e) {
            Log::error("Erreur génération rapport", [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'parametres' => $this->parametres
            ]);

            $this->fail($e);
        }
    }

    /**
     * Exporte le rapport selon le format demandé
     */
    private function exporterRapport($donnees, ReportingService $reportingService, PDFService $pdfService)
    {
        switch ($this->formatExport) {
            case 'pdf':
                return $pdfService->genererRapportFinancier($donnees);

            case 'excel':
                return $reportingService->exporterDonnees('excel', $donnees);

            case 'csv':
                return $reportingService->exporterDonnees('csv', $donnees);

            default:
                throw new \Exception("Format d'export non supporté: {$this->formatExport}");
        }
    }

    /**
     * Notifie l'utilisateur que le rapport est prêt
     */
    private function notifierUtilisateur($resultat)
    {
        if (!$this->userId) {
            return;
        }

        try {
            // Ici vous pourriez envoyer une notification à l'utilisateur
            // Par exemple avec Laravel Notifications

            /*
            $user = \App\Models\User::find($this->userId);
            if ($user) {
                $user->notify(new \App\Notifications\RapportGenereNotification($resultat));
            }
            */

        } catch (\Exception $e) {
            Log::warning("Impossible de notifier l'utilisateur", [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Nettoie les anciens rapports (optionnel)
     */
    private function nettoyerAnciensRapports()
    {
        try {
            $dossierRapports = storage_path('app/public/rapports');
            $fichiers = glob($dossierRapports . '/*');
            $maintenant = time();
            $dureeConservation = 30 * 24 * 60 * 60; // 30 jours

            foreach ($fichiers as $fichier) {
                if (is_file($fichier)) {
                    $ageRichier = $maintenant - filemtime($fichier);
                    if ($ageRichier > $dureeConservation) {
                        unlink($fichier);
                        Log::info("Ancien rapport supprimé: " . basename($fichier));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Erreur lors du nettoyage des anciens rapports: " . $e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job GenererRapport failed", [
            'user_id' => $this->userId,
            'parametres' => $this->parametres,
            'format' => $this->formatExport,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Notifier l'utilisateur de l'échec
        try {
            if ($this->userId) {
                // Notification d'échec
                /*
                $user = \App\Models\User::find($this->userId);
                if ($user) {
                    $user->notify(new \App\Notifications\RapportEchecNotification($exception->getMessage()));
                }
                */
            }
        } catch (\Exception $e) {
            Log::error("Impossible de notifier l'échec à l'utilisateur: " . $e->getMessage());
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300];

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'rapport',
            'user:' . $this->userId,
            'type:' . ($this->parametres['type'] ?? 'general'),
            'format:' . $this->formatExport
        ];
    }
}
