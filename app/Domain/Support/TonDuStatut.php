<?php

namespace App\Domain\Support;

/**
 * Le sens d'un statut de demande, tel que le Master le rend (`statut.code`).
 * Une seule table, lue par la pastille de l'écran des demandes ET par le
 * courriel de retour du support : la couleur dit la même chose aux deux.
 *
 * L'orange (`attention`) attend l'école, le vert (`succes`) est réglé, le gris
 * (`neutre`) est clos, le reste est en cours (`info`).
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
