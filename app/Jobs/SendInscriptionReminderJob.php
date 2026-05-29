<?php

namespace App\Jobs;

use App\Domain\Notifications\Notifiers\InscriptionNotifier;
use App\Models\ESBTPInscription;
use App\Models\NotificationReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job queue dédié pour l'envoi d'UN rappel d'inscription en attente
 * (Phase 14 Plan v4 — migration vers queue jobs asynchrones).
 *
 * Auparavant SendInscriptionPaiementReminders command exécutait tous les rappels
 * en série (boucle synchrone). Sur tenants avec 100+ inscriptions en attente,
 * la commande pouvait prendre 5-10 min et timeout.
 *
 * Avec ce job dispatché par inscription : exécution parallèle sur la queue
 * 'reminders' (workers Horizon), aucun timeout, retry exponential en cas d'échec
 * SMTP/Meta API transitoire.
 *
 * Queue : 'reminders' (low priority, ne bloque pas la queue 'high' des relances)
 * Retry : 3 tentatives, backoff [60s, 180s, 600s]
 * Timeout : 60s par job (suffisant pour notif multi-canal email+WA+SMS)
 *
 * Usage depuis SendInscriptionPaiementReminders command :
 *   SendInscriptionReminderJob::dispatch($inscription, $daysPending, $count);
 */
class SendInscriptionReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly int $inscriptionId,
        public readonly int $daysPending,
        public readonly int $reminderCount,
    ) {
        $this->onQueue('reminders');
    }

    public function backoff(): array
    {
        return [60, 180, 600];
    }

    public function handle(InscriptionNotifier $notifier): void
    {
        $inscription = ESBTPInscription::find($this->inscriptionId);

        if (! $inscription) {
            Log::warning('[reminder-inscription] Inscription introuvable — annulation', [
                'inscription_id' => $this->inscriptionId,
            ]);
            return;
        }

        // Garde-fou : ne pas envoyer rappel si l'inscription est déjà validée
        // (peut arriver si validation pendant que le job était en queue)
        if ($inscription->workflow_step === 'etudiant_cree') {
            Log::info('[reminder-inscription] Inscription désormais validée — rappel skipé', [
                'inscription_id' => $this->inscriptionId,
            ]);
            // Désactiver le reminder pour cohérence
            $reminder = NotificationReminder::where('remindable_type', 'App\Models\ESBTPInscription')
                ->where('remindable_id', $this->inscriptionId)
                ->first();
            if ($reminder) {
                $reminder->deactivate();
            }
            return;
        }

        try {
            // Phase 8b strangler fig — délégation via InscriptionNotifier shell
            $notifier->rappelInscription($inscription, $this->daysPending, $this->reminderCount);

            Log::info('[reminder-inscription] Rappel envoyé', [
                'inscription_id' => $this->inscriptionId,
                'days_pending' => $this->daysPending,
                'reminder_count' => $this->reminderCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('[reminder-inscription] Échec envoi rappel', [
                'inscription_id' => $this->inscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw pour retry exponential
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[reminder-inscription] Job permanently failed après 3 retries', [
            'inscription_id' => $this->inscriptionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
