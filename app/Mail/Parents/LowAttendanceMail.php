<?php

namespace App\Mail\Parents;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowAttendanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = 'Alerte taux de présence - ' . ($this->data['studentName'] ?? 'ESBTP');

        return $this->subject($subject)
                     ->view('esbtp.emails.parents.low-attendance')
                     ->with($this->data);
    }
}
