<?php

namespace App\Console\Commands;

use App\Services\Admissions\AdmissionActivationNotifier;
use App\Services\Admissions\AdmissionActivationOutbox;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionWorkflow;
use Illuminate\Console\Command;

final class ProcessAdmissionInvitations extends Command
{
    protected $signature = 'inscriptions:envoyer-invitations {--max=25}';
    protected $description = 'Traite les invitations de compte étudiant en attente avec idempotence MailPulse';

    public function handle(
        InscriptionWorkflowSettings $settings,
        AdmissionActivationOutbox $outbox,
        AdmissionActivationNotifier $notifier,
        ManagedInscriptionWorkflow $managed
    ): int {
        if (! $settings->reliableOutboxEnabled()) {
            $this->line('Outbox des invitations désactivée pour cet établissement.');

            return self::SUCCESS;
        }

        $summary = $outbox->process((int) $this->option('max'), $notifier, $managed);
        $this->line(json_encode($summary, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
