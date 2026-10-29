<?php

namespace App\Console\Commands;

use App\Services\MailPulse\MailPulseWorkflowIntentOutbox;
use App\Services\MailPulse\MailPulseWorkflowIntentRunner;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use Illuminate\Console\Command;
use Throwable;

final class MailPulseDrainWorkflowIntentsCommand extends Command
{
    protected $signature = 'mailpulse:drain-workflow-intents {--limit=5 : Max intentions par passage}';
    protected $description = 'Rejouer les intentions de notification persistées, même si la file Redis/DB est indisponible';

    public function handle(
        MailPulseWorkflowIntentOutbox $outbox,
        MailPulseWorkflowIntentRunner $runner,
        MailPulseWorkflowPolicy $policy
    ): int {
        if (! $outbox->enabled() || ! $policy->realWorkflowsEnabled()) {
            $this->line('Durable workflow outbox disabled.');
            return self::SUCCESS;
        }

        try {
            $ids = $outbox->dueIds((int) $this->option('limit'));
        } catch (Throwable $e) {
            $this->error('Impossible de lire l’outbox des workflows : '.$e::class);
            return self::FAILURE;
        }

        $processed = 0;
        foreach ($ids as $id) {
            if ($outbox->process($id, $runner, $policy)) {
                $processed++;
            }
        }
        $this->info(sprintf('Intentions reprises : %d/%d.', $processed, count($ids)));

        return self::SUCCESS;
    }
}
