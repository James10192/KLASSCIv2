<?php

namespace App\Services\Deployment;

/**
 * Les seules cles de .env que le CLI a le droit d'ecrire.
 *
 * Cette liste est le coeur de la surete du dispositif, et elle est courte
 * exprès. Ouvrir l'ecriture a une cle arbitraire reviendrait a donner, a qui
 * detient un jeton CLI, le controle complet de l'instance :
 *
 *   DB_HOST      detourne la base vers une machine tierce
 *   APP_KEY      rend illisible tout ce qui est chiffre en base
 *   MAIL_*       intercepte les reinitialisations de mot de passe
 *   APP_DEBUG    expose la configuration et les traces a tout visiteur
 *
 * Une cle ne s'ajoute donc pas au vol : c'est une modification de code, revue
 * et deployee comme le reste. Chaque entree porte la longueur minimale
 * attendue, parce qu'un secret trop court accepte en silence est un secret
 * qu'on croit poser sans l'avoir pose.
 */
class CleEnvAutorisee
{
    /**
     * @var array<string, array{longueur_min: int, description: string}>
     */
    private const AUTORISEES = [
        'REINSCRIPTION_PORTAL_SECRET' => [
            'longueur_min' => 32,
            'description' => "Secret partage avec le portail public de reinscription sur klassci.com. Doit etre IDENTIQUE a REINSCRIPTION_SECRET_<CODE> cote Vercel.",
        ],
    ];

    /** @return list<string> */
    public static function toutes(): array
    {
        return array_keys(self::AUTORISEES);
    }

    public static function estAutorisee(string $cle): bool
    {
        return array_key_exists($cle, self::AUTORISEES);
    }

    public static function longueurMinimale(string $cle): int
    {
        return self::AUTORISEES[$cle]['longueur_min'] ?? 0;
    }

    public static function description(string $cle): string
    {
        return self::AUTORISEES[$cle]['description'] ?? '';
    }
}
