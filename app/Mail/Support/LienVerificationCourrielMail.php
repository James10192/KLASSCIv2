<?php

namespace App\Mail\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Le lien qui confirme l'adresse e-mail d'un compte du personnel. */
class LienVerificationCourrielMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $nom, public string $lien, public int $heures)
    {
    }

    public function build()
    {
        $ecole = IdentiteDeLEcole::donnees();

        return $this->subject('Confirmez votre adresse e-mail · '.$ecole['schoolName'])
            ->view('esbtp.emails.support.verification-courriel')
            ->with(['nom' => $this->nom, 'lien' => $this->lien, 'heures' => $this->heures] + $ecole);
    }
}
