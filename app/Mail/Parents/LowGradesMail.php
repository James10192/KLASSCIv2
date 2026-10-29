<?php

namespace App\Mail\Parents;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowGradesMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = 'Alerte performance académique - ' . ($this->data['studentName'] ?? 'votre enfant');

        return $this->subject($subject)
                     ->view('esbtp.emails.parents.low-grades')
                     ->with($this->data);
    }
}
