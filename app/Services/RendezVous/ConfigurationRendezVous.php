<?php

namespace App\Services\RendezVous;

use App\Helpers\SettingsHelper;
use App\Services\Reinscription\PortailReinscriptionService;
use Carbon\CarbonImmutable;

/**
 * Ce que l'ecole a regle pour ses rendez-vous de guichet, relu et verifie.
 *
 * Cinq parametres ont ete demandes : l'heure d'ouverture, les heures de pause,
 * l'heure de fermeture, la duree approximative par etudiant, et le nombre de
 * personnes recues dans la journee. Ce dernier n'est PAS saisi ici, et c'est
 * delibere.
 *
 * Les quatre premiers determinent deja le nombre de creneaux d'une journee. Le
 * saisir en plus donnerait deux chiffres pour une seule question, et rien ne
 * dirait lequel gagne quand ils divergent. Or une ecole qui annonce « soixante
 * par jour » sur une plage qui n'en produit que vingt-huit ne se trompe pas :
 * elle decrit plusieurs guichets tenus en parallele, sans le dire.
 *
 * C'est donc cela qu'on lui demande — `capacite_par_creneau`, combien de
 * familles a la fois — et son nombre par jour lui est rendu, calcule, sous les
 * champs. Il gagne au passage d'etre exact : il ne peut plus contredire la
 * grille dont il decoule.
 *
 * La forme est celle que le depot emploie deja pour toute capacite :
 * ESBTPClasse::getPlacesDisponiblesAttribute() et ESBTPFraisOption comparent
 * une capacite DECLAREE sur l'objet reservable a une occupation COMPTEE. Ici
 * l'objet reservable est le creneau, pas la journee.
 *
 * Deux parametres s'ajoutent a la liste, sans lesquels la grille n'aurait pas
 * de bornes : le dernier jour de la campagne, et les jours de la semaine ou le
 * guichet ouvre. Le premier jour, lui, existait deja — c'est
 * `inscriptions.physiques.debut`, la date que le portail publie aujourd'hui
 * pour repondre a « je viens quand ? ». On la reutilise plutot que de la
 * doubler : deux dates pour une seule rentree finiraient par diverger, et la
 * famille lirait deux instructions contradictoires sur le meme ecran.
 */
final class ConfigurationRendezVous
{
    public const REGLAGE_ACTIF = 'inscriptions.rdv.enabled';
    public const REGLAGE_DERNIER_JOUR = 'inscriptions.rdv.dernier_jour';
    public const REGLAGE_JOURS_OUVERTS = 'inscriptions.rdv.jours_ouverts';
    public const REGLAGE_OUVERTURE = 'inscriptions.rdv.heure_ouverture';
    public const REGLAGE_FERMETURE = 'inscriptions.rdv.heure_fermeture';
    public const REGLAGE_PAUSE_DEBUT = 'inscriptions.rdv.pause_debut';
    public const REGLAGE_PAUSE_FIN = 'inscriptions.rdv.pause_fin';
    public const REGLAGE_DUREE = 'inscriptions.rdv.duree_minutes';
    public const REGLAGE_CAPACITE = 'inscriptions.rdv.capacite_par_creneau';

    /** Le premier jour de la campagne : le reglage qui existait avant les rendez-vous. */
    public const REGLAGE_PREMIER_JOUR = 'inscriptions.physiques.debut';

    /**
     * Bornes du LOGICIEL, pas de l'ecole — elles ne se reglent donc pas.
     *
     * La duree minimale n'est pas une opinion sur le metier : le generateur
     * avance par increments plutot que par division, precisement pour ne pas
     * reproduire la DivisionByZeroError deja rencontree dans ce depot. Une
     * duree nulle ne ferait alors pas planter la boucle, elle la ferait tourner
     * sans fin — pire, parce que muet. La borne est donc ici, a la lecture, et
     * non sous forme de garde dans le generateur ou on finirait par l'oublier.
     *
     * La capacite minimale a une raison jumelle. ESBTPFraisOption traite
     * `capacity_limit = 0` de trois facons contradictoires dans un meme
     * fichier, et dans deux chemins sur trois « ferme » y devient « illimite ».
     * Zero n'est pas une capacite : une ecole qui ne recoit personne eteint
     * l'interrupteur ou retire le jour, elle n'ecrit pas zero.
     */
    public const DUREE_MINIMALE = 5;
    public const DUREE_MAXIMALE = 480;
    public const CAPACITE_MINIMALE = 1;
    public const CAPACITE_MAXIMALE = 50;

