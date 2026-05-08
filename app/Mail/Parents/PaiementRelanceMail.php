<?php

namespace App\Mail\Parents;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaiementRelanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = 'Rappel de paiement - ' . ($this->data['studentName'] ?? 'votre enfant');

        return $this->subject($subject)
                     ->view('esbtp.emails.parents.paiement-relance')
                     ->with($this->data);
    }
}
