<?php

namespace App\Mail\Support;

use App\Helpers\SettingsHelper;

/**
 * Le nom de l'établissement, pour le SUJET des courriels KLASSCI Care : le
 * sujet se pose avant le rendu, donc avant les composeurs de vue.
 *
 * Tout le reste (logo par URL publique absolue, couleurs, adresse,
 * coordonnées) est posé par les composeurs de `esbtp.emails.*`
 * (CouleursDesCourrielsParents, IdentiteDesCourriels) et rendu par le gabarit
 * commun `esbtp.emails.layout`. Le logo n'est pas une pièce intégrée : les
 * courriels partent par MailPulse, qui ne transporte que du HTML.
 */
final class IdentiteDeLEcole
{
    /** @return array{schoolName: string} */
    public static function donnees(): array
    {
        $nom = trim((string) (SettingsHelper::getSchoolInfo()['name'] ?? ''));

        return ['schoolName' => $nom !== '' ? $nom : (string) config('app.name')];
    }
}
