<?php

namespace App\Jobs\MailPulse;

use App\Services\MailPulse\MailPulseWorkflowIntent;
use App\Services\MailPulse\MailPulseWorkflowIntentRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Emits one parent workflow notification to MailPulse outside the web request.
 *
 * Validating a payment or publishing a grade used to block on a synchronous
 * MailPulse HTTP call. The job moves that call to a worker so a slow provider
 * can no longer time out the secretariat screens.
 *
 * Idempotency is delegated to the existing outbox: MailPulseParentNotificationLog
 * derives a deterministic request_id from (tenant, event, channel, parent,
 * student, metadata) and firstOrCreate() short circuits an already recorded
 * notification with the "already_recorded" status. Replaying this job is
 * therefore safe, and a MailPulse call left in an unknown state is picked up by
 * the mailpulse:reconcile-parent-notifications schedule, not by this job.
 *
 * Note: the queue driver must be a real one (database, redis, sqs) for the
 * asynchronous behaviour to apply. Under QUEUE_CONNECTION=sync the job runs
 * inline, which preserves the previous behaviour without regression.
 */
class DispatchMailPulseParentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Transport level retries; MailPulse level retries live in the outbox. */
    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 180, 900];

    public int $timeout = 90;

    /** Give up rather than pile up on an outage the reconciler already covers. */
    public int $maxExceptions = 3;

    public function __construct(public MailPulseWorkflowIntent $intent) {}

    public function handle(MailPulseWorkflowIntentRunner $runner): void
    {
        $runner->run($this->intent);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('MailPulse parent notification job failed', [
            'event' => $this->intent->event,
            'payload' => $this->intent->payload,
            'error' => $exception->getMessage(),
        ]);
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['mailpulse', 'mailpulse:' . $this->intent->event];
    }
}
