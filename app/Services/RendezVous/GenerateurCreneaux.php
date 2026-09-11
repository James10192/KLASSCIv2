<?php

namespace App\Services\RendezVous;

use Carbon\CarbonImmutable;

/**
 * La grille des creneaux de guichet, deduite de la configuration.
 *
 * Du calcul pur : aucune lecture de base, aucune ecriture. L'occupation reelle
 * se greffe apres coup, par `avecOccupation()`, pour que cette classe reste
 * testable sans base de donnees et que la grille affichee a l'ecole pendant
 * qu'elle regle ses horaires ne depende de rien d'autre que de ses horaires.
 *
 * Rien n'est stocke : les creneaux se recalculent a chaque affichage. Ce qui
 * est stocke, c'est le rendez-vous, avec ses propres heures figees.
 */
final class GenerateurCreneaux
{
    public function __construct(private readonly ConfigurationRendezVous $config) {}

    public static function depuisLesReglages(): self
    {
        return new self(ConfigurationRendezVous::depuisLesReglages());
    }

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
                capacite: $this->config->capaciteParCreneau,
            );
        }

        return $creneaux;
    }

    /**
     * Les creneaux d'une journee, avec leur occupation.
     *
     * Les comptes sont fournis par l'appelant plutot que lus ici : une seule
     * requete groupee sur la journee, au lieu d'une par creneau.
     *
     * @param  array<string, int>  $comptes  cle « Y-m-d H:i », valeur = rendez-vous deja pris
     * @return list<Creneau>
     */
    public function pourLeJourAvecOccupation(CarbonImmutable $jour, array $comptes): array
    {
        return array_map(
            fn (Creneau $creneau) => $creneau->avecOccupation($comptes[$creneau->debut->format('Y-m-d H:i')] ?? 0),
            $this->pourLeJour($jour),
        );
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

        return count($this->grilleDuJour($this->config->premierJour));
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

        return $jours;
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