    /**
     * @param  list<int>     $joursOuverts       jours ISO ouverts (1 = lundi ... 7 = dimanche)
     * @param  ?int          $ouverture          minutes depuis minuit
     * @param  ?int          $fermeture          minutes depuis minuit
     * @param  list<string>  $problemes          ce qui empeche d'ouvrir, en francais, pour l'ecran
     */
    private function __construct(
        public readonly bool $actif,
        public readonly ?CarbonImmutable $premierJour,
        public readonly ?CarbonImmutable $dernierJour,
        public readonly array $joursOuverts,
        public readonly ?int $ouverture,
        public readonly ?int $fermeture,
        public readonly ?int $pauseDebut,
        public readonly ?int $pauseFin,
        public readonly int $dureeMinutes,
        public readonly int $capaciteParCreneau,
        public readonly array $problemes,
    ) {}

    /**
     * La configuration telle qu'elle est enregistree sur l'instance.
     *
     * Cette methode ne fait que RASSEMBLER : toute la verification vit dans
     * `depuisLesValeurs()`, qui ne touche ni base ni cache. C'est ce qui rend
     * les bornes, le zero et la pause verifiables sans base de donnees — et une
     * regle qu'on ne peut pas eprouver sans base finit par n'etre eprouvee que
     * par les ecoles.
     */
    public static function depuisLesReglages(): self
    {
        $cles = [
            self::REGLAGE_ACTIF,
            self::REGLAGE_PREMIER_JOUR,
            self::REGLAGE_DERNIER_JOUR,
            self::REGLAGE_JOURS_OUVERTS,
            self::REGLAGE_OUVERTURE,
            self::REGLAGE_FERMETURE,
            self::REGLAGE_PAUSE_DEBUT,
            self::REGLAGE_PAUSE_FIN,
            self::REGLAGE_DUREE,
            self::REGLAGE_CAPACITE,
        ];

        $valeurs = [];

        foreach ($cles as $cle) {
            $valeurs[$cle] = SettingsHelper::get($cle, '');
        }

        return self::depuisLesValeurs($valeurs);
    }

    /**
     * @param  array<string, mixed>  $valeurs  indexe par cle de reglage
     */
    public static function depuisLesValeurs(array $valeurs): self
    {
        $problemes = [];

        $actif = filter_var(self::brut($valeurs, self::REGLAGE_ACTIF), FILTER_VALIDATE_BOOLEAN);

        $premierJour = self::jour($valeurs, self::REGLAGE_PREMIER_JOUR, 'le premier jour de reception', $problemes);
        $dernierJour = self::jour($valeurs, self::REGLAGE_DERNIER_JOUR, 'le dernier jour de reception', $problemes);

        if ($premierJour !== null && $dernierJour !== null && $dernierJour->lt($premierJour)) {
            $problemes[] = "Le dernier jour de reception precede le premier.";
        }

        $joursOuverts = self::joursOuverts($valeurs, $problemes);

        $ouverture = self::heure($valeurs, self::REGLAGE_OUVERTURE, "l'heure d'ouverture du guichet", $problemes);
        $fermeture = self::heure($valeurs, self::REGLAGE_FERMETURE, "l'heure de fermeture du guichet", $problemes);

        if ($ouverture !== null && $fermeture !== null && $fermeture <= $ouverture) {
            $problemes[] = "Le guichet fermerait avant d'avoir ouvert.";
        }

        [$pauseDebut, $pauseFin] = self::pause($valeurs, $ouverture, $fermeture, $problemes);

        $duree = self::entier(
            $valeurs,
            self::REGLAGE_DUREE,
            self::DUREE_MINIMALE,
            self::DUREE_MAXIMALE,
            "La duree par etudiant doit etre comprise entre %d et %d minutes.",
            $problemes,
        );

        $capacite = self::entier(
            $valeurs,
            self::REGLAGE_CAPACITE,
            self::CAPACITE_MINIMALE,
            self::CAPACITE_MAXIMALE,
            "Le nombre de familles recues en meme temps doit etre compris entre %d et %d.",
            $problemes,
        );

        // Une plage trop courte pour un seul creneau n'est pas une plage vide :
        // c'est une configuration qui se croit bonne. On le dit ici plutot que
        // de laisser le generateur rendre un tableau vide, que la vitrine
        // afficherait comme « aucune disponibilite » — la famille conclurait
        // « c'est complet », et l'ecole ne saurait jamais pourquoi.
        if ($ouverture !== null && $fermeture !== null && $duree !== null) {
            $utiles = ($fermeture - $ouverture) - self::minutesDePause($ouverture, $fermeture, $pauseDebut, $pauseFin);

            if ($utiles < $duree) {
                $problemes[] = "La plage d'ouverture, une fois la pause retiree, ne laisse la place a aucun creneau.";
            }
        }

        return new self(
            actif: $actif,
            premierJour: $premierJour,
            dernierJour: $dernierJour,
            joursOuverts: $joursOuverts,
            ouverture: $ouverture,
            fermeture: $fermeture,
            pauseDebut: $pauseDebut,
            pauseFin: $pauseFin,
            dureeMinutes: $duree ?? self::DUREE_MINIMALE,
            capaciteParCreneau: $capacite ?? self::CAPACITE_MINIMALE,
            problemes: $problemes,
        );
    }

