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
 *
 * Toutes les cles gerables ne sont pas des secrets. Un identifiant se controle
 * par sa FORME, pas par sa longueur — « esbtp-abidjan » pose sur une instance
 * qui n'est pas Abidjan fait exactement la bonne longueur — et il se relit sans
 * danger, alors qu'un secret ne ressort jamais. D'ou les deux attributs
 * supplementaires : le format attendu, et le fait d'etre secret ou non.
 */
class CleEnvAutorisee
{
    /**
     * @var array<string, array{longueur_min: int, format: ?string, format_lisible: ?string, secrete: bool, description: string}>
     */
    private const AUTORISEES = [
        'REINSCRIPTION_PORTAL_SECRET' => [
            'longueur_min' => 32,
            'format' => null,
            'format_lisible' => null,
            'secrete' => true,
            'description' => "Secret partage avec le portail public de reinscription sur klassci.com. Doit etre IDENTIQUE a REINSCRIPTION_SECRET_<CODE> cote Vercel.",
        ],
        'TENANT_CODE' => [
            'longueur_min' => 3,
            // Minuscules, chiffres et tirets : la forme des codes de la flotte
            // (« usat », « esbtp-abidjan »). CoherenceIdentiteInstance suppose
            // la meme forme quand elle deduit le nom de base en remplacant les
            // tirets par des tirets bas.
            'format' => '/^[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])$/',
            'format_lisible' => 'minuscules, chiffres et tirets, de 3 a 32 caracteres, sans tiret en debut ni en fin',
            'secrete' => false,
            'description' => "Code de l'instance. Il designe les quotas que le paywall lit chez adminKlassci et l'identite servie au site public : une instance qui porte le code d'une autre lit les limites de celle-la. A verifier avec « php artisan tenant:verifier-identite ».",
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

    /**
     * Une cle sans format declare accepte toute valeur : c'est le cas des
     * secrets, dont seule la longueur se controle.
     */
    public static function respecteFormat(string $cle, string $valeur): bool
    {
        $format = self::AUTORISEES[$cle]['format'] ?? null;

        return $format === null || preg_match($format, $valeur) === 1;
    }

    public static function formatLisible(string $cle): ?string
    {
        return self::AUTORISEES[$cle]['format_lisible'] ?? null;
    }

    /**
     * Une valeur secrete ne ressort jamais, meme pour son proprietaire : on
     * n'en publie qu'une empreinte. Un identifiant, lui, doit se relire — c'est
     * precisement en le lisant qu'on decouvre qu'une instance se declare sous
     * le code d'une autre.
     */
    public static function estSecrete(string $cle): bool
    {
        return self::AUTORISEES[$cle]['secrete'] ?? true;
    }
}
