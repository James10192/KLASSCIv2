<?php

namespace App\Console\Commands;

use App\Models\ParentChatbotInboundEvent;
use Illuminate\Console\Command;

class MailPulsePruneParentChatbotInboundResponsesCommand extends Command
{
    protected $signature = 'mailpulse:prune-parent-chatbot-inbound-responses
        {--hours= : Retention period in hours}
        {--limit=1000 : Maximum number of responses to prune}';

    protected $description = 'Prune expired encrypted parent chatbot inbound responses';

    public function handle(): int
    {
        $hours = $this->positiveIntegerOption('hours', (int) config('services.mailpulse.parent_chatbot_inbound_response_retention_hours', 168));
        $limit = $this->positiveIntegerOption('limit', 1000);

        if ($hours === null || $limit === null || $hours > 8760 || $limit > 10000) {
            $this->error('The hours option must be between 1 and 8760 and limit between 1 and 10000.');

            return self::INVALID;
        }

        $pruned = ParentChatbotInboundEvent::pruneRecordedResponsesBefore(now()->subHours($hours), $limit);
        $this->info("Pruned {$pruned} parent chatbot inbound response(s).");

        return self::SUCCESS;
    }

    private function positiveIntegerOption(string $option, int $default): ?int
    {
        $value = $this->option($option);
        if ($value === null) {
            return $default;
        }

        return is_string($value) && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }
}
