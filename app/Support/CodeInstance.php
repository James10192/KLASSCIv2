<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Code de l'instance (l'etablissement), source unique.
 *
 * KLASSCI est multi-instance : chaque ecole a sa base et son `.env`, et son code
 * identifie l'etablissement dans les numeros de pieces officielles (releve de
 * notes, proces-verbal de jury, convocation d'examen). Ces numeros sont figes
 * dans le document a l'emission : un code faux ne se rattrape pas.
 *
 * Deux regles tenues ici, et nulle part ailleurs :
 *
 *  1. La lecture passe par la configuration (`config('app.tenant_code')`), jamais
 *     par `env()`. Sous `config:cache` — l'etat normal en production — `env()`
 *     renvoie null hors des fichiers de configuration : un repli ecrit a cote de
 *     l'appel s'appliquerait donc systematiquement au lieu d'etre un cas de bord.
 *
 *  2. Aucun code d'etablissement n'est ecrit en dur. `config/app.php` retombe sur
 *     la valeur neutre « default » quand `TENANT_CODE` n'est pas renseigne : on la
 *     traite comme une absence de configuration, et une piece officielle refuse
 *     alors d'etre emise plutot que de porter un identifiant devine.
 */
final class CodeInstance
{
    /**
     * Valeur de repli de config/app.php : elle signale une absence de reglage,
     * elle ne designe aucun etablissement.
     */
    private const NON_CONFIGURE = 'DEFAULT';

    /**
     * Code normalise, ou null si l'instance n'est pas configuree.
     */
    public static function resoudre(): ?string
    {
        $code = strtoupper(trim((string) config('app.tenant_code')));

        if ($code === '' || $code === self::NON_CONFIGURE) {
            return null;
        }

        return $code;
    }

    /**
     * Code exige pour numeroter une piece officielle.
     *
     * @param  string  $piece  Libelle de la piece, pour que le message dise quoi debloquer.
     *
     * @throws RuntimeException si l'instance n'est pas configuree.
     */
    public static function exigerPourPieceOfficielle(string $piece): string
    {
        $code = self::resoudre();

        if ($code === null) {
            throw new RuntimeException(sprintf(
                "Le code de l'établissement n'est pas configuré : impossible de numéroter %s. "
                ."Renseignez TENANT_CODE dans le fichier .env de l'instance, puis videz le cache de configuration.",
                $piece
            ));
        }

        return $code;
    }
}
