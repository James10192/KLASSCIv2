<?php

namespace App\Console\Commands;

use App\Services\ParentChatbot\ParentChatbotLinkCodeDeliveryService;
use Illuminate\Console\Command;

class MailPulseReconcileParentLinkCodesCommand extends Command
{
    protected $signature = 'mailpulse:reconcile-parent-link-codes {--limit=50}';

    protected $description = 'Rejoue les codes de liaison parent chatbot en attente de reconciliation MailPulse.';

    public function handle(ParentChatbotLinkCodeDeliveryService $delivery): int
    {
        $processed = $delivery->reconcilePending((int) $this->option('limit'));

        $this->info("Codes de liaison parents MailPulse traites: {$processed}");

        return self::SUCCESS;
    }
}
