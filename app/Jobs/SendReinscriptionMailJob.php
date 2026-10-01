<?php

namespace App\Jobs;

use App\Models\ESBTPInscription;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Envoie l'email de confirmation de réinscription aux parents (queued pour éviter
 * de spammer N synchronously en bulk).
 */
class SendReinscriptionMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public int $inscriptionId,
        public string $decision,
        public ?string $batchId = null,
    ) {}

    public function handle(NotificationService $notif): void
    {
        $inscription = ESBTPInscription::find($this->inscriptionId);
        if (!$inscription) {
            Log::warning('SendReinscriptionMailJob: inscription not found', [
                'inscription_id' => $this->inscriptionId,
                'batch_id' => $this->batchId,
            ]);
            return;
        }

        try {
            $notif->notifyParentsReinscriptionCreated($inscription, $this->decision, null);
        } catch (\Throwable $e) {
            // Refus de débit : le worker reporte le job (ReportDesCourrielsRefuses), ce n'est pas une panne.
            $niveau = $e instanceof \App\Mail\Transport\DebitMailPulseAtteint ? 'warning' : 'error';
            Log::$niveau('SendReinscriptionMailJob: notification failed', [
                'inscription_id' => $this->inscriptionId,
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
