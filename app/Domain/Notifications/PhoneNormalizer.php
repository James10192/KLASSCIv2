<?php

namespace App\Domain\Notifications;

/**
 * Met un numéro de téléphone sous sa forme canonique E.164.
 *
 * Deux écritures, deux traitements — et c'est toute la règle.
 *
 * 1. **L'écriture internationale** (`+…`, `00…`) porte son indicatif pays.
 *    On la croit et on la conserve. C'est la seule façon d'accepter un numéro
 *    d'un pays que ce code ne connaît pas, et il y en a : `ucao-benin` est une
 *    instance béninoise, et `+229 01 42 34 56 78` était refusé jusqu'ici.
 *
 * 2. **L'écriture nationale** ne porte aucun indicatif : on lui appose celui de
 *    l'instance (réglage `telephone_indicatif_pays`, `225` à défaut).
 *
 * Ce qu'on ne fait JAMAIS : deviner le pays depuis les chiffres. `0142345678`
 * est simultanément un mobile MTN Bénin valide et un mobile Moov Côte d'Ivoire
 * valide — les deux plans font dix chiffres et commencent par `01` depuis la
 * renumérotation béninoise du 30 novembre 2024. Aucune inférence n'est possible,
 * et les séries se réattribuent. Une liste blanche de préfixes par pays serait
 * donc fausse le jour de son écriture, pas seulement plus tard.
 *
 * La contrepartie assumée : hors de l'indicatif de l'instance, on ne valide que
 * la FORME (UIT-T E.164 §6.2 : quinze chiffres au plus, indicatif compris). On
 * ne sait ni si le numéro existe, ni où couper son indicatif — découper
 * demanderait la table des indicatifs mondiaux, une dette pour un affichage.
 *
 * Pure compute — pas de dépendance Laravel, pour que le test s'exécute sans
 * application. D'où le résolveur d'indicatif plutôt qu'un appel direct au
 * réglage : c'est `AppServiceProvider` qui le branche.
 */
final class PhoneNormalizer
{
    /**
     * L'indicatif servi tant que personne n'en configure un.
     *
     * Six des huit instances sont ivoiriennes, et leur forme canonique doit
     * rester octet pour octet `+225` + dix chiffres : `esbtp_candidatures`
     * porte un index UNIQUE dessus, et toute la détection de doublon en dépend.
     */
    private const INDICATIF_PAR_DEFAUT = '225';

    /**
     * La forme d'une saisie nationale, telle que le plan ivoirien la fixe
     * depuis 2021 : dix chiffres, dont le zéro initial fait partie.
     *
     * Ces deux constantes décrivent un plan de numérotation, pas une liste de
     * pays — et elles ne doivent PAS le devenir. Le plan béninois s'y range :
     * dix chiffres depuis le 30 novembre 2024, et tous ses mobiles commencent
     * par `01`. Le filtre y est simplement plus lâche (un fixe béninois porte
     * aussi `01`, il passe donc) — ce qui laisse entrer un numéro joignable, pas
     * un numéro corrompu.
     */
    private const LONGUEUR_NATIONALE = 10;

    private const PREFIXES_MOBILES = '/^(01|02|03|05|06|07|08|09)/';

    /** UIT-T E.164 §6.2 : quinze chiffres au plus, indicatif pays compris. */
    private const LONGUEUR_E164_MAX = 15;

    /** Plancher pratique : aucun plan national n'attribue en dessous. */
    private const LONGUEUR_E164_MIN = 8;

    /** @var (callable():?string)|null */
    private static $resolveurIndicatif = null;

    private static ?string $indicatifMemoise = null;

    /**
     * Branche la lecture de l'indicatif de l'instance.
     *
     * Appelé une fois au démarrage. La fermeture n'est évaluée qu'au premier
     * numéro analysé : une commande qui ne touche pas au téléphone ne paie
     * aucune lecture de réglage.
     *
     * @param  (callable():?string)|null  $resolveur
     */
    public static function definirResolveurIndicatif(?callable $resolveur): void
    {
        self::$resolveurIndicatif = $resolveur;
        self::$indicatifMemoise = null;
    }

    /**
     * L'indicatif pays apposé aux saisies nationales de cette instance.
     *
     * Une valeur illisible (vide, lettres, plus de trois chiffres) retombe sur
     * le défaut plutôt que de produire des numéros que personne ne pourra
     * rattraper : un réglage mal saisi ne doit pas corrompre une base.
     */
    public static function indicatifNationalParDefaut(): string
    {
        if (self::$indicatifMemoise !== null) {
            return self::$indicatifMemoise;
        }

        $brut = self::$resolveurIndicatif !== null ? (self::$resolveurIndicatif)() : null;
        $chiffres = preg_replace('/\D+/', '', (string) $brut);

        return self::$indicatifMemoise = ($chiffres !== '' && $chiffres !== null && strlen($chiffres) <= 3)
            ? $chiffres
            : self::INDICATIF_PAR_DEFAUT;
    }