    /**
     * La prise de rendez-vous peut-elle etre proposee ?
     *
     * L'interrupteur ET une configuration complete. Une configuration qu'on ne
     * sait pas lire n'est pas une configuration absente : c'est la meme regle
     * que la fenetre saisonniere du portail, qui ferme le canal sur une borne
     * illisible plutot que de l'ouvrir hors saison sur une faute de frappe.
     */
    public function estUtilisable(): bool
    {
        return $this->actif && $this->problemes === [];
    }

    /**
     * Complete, mais pas forcement allumee.
     *
     * L'ecran de reglage s'en sert pour montrer la grille pendant que l'ecole
     * la met au point, avant qu'elle ouvre le canal.
     */
    public function estComplete(): bool
    {
        return $this->problemes === [];
    }

    /** Ce jour tombe-t-il dans la campagne, un jour ou le guichet ouvre ? */
    public function jourOuvert(CarbonImmutable $jour): bool
    {
        if (! $this->estComplete()) {
            return false;
        }

        $jour = $jour->startOfDay();

        return $jour->gte($this->premierJour)
            && $jour->lte($this->dernierJour)
            && in_array($jour->dayOfWeekIso, $this->joursOuverts, true);
    }

    /** Minutes reellement disponibles dans une journee, pause deduite. */
    public function minutesUtiles(): int
    {
        if (! $this->estComplete()) {
            return 0;
        }

        return ($this->fermeture - $this->ouverture)
            - self::minutesDePause($this->ouverture, $this->fermeture, $this->pauseDebut, $this->pauseFin);
    }

    // -----------------------------------------------------------------
    // Lecture des reglages
    // -----------------------------------------------------------------

    /**
     * Une valeur de reglage, ramenee a une chaine taillee.
     *
     * `Setting::get()` rend une chaine, mais un appelant de test passe volontiers
     * un entier ou un booleen. On normalise ici plutot que dans chaque
     * verificateur : c'est le genre de conversion que l'on finit par ecrire
     * differemment a cinq endroits.
     *
     * @param  array<string, mixed>  $valeurs
     */
    private static function brut(array $valeurs, string $cle): string
    {
        $valeur = $valeurs[$cle] ?? '';

        if (is_bool($valeur)) {
            return $valeur ? '1' : '0';
        }

        return is_scalar($valeur) ? trim((string) $valeur) : '';
    }

    /**
     * @param  list<string>  $problemes
     */
    private static function jour(array $valeurs, string $cle, string $libelle, array &$problemes): ?CarbonImmutable
    {
        $brut = self::brut($valeurs, $cle);

        if ($brut === '') {
            $problemes[] = "Renseignez {$libelle}.";

            return null;
        }

        // Le meme analyseur que les bornes de la fenetre saisonniere, et pour la
        // meme raison : Carbon::createFromFormat ne leve pas sur une date qui
        // deborde, elle reporte. « 2026-02-31 » deviendrait le 3 mars, et la
        // campagne se decalerait sans que rien ne le dise.
        $date = PortailReinscriptionService::interpreterDateIso($brut);

        if ($date === null) {
            $problemes[] = "La date saisie pour {$libelle} est illisible (format attendu : AAAA-MM-JJ).";

            return null;
        }

        return CarbonImmutable::instance($date)->startOfDay();
    }

    /**
     * Une heure de la journee, en minutes depuis minuit.
     *
     * Volontairement des entiers et non des objets de date : un horaire de
     * guichet n'appartient a aucun jour, et le faire porter par une date
     * inviterait a comparer des instants qui n'ont pas le meme jour.
     *
     * @param  list<string>  $problemes
     */
    private static function heure(array $valeurs, string $cle, string $libelle, array &$problemes): ?int
    {
        $brut = self::brut($valeurs, $cle);

        if ($brut === '') {
            $problemes[] = "Renseignez {$libelle}.";

            return null;
        }

        $minutes = self::interpreterHeure($brut);

        if ($minutes === null) {
            $problemes[] = "L'heure saisie pour {$libelle} est illisible (format attendu : HH:MM).";

            return null;
        }

        return $minutes;
    }

