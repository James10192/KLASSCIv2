<?php

namespace App\Console\Commands;

use App\Services\Familles\InvitationResponsable;
use App\Services\Familles\ReglagesFamille;
use App\Services\MailPulse\MailPulseClient;
use Illuminate\Console\Command;

final class ProcessFamilyInvitations extends Command
{
    protected $signature = 'familles:envoyer-invitations {--max=25}';
    protected $description = 'Traite la file persistante des invitations parentales MailPulse';

    public function handle(ReglagesFamille $settings, InvitationResponsable $service, MailPulseClient $mailPulse): int
    {
        if (! $settings->enabled()) {
            $this->line('Portail familial désactivé pour cet établissement.');

            return self::SUCCESS;
        }

        $this->line(json_encode($service->traiter((int) $this->option('max'), $mailPulse), JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
