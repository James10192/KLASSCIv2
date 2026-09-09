<?php

namespace App\Jobs;

use App\Services\MailPulse\MailPulseClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EnvoyerMailRdvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $email,
        public readonly string $sujet,
        public readonly string $texte,
        public readonly string $html = '',
        public readonly ?string $pdfBase64 = null,
    ) {
    }

    public function handle(MailPulseClient $client): void
    {
        $contenu = $this->html !== ''
            ? ['type' => 'html', 'html' => $this->html, 'text' => $this->texte, 'subject' => $this->sujet]
            : ['type' => 'text', 'text' => $this->sujet."\n\n".$this->texte];

        $message = [
            'channel' => 'email',
            'recipient' => ['type' => 'email', 'value' => $this->email],
            'content' => $contenu,
            'metadata' => ['source' => 'klassci', 'workflow_event' => 'rendez_vous'],
        ];

        if ($this->pdfBase64) {
            $message['attachments'] = [[
                'filename' => 'convocation-rendez-vous.pdf',
                'content' => $this->pdfBase64,
                'content_type' => 'application/pdf',
            ]];
        }

        $resultat = $client->sendEmailMessage($message);

        if ($resultat->ok || $resultat->status === 'disabled') {
            if ($resultat->status === 'disabled') {
                Log::warning('Mail de rendez-vous ignore : MailPulse desactive', [
                    'email' => $this->email,
                ]);
            }

            return;
        }

        throw new RuntimeException('MailPulse rdv: '.$resultat->status);
    }
}
