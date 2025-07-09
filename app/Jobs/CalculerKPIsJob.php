<?php

namespace App\Jobs;

use App\Services\ComptabiliteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalculerKPIsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $periode;
    protected $anneeId;
    protected $dateCalcul;

    /**
     * Create a new job instance.
     */
    public function __construct($periode = 'jour', $anneeId = null, $dateCalcul = null)
    {
        $this->periode = $periode;
        $this->anneeId = $anneeId;
        $this->dateCalcul = $dateCalcul ?? now()->format('Y-m-d');

        // Configuration de la file d'attente selon la priorité
        $this->onQueue($this->determinerFileAttente());
    }

    /**
     * Détermine la file d'attente selon le type de calcul
     */
    private function determinerFileAttente()
    {
        return match($this->periode) {
            'horaire', 'temps_reel' => 'high',      // Priorité haute pour temps réel
            'journalier' => 'medium',               // Priorité moyenne pour quotidien
            'hebdomadaire', 'mensuel' => 'low',     // Priorité basse pour périodes longues
            default => 'medium'
        };
    }

    /**
     * Execute the job.
     */
    public function handle(ComptabiliteService $comptabiliteService): void
    {
        try {
            Log::info("Début calcul KPIs - Période: {$this->periode}, Date: {$this->dateCalcul}");

            // Calcul des KPIs avancés
            $kpis = $comptabiliteService->calculerKPIsAvances($this->anneeId);

            // Sauvegarde des KPIs
            $comptabiliteService->sauvegarderKPIs($kpis, $this->periode);

            Log::info("KPIs calculés et sauvegardés avec succès pour la période: {$this->periode}");

            // Calcul des KPIs additionnels selon la période
            $this->calculerKPIsSpecifiques($comptabiliteService);

        } catch (\Exception $e) {
            Log::error("Erreur lors du calcul des KPIs: " . $e->getMessage(), [
                'periode' => $this->periode,
                'annee_id' => $this->anneeId,
                'date_calcul' => $this->dateCalcul,
                'trace' => $e->getTraceAsString()
            ]);

            $this->fail($e);
        }
    }

    /**
     * Calcule des KPIs spécifiques selon la période
     */
    private function calculerKPIsSpecifiques(ComptabiliteService $comptabiliteService)
    {
        switch ($this->periode) {
            case 'jour':
                $this->calculerKPIsJournaliers($comptabiliteService);
                break;

            case 'semaine':
                $this->calculerKPIsHebdomadaires($comptabiliteService);
                break;

            case 'mois':
                $this->calculerKPIsMensuels($comptabiliteService);
                break;

            case 'trimestre':
                $this->calculerKPIsTrimestriels($comptabiliteService);
                break;

            case 'annee':
                $this->calculerKPIsAnnuels($comptabiliteService);
                break;
        }
    }

    private function calculerKPIsJournaliers(ComptabiliteService $comptabiliteService)
    {
        // KPIs spécifiques journaliers
        $dateCalcul = Carbon::parse($this->dateCalcul);

        // Exemple: Encaissements du jour
        $encaissementsJour = \App\Models\ESBTPPaiement::whereDate('date_paiement', $dateCalcul)
            ->where('statut', 'completé')
            ->sum('montant');

        \App\Models\ESBTPKPI::updateOrCreate(
            [
                'nom' => 'encaissements.journalier',
                'periode' => 'jour',
                'date_calcul' => $dateCalcul->format('Y-m-d')
            ],
            [
                'valeur' => $encaissementsJour,
                'type' => 'recette',
                'unite' => 'FCFA'
            ]
        );
    }

    private function calculerKPIsHebdomadaires(ComptabiliteService $comptabiliteService)
    {
        $dateCalcul = Carbon::parse($this->dateCalcul);
        $debutSemaine = $dateCalcul->startOfWeek();
        $finSemaine = $dateCalcul->copy()->endOfWeek();

        // Encaissements de la semaine
        $encaissementsSemaine = \App\Models\ESBTPPaiement::whereBetween('date_paiement', [$debutSemaine, $finSemaine])
            ->where('statut', 'completé')
            ->sum('montant');

        \App\Models\ESBTPKPI::updateOrCreate(
            [
                'nom' => 'encaissements.hebdomadaire',
                'periode' => 'semaine',
                'date_calcul' => $debutSemaine->format('Y-m-d')
            ],
            [
                'valeur' => $encaissementsSemaine,
                'type' => 'recette',
                'unite' => 'FCFA'
            ]
        );
    }

    private function calculerKPIsMensuels(ComptabiliteService $comptabiliteService)
    {
        $dateCalcul = Carbon::parse($this->dateCalcul);
        $debutMois = $dateCalcul->startOfMonth();
        $finMois = $dateCalcul->copy()->endOfMonth();

        // Taux de recouvrement mensuel
        $totalInscriptions = \App\Models\ESBTPInscription::whereHas('anneeUniversitaire', function($q) {
            $q->where('est_actif', true);
        })->count();

        $etudiantsPayeComplet = \App\Models\ESBTPInscription::whereHas('paiements', function($q) use ($debutMois, $finMois) {
            $q->whereBetween('date_paiement', [$debutMois, $finMois])
              ->where('statut', 'completé');
        })->distinct('etudiant_id')->count();

        $tauxRecouvrement = $totalInscriptions > 0 ?
            round(($etudiantsPayeComplet / $totalInscriptions) * 100, 2) : 0;

        \App\Models\ESBTPKPI::updateOrCreate(
            [
                'nom' => 'taux.recouvrement.mensuel',
                'periode' => 'mois',
                'date_calcul' => $debutMois->format('Y-m-d')
            ],
            [
                'valeur' => $tauxRecouvrement,
                'type' => 'performance',
                'unite' => '%'
            ]
        );
    }

    private function calculerKPIsTrimestriels(ComptabiliteService $comptabiliteService)
    {
        $dateCalcul = Carbon::parse($this->dateCalcul);
        $debutTrimestre = $dateCalcul->startOfQuarter();
        $finTrimestre = $dateCalcul->copy()->endOfQuarter();

        // Résultat net trimestriel
        $recettes = \App\Models\ESBTPPaiement::whereBetween('date_paiement', [$debutTrimestre, $finTrimestre])
            ->where('statut', 'completé')
            ->sum('montant');

        $depenses = \App\Models\ESBTPDepense::whereBetween('date_depense', [$debutTrimestre, $finTrimestre])
            ->whereIn('statut', ['validée', 'approuve'])
            ->sum('montant');

        $resultatNet = $recettes - $depenses;

        \App\Models\ESBTPKPI::updateOrCreate(
            [
                'nom' => 'resultat.net.trimestriel',
                'periode' => 'trimestre',
                'date_calcul' => $debutTrimestre->format('Y-m-d')
            ],
            [
                'valeur' => $resultatNet,
                'type' => 'performance',
                'unite' => 'FCFA'
            ]
        );
    }

    private function calculerKPIsAnnuels(ComptabiliteService $comptabiliteService)
    {
        $dateCalcul = Carbon::parse($this->dateCalcul);
        $debutAnnee = $dateCalcul->startOfYear();
        $finAnnee = $dateCalcul->copy()->endOfYear();

        // Croissance annuelle
        $recettesAnneeActuelle = \App\Models\ESBTPPaiement::whereBetween('date_paiement', [$debutAnnee, $finAnnee])
            ->where('statut', 'completé')
            ->sum('montant');

        $recettesAnneePrecedente = \App\Models\ESBTPPaiement::whereBetween('date_paiement', [
            $debutAnnee->copy()->subYear(),
            $finAnnee->copy()->subYear()
        ])
        ->where('statut', 'completé')
        ->sum('montant');

        $croissance = $recettesAnneePrecedente > 0 ?
            round((($recettesAnneeActuelle - $recettesAnneePrecedente) / $recettesAnneePrecedente) * 100, 2) : 0;

        \App\Models\ESBTPKPI::updateOrCreate(
            [
                'nom' => 'croissance.annuelle',
                'periode' => 'annee',
                'date_calcul' => $debutAnnee->format('Y-m-d')
            ],
            [
                'valeur' => $croissance,
                'type' => 'performance',
                'unite' => '%'
            ]
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job CalculerKPIs échoué définitivement", [
            'periode' => $this->periode,
            'annee_id' => $this->anneeId,
            'date_calcul' => $this->dateCalcul,
            'attempts' => $this->attempts,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Notifier les administrateurs des échecs critiques
        if (in_array($this->periode, ['horaire', 'journalier'])) {
            $this->notifierEchecCritique($exception);
        }
    }

    /**
     * Notifie les administrateurs en cas d'échec critique
     */
    private function notifierEchecCritique(\Throwable $exception)
    {
        try {
            // Créer une notification dans la base de données
            \DB::table('notifications')->insert([
                'type' => 'critical_job_failure',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => 1, // Admin principal
                'data' => json_encode([
                    'job_type' => 'CalculerKPIs',
                    'periode' => $this->periode,
                    'error' => $exception->getMessage(),
                    'timestamp' => now()->toISOString()
                ]),
                'created_at' => now(),
                'updated_at' => now(),
                'id' => \Illuminate\Support\Str::uuid()
            ]);

            Log::info("Notification d'échec critique envoyée pour CalculerKPIs");
        } catch (\Exception $e) {
            Log::error("Impossible d'envoyer la notification d'échec: " . $e->getMessage());
        }
    }

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5; // Augmenté pour plus de résilience

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60, 180, 300]; // Progression exponentielle

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(3); // Plus de temps pour les calculs complexes
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return match($this->periode) {
            'horaire', 'temps_reel' => [5, 15, 30],
            'journalier' => [30, 60, 120],
            'hebdomadaire', 'mensuel' => [60, 180, 300],
            default => [30, 60, 120]
        };
    }
}
