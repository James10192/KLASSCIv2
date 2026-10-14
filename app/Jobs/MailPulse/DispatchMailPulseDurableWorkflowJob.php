<?php

namespace App\Jobs\MailPulse;

use App\Services\MailPulse\MailPulseWorkflowIntentOutbox;
use App\Services\MailPulse\MailPulseWorkflowIntentRunner;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

final class DispatchMailPulseDurableWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // Reconciler owns retries. The queue job is only an accelerator.
    public int $tries = 1;
    public int $timeout = 90;

    public function __construct(public readonly int $intentId) {}

    public function handle(
        MailPulseWorkflowIntentOutbox $outbox,
        MailPulseWorkflowIntentRunner $runner,
        MailPulseWorkflowPolicy $policy
    ): void {
        $outbox->process($this->intentId, $runner, $policy);
    }

    public function tags(): array
    {
        return ['mailpulse', 'mailpulse:durable-intent'];
    }
}
