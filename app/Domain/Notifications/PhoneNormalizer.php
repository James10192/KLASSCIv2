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
 *    l'instance, et on la mesure aux préfixes que l'instance déclare
 *    (`telephone_indicatif_pays`, `telephone_prefixes_mobiles` — le plan
 *    ivoirien à défaut, donc rien ne bouge là où rien n'est réglé).
 *
 * Ce qu'on ne fait JAMAIS : **déduire** le pays des chiffres. `0142345678` est
 * simultanément un mobile MTN Bénin valide et un mobile Moov Côte d'Ivoire
 * valide — les deux plans font dix chiffres et commencent par `01` depuis la
 * renumérotation béninoise du 30 novembre 2024. Aucune inférence n'est possible,
 * et les séries se réattribuent. Une table « pays → préfixes » serait donc
 * fausse le jour de son écriture, pas seulement plus tard.
 *
 * Que l'instance DÉCLARE ses préfixes est l'inverse exact : ce n'est plus le
 * code qui devine un pays, c'est l'école qui dit le sien.
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
     * Les préfixes qu'un numéro national peut porter ici.
     *
     * DÉCLARÉS par l'instance, jamais déduits d'un pays — c'est toute la
     * différence avec la liste blanche que cette classe refuse. Déduire
     * « Bénin → 01 » serait une inférence, et elle serait fausse dans l'autre
     * sens : `0142345678` est aussi un Moov ivoirien valide. Déclarer « ici, un
     * numéro national commence par 01 » est un fait que l'école connaît.
     *
     * Le défaut est le plan ivoirien depuis 2021. Laissé tel quel sur une
     * instance béninoise, le filtre accepterait `0707123456` — un préfixe que
     * l'ARCEP Bénin n'attribue à personne, puisque la renumérotation du
     * 30 novembre 2024 a préfixé `01` à TOUS les numéros du pays, fixes
     * compris. Le numéro produit serait injoignable, et le serait en silence :
     * c'est l'image en miroir du défaut que cette classe corrige.
     */
    private const PREFIXES_PAR_DEFAUT = '01,02,03,05,06,07,08,09';

    /**
     * La longueur d'un numéro national, dix chiffres — le zéro initial en fait
     * partie depuis le passage à dix chiffres (Côte d'Ivoire 2021, Bénin 2024).
     *
     * Volontairement NON configurable : les deux pays servis s'y rangent, et
     * une constante qu'on ne peut pas éprouver vieillit mal. Condition de
     * réouverture, nommable : la première instance dont le plan national n'est
     * pas à dix chiffres (le Sénégal en fait neuf). La dégradation serait alors
     * un refus visible, pas une corruption — d'où l'attente.
     */
    private const LONGUEUR_NATIONALE = 10;

    /** UIT-T E.164 §6.2 : quinze chiffres au plus, indicatif pays compris. */
    private const LONGUEUR_E164_MAX = 15;

    /** Plancher pratique : aucun plan national n'attribue en dessous. */
    private const LONGUEUR_E164_MIN = 8;

    public const CLE_INDICATIF = 'telephone_indicatif_pays';

    public const CLE_PREFIXES = 'telephone_prefixes_mobiles';

    /** @var (callable(string):?string)|null */
    private static $resolveurReglages = null;

    /**
     * Branche la lecture des deux réglages de l'instance.
     *
     * Appelé une fois au démarrage. La fermeture n'est évaluée qu'au premier
     * numéro analysé : une commande qui ne touche pas au téléphone ne paie
     * aucune lecture de réglage.
     *
     * **Aucune mémoïsation ici, et c'est délibéré.** `Setting::get()` met déjà
     * en cache, avec une invalidation qui marche (`Setting::saved` purge la
     * clé). Un second cache posé ici n'aurait, lui, aucune invalidation — et il
     * vivrait aussi longtemps que le processus. Or le travailleur de file
     * (`queue:work`, sans `--max-time`) est un démon, et c'est précisément lui
     * qui normalise les numéros des relances. Corriger l'indicatif d'une
     * instance aurait donc réparé l'écran tout en laissant les relances partir
     * avec l'ancien, jusqu'au prochain recyclage : exactement la corruption que
     * cette classe existe pour supprimer.
     *
     * @param  (callable(string):?string)|null  $resolveur
     */
    public static function definirResolveurReglages(?callable $resolveur): void
    {
        self::$resolveurReglages = $resolveur;
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
        $chiffres = preg_replace('/\D+/', '', (string) self::reglage(self::CLE_INDICATIF));

        return ($chiffres !== '' && $chiffres !== null && strlen($chiffres) <= 3)
            ? $chiffres
            : self::INDICATIF_PAR_DEFAUT;
    }

    /**
     * Les préfixes qu'un numéro national peut porter sur cette instance.
     *
     * @return list<string>
     */
    public static function prefixesNationaux(): array
    {
        $brut = (string) (self::reglage(self::CLE_PREFIXES) ?? '');
        $prefixes = [];

        foreach (preg_split('/[\s,;|]+/', $brut) ?: [] as $morceau) {
            $chiffres = preg_replace('/\D+/', '', $morceau);

            if ($chiffres !== '' && $chiffres !== null && strlen($chiffres) <= 4) {
                $prefixes[] = $chiffres;
            }
        }

        return $prefixes !== []
            ? $prefixes
            : explode(',', self::PREFIXES_PAR_DEFAUT);
    }

    private static function reglage(string $cle): ?string
    {
        return self::$resolveurReglages !== null ? (self::$resolveurReglages)($cle) : null;
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

        // Aucun indicatif pays ne commence par zéro : l'UIT-T E.164 les répartit
        // en neuf zones, de 1 à 9. Sans ce contrôle, `000707123456` produisait
        // `+0707123456` — une chaîne qui a la forme de l'E.164 et n'en est pas.
        if ($chiffres[0] === '0') {
            return null;
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

        foreach (self::prefixesNationaux() as $prefixe) {
            if (str_starts_with($national, $prefixe)) {
                return ['indicatif' => $indicatif, 'national' => $national];
            }
        }

        return null;
    }
}
