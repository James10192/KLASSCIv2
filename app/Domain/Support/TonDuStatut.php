<?php

namespace App\Domain\Support;

/**
 * Le sens d'un statut de demande, tel que le Master le rend (`statut.code`).
 * Une seule table, lue par la pastille de l'écran des demandes ET par le
 * courriel de retour du support : le même ton aux deux, chaque surface
 * choisit sa couleur.
 *
 * `attention` attend l'école, `succes` est réglé, `neutre` est clos, le reste
 * est en cours (`info`).
 */
final class TonDuStatut
{
    public const ACTION_REQUISE = 'ACTION_REQUISE';

    public static function pour(?string $code): string
    {
        return match ($code) {
            self::ACTION_REQUISE => 'attention',
            'RESOLU' => 'succes',
            'FERME' => 'neutre',
            default => 'info',
        };
    }
}
