<?php

namespace App\Jobs;

use App\Models\ESBTPRelance;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnvoyerRelanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $relance;

    /**
     * Create a new job instance.
     */
    public function __construct(ESBTPRelance $relance)
    {
        $this->relance = $relance;

        // File haute priorité pour les relances (communication critique)
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            Log::info("Début envoi relance ID: {$this->relance->id}");

            $resultat = $notificationService->envoyerRelance($this->relance);

            // Ecartee (eleve hors des relances) : rien a envoyer, rien a
            // retenter, et surtout pas de passage en echec, qui la rendrait
            // de nouveau renvoyable.
            if ($resultat['ecartee'] ?? false) {
                Log::info("Relance ID: {$this->relance->id} écartée : {$resultat['message']}");

                return;
            }

            if ($resultat['success']) {
                Log::info("Relance envoyée avec succès ID: {$this->relance->id}");
            } else {
                Log::error("Échec envoi relance ID: {$this->relance->id} - " . $resultat['message']);
                $this->relance->marquerCommeEchec(['error' => $resultat['message']]);
                $this->fail($resultat['message']);
            }

        } catch (\Exception $e) {
            Log::error("Erreur critique Job EnvoyerRelance ID: {$this->relance->id} - " . $e->getMessage());
            $this->relance->marquerCommeEchec(['error' => $e->getMessage()]);
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job EnvoyerRelance failed pour relance ID: {$this->relance->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->relance->marquerCommeEchec([
            'job_failed' => true,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10);
    }

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5; // Plus de tentatives pour les communications importantes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [5, 15, 30, 60, 120]; // Retry rapide pour relances
}
