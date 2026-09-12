<?php

namespace App\Services\RendezVous;

use Carbon\CarbonImmutable;

/**
 * La grille des creneaux de guichet, deduite de la configuration.
 *
 * Du calcul pur : aucune lecture de base, aucune ecriture. La grille montree a
 * l'ecole pendant qu'elle regle ses horaires ne depend ainsi de rien d'autre
 * que de ses horaires, et s'eprouve sans base de donnees.
 */
final class GenerateurCreneaux
{
    /** Memoires de calcul : l'apercu pose trois fois les memes questions. */
    private ?int $creneauxParJour = null;

    private ?int $joursDeReception = null;

    public function __construct(private readonly ConfigurationRendezVous $config) {}

    public function configuration(): ConfigurationRendezVous
    {
        return $this->config;
    }

    /**
     * Les creneaux d'une journee donnee.
     *
     * Vide si le jour tombe hors campagne, un jour de fermeture, ou si la
     * configuration est incomplete — et dans ce dernier cas, `problemes()` dit
     * pourquoi. C'est la difference entre « il n'y a plus de place » et « cette
     * ecole n'a pas fini de se regler », et l'appelant doit pouvoir les
     * distinguer : les confondre ferait lire « complet » a une famille devant
     * une ecole qui n'a simplement rien configure.
     *
     * @return list<Creneau>
     */
    public function pourLeJour(CarbonImmutable $jour): array
    {
        if (! $this->config->jourOuvert($jour)) {
            return [];
        }

        return $this->grilleDuJour($jour);
    }

    /**
     * La grille des horaires seule, sans le calendrier.
     *
     * Separee de `pourLeJour()` parce que les deux repondent a des questions
     * differentes. « Combien de creneaux une journee comporte » ne depend que
     * des horaires ; « y a-t-il des creneaux ce mardi » depend aussi de la
     * campagne et des jours d'ouverture. Les confondre ferait rendre zero
     * creneau par jour a une ecole dont la campagne est simplement terminee.
     *
     * @return list<Creneau>
     */
    private function grilleDuJour(CarbonImmutable $jour): array
    {
        if (! $this->config->estComplete()) {
            return [];
        }

        $minuit = $jour->startOfDay();
        $duree = $this->config->dureeMinutes;

        $creneaux = [];

        // Une boucle d'increment, jamais une division.
        //
        // `floor(minutes_utiles / duree)` diviserait par une valeur saisie par
        // l'ecole, et ce depot a deja paye cette erreur une fois. Le correctif
        // retenu alors etait explicite : faire disparaitre la division, plutot
        // que poser une garde qu'on finirait par oublier en la deplacant.
        //
        // La duree minimale est garantie par la configuration, qui refuse toute
        // valeur sous ConfigurationRendezVous::DUREE_MINIMALE. Sans elle, cette
        // boucle ne planterait pas : elle tournerait sans fin, ce qui est pire,
        // parce que muet.
        for ($debut = $this->config->ouverture; $debut + $duree <= $this->config->fermeture; $debut += $duree) {
            $fin = $debut + $duree;

            if ($this->chevaucheLaPause($debut, $fin)) {
                continue;
            }

            $creneaux[] = new Creneau(
                debut: $minuit->addMinutes($debut),
                fin: $minuit->addMinutes($fin),
            );
        }

        return $creneaux;
    }

    /**
     * Combien de creneaux une journee d'ouverture comporte.
     *
     * Le meme pour tous les jours ouverts : la configuration ne decrit qu'une
     * journee type. On passe donc par la grille seule, sans le calendrier — le
     * demander a un jour precis rendrait zero des la campagne terminee, pour une
     * raison de calendrier et non d'horaires.
     */
    public function creneauxParJour(): int
    {
        if (! $this->config->estComplete()) {
            return 0;
        }

        return $this->creneauxParJour ??= count($this->grilleDuJour($this->config->premierJour));
    }

    /**
     * Le chiffre demande : combien de personnes l'ecole recoit dans la journee.
     *
     * Rendu, et non saisi. C'est tout l'objet de cette classe.
     */
    public function placesParJour(): int
    {
        return $this->creneauxParJour() * $this->config->capaciteParCreneau;
    }

    /** Combien de jours de reception la campagne compte reellement. */
    public function joursDeReception(): int
    {
        if (! $this->config->estComplete()) {
            return 0;
        }

        if ($this->joursDeReception !== null) {
            return $this->joursDeReception;
        }

        $jours = 0;

        for (
            $jour = $this->config->premierJour;
            $jour->lte($this->config->dernierJour);
            $jour = $jour->addDay()
        ) {
            if (in_array($jour->dayOfWeekIso, $this->config->joursOuverts, true)) {
                $jours++;
            }
        }

        return $this->joursDeReception = $jours;
    }

    /** Ce que la campagne peut absorber en tout. */
    public function placesSurLaCampagne(): int
    {
        return $this->placesParJour() * $this->joursDeReception();
    }

    /**
     * Combien de jours de reception il faudrait pour recevoir tout le monde.
     *
     * Ce que l'ecran doit dire a l'ecole en septembre plutot qu'en novembre.
     * Null quand la grille ne produit aucune place : la question n'a alors pas
     * de reponse, et rendre zero laisserait croire qu'il n'y a rien a faire.
     */
    public function joursNecessairesPour(int $personnes): ?int
    {
        $parJour = $this->placesParJour();

        if ($parJour <= 0 || $personnes <= 0) {
            return null;
        }

        return (int) ceil($personnes / $parJour);
    }

    /**
     * Un creneau qui mord sur la pause est retire, pas raccourci.
     *
     * Le raccourcir donnerait a la famille un rendez-vous de sept minutes la ou
     * l'ecole en a prevu quinze, sans le lui dire.
     */
    private function chevaucheLaPause(int $debut, int $fin): bool
    {
        if ($this->config->pauseDebut === null || $this->config->pauseFin === null) {
            return false;
        }

        // Le predicat de chevauchement du depot, recopie plutot qu'importe : le
        // domaine horaire des cours n'a pas encore tranche entre l'hebdomadaire
        // et le date, et s'y brancher pour deux lignes couterait plus que de les
        // ecrire.
        return $debut < $this->config->pauseFin && $fin > $this->config->pauseDebut;
    }
}