    /**
     * Tente de normaliser un numéro brut. Retourne null si invalide.
     */
    public static function toE164(?string $raw): ?string
    {
        $parties = self::decomposer($raw);

        if ($parties === null) {
            return null;
        }

        return '+'.($parties['indicatif'] ?? '').$parties['national'];
    }

    /**
     * Sépare l'indicatif pays du reste — quand on peut le savoir.
     *
     * `indicatif` vaut null pour un numéro écrit avec un indicatif que cette
     * instance ne connaît pas : on a alors conservé l'écriture entière dans
     * `national`, et `'+'.$national` reste la forme canonique. C'est ce que
     * `PhoneFormatter` lit pour ne pas grouper un numéro au mauvais endroit.
     *
     * @return array{indicatif: ?string, national: string}|null
     */
    public static function decomposer(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $brut = trim($raw);
        $chiffres = preg_replace('/\D+/', '', $brut);

        if ($chiffres === '' || $chiffres === null) {
            return null;
        }

        $indicatifLocal = self::indicatifNationalParDefaut();

        // Le « + » de l'écriture internationale, ou le « 00 » qui en tient lieu
        // depuis un clavier qui n'a pas le premier.
        $estInternational = str_starts_with($brut, '+') || str_starts_with($chiffres, '00');

        if (! $estInternational) {
            // Notre propre forme canonique écrite sans le « + » : c'est ce que
            // rend `toWhatsAppId()`, et ce que certains stockages ont conservé.
            //
            // Admise UNIQUEMENT pour notre indicatif et à la longueur exacte.
            // L'ouvrir aux autres pays rendrait `33612345678` indistinguable
            // d'une saisie nationale — et il n'y a aucun « + » pour trancher.
            if (strlen($chiffres) === strlen($indicatifLocal) + self::LONGUEUR_NATIONALE
                && str_starts_with($chiffres, $indicatifLocal)) {
                return self::validerSaisieNationale(
                    substr($chiffres, strlen($indicatifLocal)),
                    $indicatifLocal
                );
            }

            return self::validerSaisieNationale($chiffres, $indicatifLocal);
        }

        if (str_starts_with($chiffres, '00')) {
            $chiffres = substr($chiffres, 2);
        }

        if ($chiffres === '') {
            return null;
        }

        // Notre propre pays : on connaît son plan, donc on l'applique. Sans
        // cela `+225 27 20 30 10 20` (un fixe) et `+225 07 07 12 34` (trop
        // court) seraient acceptés alors qu'ils étaient refusés jusqu'ici.
        if (str_starts_with($chiffres, $indicatifLocal)) {
            return self::validerSaisieNationale(
                substr($chiffres, strlen($indicatifLocal)),
                $indicatifLocal
            );
        }

        if (strlen($chiffres) < self::LONGUEUR_E164_MIN || strlen($chiffres) > self::LONGUEUR_E164_MAX) {
            return null;
        }

        return ['indicatif' => null, 'national' => $chiffres];
    }

    /**
     * Le numéro est-il un mobile du pays de cette instance ?
     *
     * Distinct de `isValid()`, qui accepte désormais tout numéro international
     * bien formé. Sert là où la portée étroite est une DÉCISION et non un
     * effet de bord — le portail de candidature, dont le téléphone est la clé
     * d'unicité (voir `PortailCandidatureRequest`).
     */
    public static function estMobileNational(?string $raw): bool
    {
        $parties = self::decomposer($raw);

        return $parties !== null && $parties['indicatif'] === self::indicatifNationalParDefaut();
    }

    /**
     * Format E.164 sans le `+` (utile pour wa.me/22507XXXXXXXX).
     */
    public static function toWhatsAppId(?string $raw): ?string
    {
        $e164 = self::toE164($raw);

        return $e164 === null ? null : substr($e164, 1);
    }

    public static function isValid(?string $raw): bool
    {
        return self::toE164($raw) !== null;
    }

    /**
     * @return array{indicatif: string, national: string}|null
     */
    private static function validerSaisieNationale(string $national, string $indicatif): ?array
    {
        if (strlen($national) !== self::LONGUEUR_NATIONALE) {
            return null;
        }

        if (! preg_match(self::PREFIXES_MOBILES, $national)) {
            return null;
        }

        return ['indicatif' => $indicatif, 'national' => $national];
    }
}
