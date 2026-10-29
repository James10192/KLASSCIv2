<?php

namespace App\Console\Commands;

use App\Services\MailPulse\MailPulseTestNotificationService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class MailPulseTestCommand extends Command
{
    protected $signature = 'mailpulse:test
        {--event=payment_received : payment_received, absence_reported, grade_published, fee_reminder}
        {--channel=both : email, whatsapp, sms, both}
        {--dry-run=true : true pour simuler sans appel MailPulse, false pour envoyer aux destinataires de test}';

    protected $description = 'Simule une notification KLASSCI via MailPulse vers TEST_NOTIFICATION_EMAIL/PHONE uniquement';

    public function handle(MailPulseTestNotificationService $service): int
    {
        $dryRun = filter_var($this->option('dry-run'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($dryRun === null) {
            $this->error('--dry-run doit valoir true ou false.');
            return self::FAILURE;
        }

        try {
            $result = $service->send(
                (string) $this->option('event'),
                (string) $this->option('channel'),
                $dryRun
            );
        } catch (ValidationException $e) {
            $this->error('Configuration ou payload invalide.');
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->line(" - {$field}: {$message}");
                }
            }

            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
