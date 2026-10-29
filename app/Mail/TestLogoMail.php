<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestLogoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $logoPath;

    public function __construct($logoPath)
    {
        $this->logoPath = $logoPath;
    }

    public function build()
    {
        return $this->subject('Test Logo Email')
                    ->view('emails.test-logo')
                    ->with([
                        'schoolLogoPath' => $this->logoPath
                    ]);
    }
}
