<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Rappel des pieces du dossier d'inscription qui restent a deposer.
 *
 * Volontairement distinct du rappel de paiement : le destinataire n'a rien a
 * payer, il a des documents a apporter au secretariat.
 */
class PiecesManquantesRelanceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array>  $manquantes  libelle + exemplaires attendus
     */
    public function __construct(
        public readonly string $etudiantNom,
        public readonly array $manquantes,
        public readonly ?string $anneeNom = null,
        public readonly ?string $ecoleNom = null,
    ) {}

    public function build()
    {
        return $this
            ->subject('Pieces manquantes a votre dossier d\'inscription')
            ->view('esbtp.emails.pieces-manquantes')
            ->with([
                'etudiantNom' => $this->etudiantNom,
                'manquantes' => $this->manquantes,
                'anneeNom' => $this->anneeNom,
                'ecoleNom' => $this->ecoleNom,
            ]);
    }
}
