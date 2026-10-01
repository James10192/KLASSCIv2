<?php

namespace App\Mail\Support;

use App\Domain\Support\Services\AvertirDuRetourDuSupport;
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
        $sujet = AvertirDuRetourDuSupport::titre([
            'reference' => $this->donnees['reference'],
            'titre' => $this->donnees['titre'],
            'statut' => ['libelle' => $this->donnees['statut_libelle'], 'code' => $this->donnees['statut_code'] ?? null],
        ], (bool) $this->donnees['a_repondu']).' · '.$ecole['schoolName'];

        return $this->subject($sujet)
            ->view('esbtp.emails.support.reponse-du-support')
            ->with($this->donnees + $ecole);
    }
}
