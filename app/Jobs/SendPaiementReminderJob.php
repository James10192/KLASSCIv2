<?php

namespace App\Jobs;

use App\Domain\Notifications\Notifiers\PaiementNotifier;
use App\Models\ESBTPPaiement;
use App\Models\NotificationReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job queue dédié pour l'envoi d'UN rappel de paiement en attente
 * (Phase 14 Plan v4 — symétrique à SendInscriptionReminderJob).
 *
 * Queue : 'reminders' (low priority)
 * Retry : 3 tentatives, backoff [60s, 180s, 600s]
 * Timeout : 60s par job
 *
 * Usage :
 *   SendPaiementReminderJob::dispatch($paiement->id, $daysPending, $count);
 */
class SendPaiementReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $paiementId,
        public readonly int $daysPending,
        public readonly int $reminderCount,
    ) {
        $this->onQueue('reminders');
    }

    public function backoff(): array
    {
        return [60, 180, 600];
    }

    public function handle(PaiementNotifier $notifier): void
    {
        $paiement = ESBTPPaiement::find($this->paiementId);

        if (! $paiement) {
            Log::warning('[reminder-paiement] Paiement introuvable — annulation', [
                'paiement_id' => $this->paiementId,
            ]);
            return;
        }

        // Garde-fou : ne pas envoyer rappel si le paiement n'est plus en attente
        if ($paiement->status !== 'en_attente') {
            Log::info('[reminder-paiement] Paiement plus en attente — rappel skipé', [
                'paiement_id' => $this->paiementId,
                'current_status' => $paiement->status,
            ]);
            $reminder = NotificationReminder::where('remindable_type', 'App\Models\ESBTPPaiement')
                ->where('remindable_id', $this->paiementId)
                ->first();
            if ($reminder) {
                $reminder->deactivate();
            }
            return;
        }

        try {
            // Phase 8b strangler fig — délégation via PaiementNotifier shell
            $notifier->rappelPaiement($paiement, $this->daysPending, $this->reminderCount);

            Log::info('[reminder-paiement] Rappel envoyé', [
                'paiement_id' => $this->paiementId,
                'days_pending' => $this->daysPending,
                'reminder_count' => $this->reminderCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('[reminder-paiement] Échec envoi rappel', [
                'paiement_id' => $this->paiementId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[reminder-paiement] Job permanently failed après 3 retries', [
            'paiement_id' => $this->paiementId,
            'error' => $exception->getMessage(),
        ]);
    }
}
