<?php

namespace App\Console\Commands;

use App\Models\ParentNotificationLog;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseParentNotificationLog;
use Illuminate\Console\Command;

class MailPulseReconcileParentNotificationsCommand extends Command
{
    protected $signature = 'mailpulse:reconcile-parent-notifications {--limit=50}';

    protected $description = 'Rejoue les notifications parents MailPulse en attente de reconciliation.';

    public function handle(
        MailPulseParentNotificationLog $notificationLogs,
        MailPulseClient $client
    ): int {
        $limit = max(1, min(200, (int) $this->option('limit')));
        $processed = 0;

        $notificationLogs->expireOutboxEntries();

        ParentNotificationLog::query()
            ->where('status', 'pending')
            ->whereNotNull('request_id')
            ->where('metadata->provider', 'mailpulse')
            ->where(function ($query): void {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', now());
            })
            ->oldest('updated_at')
            ->limit($limit)
            ->get()
            ->each(function (ParentNotificationLog $log) use ($notificationLogs, $client, &$processed): void {
                if ($notificationLogs->retryPending($log, $client)) {
                    $processed++;
                }
            });

        $this->info("Notifications parents MailPulse rejouees: {$processed}");

        return self::SUCCESS;
    }
}
