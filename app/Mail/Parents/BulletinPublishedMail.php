<?php

namespace App\Mail\Parents;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BulletinPublishedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = 'Bulletin disponible - ' . ($this->data['studentName'] ?? 'ESBTP');

        return $this->subject($subject)
                     ->view('esbtp.emails.parents.bulletin-published')
                     ->with($this->data);
    }
}
