<?php

namespace App\Mail\Parents;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaiementRejeteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = 'Paiement rejeté - ' . ($this->data['studentName'] ?? 'ESBTP');

        return $this->subject($subject)
                     ->view('esbtp.emails.parents.paiement-rejete')
                     ->with($this->data);
    }
}