    /**
     * Point de verite unique du format des heures : la lecture (ici) et
     * l'ecriture (le formulaire des parametres) doivent avoir exactement la
     * meme severite, sinon l'ecole enregistre une valeur que la grille refuse
     * ensuite d'appliquer, sans que rien ne le dise.
     */
    public static function interpreterHeure(string $valeur): ?int
    {
        if (preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', trim($valeur), $m) !== 1) {
            return null;
        }

        return ((int) $m[1]) * 60 + (int) $m[2];
    }

    /** L'ecriture inverse, pour l'ecran de reglage et les messages. */
    public static function formaterHeure(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * La pause est facultative, mais elle l'est ENSEMBLE.
     *
     * Une seule des deux bornes renseignee est une saisie interrompue, pas une
     * intention : la retenir reviendrait a inventer l'autre borne.
     *
     * @param  list<string>  $problemes
     * @return array{0: ?int, 1: ?int}
     */
    private static function pause(array $valeurs, ?int $ouverture, ?int $fermeture, array &$problemes): array
    {
        $debutBrut = self::brut($valeurs, self::REGLAGE_PAUSE_DEBUT);
        $finBrut = self::brut($valeurs, self::REGLAGE_PAUSE_FIN);

        if ($debutBrut === '' && $finBrut === '') {
            return [null, null];
        }

        if ($debutBrut === '' || $finBrut === '') {
            $problemes[] = "La pause a besoin de ses deux bornes : renseignez l'heure de debut ET l'heure de fin, ou laissez les deux vides.";

            return [null, null];
        }

        $debut = self::interpreterHeure($debutBrut);
        $fin = self::interpreterHeure($finBrut);

        if ($debut === null || $fin === null) {
            $problemes[] = "Les heures de pause sont illisibles (format attendu : HH:MM).";

            return [null, null];
        }

        if ($fin <= $debut) {
            $problemes[] = "La pause se terminerait avant d'avoir commence.";

            return [null, null];
        }

        if ($ouverture !== null && $fermeture !== null && ($debut < $ouverture || $fin > $fermeture)) {
            $problemes[] = "La pause deborde de la plage d'ouverture du guichet.";

            return [null, null];
        }

        return [$debut, $fin];
    }

    /**
     * Les jours de la semaine ou le guichet ouvre, en numerotation ISO.
     *
     * Des numeros et non des noms : ils ne dependent d'aucune langue, et
     * `dayOfWeekIso` les rend directement. Sans ce reglage, la grille
     * proposerait joyeusement des creneaux le dimanche — le depot n'a aucune
     * notion de jour ouvre dans ses services metier, il n'y a rien a deduire.
     *
     * @param  list<string>  $problemes
     * @return list<int>
     */
    private static function joursOuverts(array $valeurs, array &$problemes): array
    {
        $brut = self::brut($valeurs, self::REGLAGE_JOURS_OUVERTS);

        $jours = [];

        foreach (explode(',', $brut) as $morceau) {
            $morceau = trim($morceau);

            if ($morceau === '') {
                continue;
            }

            if (preg_match('/^[1-7]$/', $morceau) !== 1) {
                $problemes[] = "Les jours d'ouverture sont illisibles (attendu : des numeros de 1 pour lundi a 7 pour dimanche, separes par des virgules).";

                return [];
            }

            $jours[(int) $morceau] = (int) $morceau;
        }

        if ($jours === []) {
            $problemes[] = "Choisissez au moins un jour de la semaine ou le guichet recoit.";

            return [];
        }

        ksort($jours);

        return array_values($jours);
    }

    /**
     * @param  list<string>  $problemes
     */
    private static function entier(array $valeurs, string $cle, int $min, int $max, string $message, array &$problemes): ?int
    {
        $brut = self::brut($valeurs, $cle);

        if ($brut === '' || preg_match('/^\d+$/', $brut) !== 1) {
            $problemes[] = sprintf($message, $min, $max);

            return null;
        }

        $valeur = (int) $brut;

        if ($valeur < $min || $valeur > $max) {
            $problemes[] = sprintf($message, $min, $max);

            return null;
        }

        return $valeur;
    }

    /**
     * La part de la pause qui mord reellement sur la plage d'ouverture.
     *
     * L'intersection, et non la duree brute de la pause : la pause a deja ete
     * refusee si elle debordait, mais ce calcul reste ecrit en intersection
     * pour que le jour ou la regle s'assouplit, le total d'heures utiles reste
     * juste au lieu de devenir negatif.
     */
    private static function minutesDePause(?int $ouverture, ?int $fermeture, ?int $pauseDebut, ?int $pauseFin): int
    {
        if ($ouverture === null || $fermeture === null || $pauseDebut === null || $pauseFin === null) {
            return 0;
        }

        return max(0, min($fermeture, $pauseFin) - max($ouverture, $pauseDebut));
    }
}
