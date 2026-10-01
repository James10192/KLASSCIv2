<?php

namespace App\Mail\Support;

use App\Mail\Transport\MailPulseTransport;
use Illuminate\Mail\Message;

/**
 * La source du logo de l'école dans un courriel, partagée par le gabarit
 * commun (`esbtp.emails.layout`) et celui des avis aux parents
 * (`esbtp.emails.parents.recu`).
 *
 * Par SMTP, le logo part en pièce intégrée, que les messageries affichent sans
 * demander. Par MailPulse (mailer comme API), seule l'URL publique existe : une
 * pièce intégrée y serait retirée.
 *
 * Une classe et non un partiel Blade : un partiel rend du texte, il ne peut pas
 * rendre au gabarit la valeur dont celui-ci a besoin pour choisir entre l'image
 * et l'initiale.
 */
final class SourceDuLogo
{
    public static function pour(mixed $message, ?string $cheminLogo, ?string $urlLogo): ?string
    {
        if (! empty($cheminLogo) && $message instanceof Message && is_file($cheminLogo)
            && ! MailPulseTransport::actif()) {
            return $message->embed($cheminLogo);
        }

        return ! empty($urlLogo) ? $urlLogo : null;
    }
}
