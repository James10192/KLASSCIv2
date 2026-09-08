<?php

namespace App\Mail\RendezVous;

use Illuminate\Mail\Mailable;

class ConfirmationRdvMail extends Mailable
{
    public function __construct(public readonly array $data)
    {
    }

    public function build(): self
    {
        return $this->subject($this->data['sujet'] ?? 'Votre rendez-vous')
            ->view('emails.rendez-vous.confirmation')
            ->with($this->data);
    }
}
