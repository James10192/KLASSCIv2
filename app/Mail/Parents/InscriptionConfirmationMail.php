<?php

namespace App\Mail\Parents;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InscriptionConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'Confirmation d\'inscription - ' . ($this->data['studentName'] ?? 'votre enfant');

        // Préparer le logo pour embed
        $logoPath = $this->data['schoolLogoPath'] ?? null;

        $mail = $this->subject($subject)
                     ->view('esbtp.emails.parents.inscription-confirmation')
                     ->with($this->data);

        // Attacher le logo comme embedded image si présent
        if ($logoPath && file_exists($logoPath)) {
            $this->data['schoolLogoEmbed'] = $logoPath;
        }

        return $mail;
    }
}
