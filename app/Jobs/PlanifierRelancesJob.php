<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PlanifierRelancesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $parametres;

    /**
     * Create a new job instance.
     */
    public function __construct(array $parametres = [])
    {
        $this->parametres = array_merge([
            'niveau_max' => 3,
            'intervalle_jours' => 7,
            'types_relance' => ['email', 'sms'],
            'segmentation' => 'auto'
        ], $parametres);

        // File priorité moyenne pour la planification
        $this->onQueue('medium');
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            Log::info("Début planification automatique des relances", $this->parametres);

            // Planifier les relances selon la configuration
            $resultat = $notificationService->planifierRelancesAvancees($this->parametres);

            Log::info("Planification terminée", [
                'relances_planifiees' => $resultat['relances_planifiees'],
                'etudiants_traites' => $resultat['etudiants_traites'] ?? 0
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la planification automatique des relances: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job PlanifierRelancesJob échoué", [
            'error' => $exception->getMessage(),
            'parametres' => $this->parametres
        ]);
    }

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300]; // 1 min, puis 5 min
}
