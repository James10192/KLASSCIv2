<?php

namespace App\Support\Lms;

use App\Models\User;

/**
 * Le jeton SERVEUR du LMS : un jeton Sanctum porte par le compte technique
 * « Service LMS » de l'ecole, utilise par les taches de fond du LMS
 * (synchronisation, remontees), distinct des jetons que le login LMS donne a
 * chaque utilisateur (droit `lms:access`).
 *
 * On lit les droits DANS LA LISTE du jeton, pas par `tokenCan()` : un jeton
 * portant `*` repond oui a tout, et un jeton CLI ou utilisateur ne doit jamais
 * passer pour le serveur du LMS par ce biais.
 *
 * Voir docs/api/LMS_JETON_SERVEUR.md.
 */
final class JetonServeurLms
{
    /** Marque le jeton comme jeton serveur. Porte par tous. */
    public const SERVEUR = 'lms:serveur';

    public const LECTURE = 'lms:lecture';

    public const NOTES = 'lms:notes';

    public const PRESENCES = 'lms:presences';

    /** Droits qu'un jeton serveur peut recevoir, en plus de la marque. */
    public const DROITS = [self::LECTURE, self::NOTES, self::PRESENCES];

    /** Identifiant du compte technique, unique par ecole. */
    public const COMPTE = 'service-lms';

    public static function estServeur(?User $utilisateur): bool
    {
        return in_array(self::SERVEUR, self::droitsDuJeton($utilisateur), true);
    }

    /** Le jeton est un jeton serveur ET porte ce droit. */
    public static function peut(?User $utilisateur, string $droit): bool
    {
        $droits = self::droitsDuJeton($utilisateur);

        return in_array(self::SERVEUR, $droits, true) && in_array($droit, $droits, true);
    }

    /** @return array<int, string> */
    private static function droitsDuJeton(?User $utilisateur): array
    {
        $jeton = $utilisateur?->currentAccessToken();

        // Une session web porte un TransientToken, sans liste de droits.
        return is_object($jeton) && isset($jeton->abilities) && is_array($jeton->abilities)
            ? $jeton->abilities
            : [];
    }
}
