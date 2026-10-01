<?php

namespace App\Mail\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Le support KLASSCI a repondu a une demande, ou l'a resolue / fermee.
 * N'est envoye qu'a une adresse verifiee (AdresseJoignable).
 */
class ReponseDuSupportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{reference: string, titre: ?string, nom: string, a_repondu: bool, statut_libelle: ?string, statut_code: ?string, extrait: ?string, lien: string}  $donnees
     */
    public function __construct(public array $donnees)
    {
    }

    public function build()
    {
        $ecole = IdentiteDeLEcole::donnees();
        $sujet = ($this->donnees['a_repondu'] ? 'Le support a répondu' : 'Votre demande est '.mb_strtolower((string) $this->donnees['statut_libelle']))
            .' · '.$this->donnees['reference'].' · '.$ecole['schoolName'];

        return $this->subject($sujet)
            ->view('esbtp.emails.support.reponse-du-support')
            ->with($this->donnees + $ecole);
    }
}
