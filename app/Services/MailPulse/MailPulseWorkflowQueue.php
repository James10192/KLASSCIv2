<?php

namespace App\Services\MailPulse;

use App\Jobs\MailPulse\DispatchMailPulseParentNotificationJob;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single entry point used by the web layer to hand a parent notification over
 * to the queue. It never throws: a MailPulse problem must not turn a payment
 * validation or a grade entry into a 500 for the secretariat.
 */
class MailPulseWorkflowQueue
{
    public function __construct(private MailPulseWorkflowPolicy $workflowPolicy) {}

    public function push(MailPulseWorkflowIntent $intent): void
    {
        try {
            // Cheap kill switch check so a disabled tenant never fills the queue.
            // The worker re-checks it before emitting anything.
            if (! $this->workflowPolicy->realWorkflowsEnabled()) {
                return;
            }

            // afterCommit keeps the worker from reading rows the caller has not
            // committed yet. The sync driver ignores it and runs inline, which
            // matches the behaviour that existed before this queue was added.
            DispatchMailPulseParentNotificationJob::dispatch($intent)->afterCommit();
        } catch (Throwable $e) {
            Log::warning('MailPulse workflow notification could not be queued', [
                'event' => $intent->event,
                'error' => $e->getMessage(),
            ]);

            $this->runInline($intent);
        }
    }

    /**
     * Nothing is written to the outbox before the job runs, so a queue backend
     * that is down would otherwise lose the notification with no row for the
     * reconciler to replay. Running inline is slower than the queue but keeps
     * the durable record, and still never propagates a failure to the caller.
     */
    private function runInline(MailPulseWorkflowIntent $intent): void
    {
        try {
            app(MailPulseWorkflowIntentRunner::class)->run($intent);
        } catch (Throwable $e) {
            Log::error('MailPulse workflow notification lost', [
                'event' => $intent->event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
