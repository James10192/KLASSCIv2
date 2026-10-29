<?php

namespace App\Console\Commands\Reconciliation;

use App\Domain\Comptabilite\Reconciliation\Notifications\ReconciliationOverdueNotification;
use App\Domain\Comptabilite\Reconciliation\Services\ReconciliationMetricsService;
use App\Helpers\SettingsHelper;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Vérifie les sessions overdue et notifie les utilisateurs ayant la permission
 * `comptabilite.reconciliation.open` (rule customizable-roles : permission, pas rôle).
 *
 * À schedule daily (8h) dans bootstrap/app.php.
 */
class CheckOverdueCommand extends Command
{
    protected $signature = 'reconciliation:check-overdue {--dry : Liste les destinataires sans envoyer}';

    protected $description = 'Notifie les utilisateurs autorisés des sessions de réconciliation overdue.';

    public function handle(ReconciliationMetricsService $service): int
    {
        $overdue = $service->overdueSessions();
        if ($overdue->isEmpty()) {
            $this->info('Aucune session overdue — rien à notifier.');
            return self::SUCCESS;
        }

        $thresholdDays = (int) SettingsHelper::get('comptabilite.reconciliation.overdue_days', 2);

        // Notifier les users qui ont la permission métier (pas un rôle hardcodé)
        $recipients = User::query()
            ->whereHas('permissions', fn ($q) => $q->where('name', 'comptabilite.reconciliation.open'))
            ->orWhereHas('roles.permissions', fn ($q) => $q->where('name', 'comptabilite.reconciliation.open'))
            ->whereNotNull('email')
            ->distinct()
            ->get();

        $this->info("Sessions overdue : {$overdue->count()}");
        $this->info("Destinataires (permission .open) : {$recipients->count()}");

        if ($this->option('dry')) {
            foreach ($recipients as $u) {
                $this->line("  → {$u->email} ({$u->name})");
            }
            return self::SUCCESS;
        }

        try {
            Notification::send($recipients, new ReconciliationOverdueNotification($overdue, $thresholdDays));
            $this->info('Notifications envoyées.');
            Log::info('reconciliation:check-overdue dispatched', [
                'overdue_count' => $overdue->count(),
                'recipients_count' => $recipients->count(),
            ]);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Erreur envoi : ' . $e->getMessage());
            Log::error('reconciliation:check-overdue failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}
