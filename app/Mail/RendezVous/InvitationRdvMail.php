<?php

namespace App\Mail\RendezVous;

use Illuminate\Mail\Mailable;

class InvitationRdvMail extends Mailable
{
    public function __construct(public readonly array $data)
    {
    }

    public function build(): self
    {
        return $this->subject($this->data['sujet'] ?? 'Prenez rendez-vous')
            ->view('emails.rendez-vous.invitation')
            ->with($this->data);
    }
}
