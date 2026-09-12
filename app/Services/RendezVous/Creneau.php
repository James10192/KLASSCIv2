<?php

namespace App\Services\RendezVous;

use Carbon\CarbonImmutable;

/**
 * Un creneau de guichet : un debut, une fin.
 *
 * Immuable, et sans identite : ce n'est pas une ligne de base. Les creneaux ne
 * se stockent pas, ils se recalculent a partir de la configuration a chaque
 * affichage. Ce qui se stockera, quand la reservation existera, c'est le
 * RENDEZ-VOUS, avec ses propres heures figees — seule facon qu'un changement
 * d'horaires en pleine campagne ne deplace personne.
 *
 * Rien d'autre ici : ni capacite, ni places restantes. Elles vivront sur le
 * rendez-vous et dans la configuration, la ou on les lit reellement.
 */
final class Creneau
{
    public function __construct(
        public readonly CarbonImmutable $debut,
        public readonly CarbonImmutable $fin,
    ) {}
}
