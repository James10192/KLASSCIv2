<?php

namespace App\Console\Commands;

use App\Services\ParentChatbot\ParentChatbotOnboardingService;
use Illuminate\Console\Command;

class MailPulseProcessParentChatbotOnboardingCommand extends Command
{
    protected $signature = 'mailpulse:process-parent-chatbot-onboarding {--limit=25}';

    protected $description = 'Traite les activations massives du chatbot parent MailPulse.';

    public function handle(ParentChatbotOnboardingService $onboarding): int
    {
        $result = $onboarding->process((int) $this->option('limit'));

        $this->info("Activations traitees: {$result['claimed']}; synchronisations: {$result['synced']}");

        return self::SUCCESS;
    }
}
