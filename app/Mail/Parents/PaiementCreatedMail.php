<?php

namespace App\Mail\Parents;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaiementCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = 'Paiement en attente - ' . ($this->data['studentName'] ?? 'votre enfant');

        return $this->subject($subject)
                     ->view('esbtp.emails.parents.paiement-created')
                     ->with($this->data);
    }
}
