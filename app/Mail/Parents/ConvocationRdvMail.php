<?php

namespace App\Mail\Parents;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConvocationRdvMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, mixed> $data */
    public function __construct(public array $data, public readonly string $pdfBinaire)
    {
    }

    public function build(): self
    {
        $mail = $this->subject($this->data['sujet'] ?? 'Convocation au guichet')
            ->view('esbtp.emails.parents.rendez-vous-convocation')
            ->with($this->data);

        if ($this->pdfBinaire !== '') {
            $mail->attachData($this->pdfBinaire, 'convocation-rendez-vous.pdf', [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
