<?php

namespace App\Services\RendezVous;

use Carbon\CarbonImmutable;

/**
 * Un creneau de guichet : un debut, une fin, une capacite.
 *
 * Immuable, et sans identite : ce n'est pas une ligne de base. Les creneaux ne
 * sont jamais stockes — ils se recalculent a partir de la configuration a
 * chaque affichage. Ce qui est stocke, c'est le RENDEZ-VOUS, et il porte ses
 * propres `debut_at` / `fin_at` figes au moment de la reservation.
 *
 * La distinction est la seule protection des familles quand l'ecole decale ses
 * horaires en cours de campagne. Un rendez-vous qui ne porterait qu'un indice
 * de creneau se deplacerait avec la configuration, en silence, apres que la
 * famille a recu son heure par message.
 */
final class Creneau
{
    public function __construct(
        public readonly CarbonImmutable $debut,
        public readonly CarbonImmutable $fin,
        /** Combien de familles peuvent etre recues en meme temps sur ce creneau. */
        public readonly int $capacite,
        /** Combien de places restent. Null tant que l'occupation n'a pas ete comptee. */
        public readonly ?int $restant = null,
    ) {}

    /**
     * Le meme creneau, avec son occupation reelle.
     *
     * `max(0, ...)` sur le modele de ESBTPClasse::getPlacesDisponiblesAttribute()
     * et de ESBTPFraisOption : une capacite baissee sous l'occupation deja prise
     * doit rendre zero place, jamais un nombre negatif.
     */
    public function avecOccupation(int $pris): self
    {
        return new self($this->debut, $this->fin, $this->capacite, max(0, $this->capacite - $pris));
    }

    public function estComplet(): bool
    {
        return $this->restant !== null && $this->restant <= 0;
    }

    /**
     * Ce que le portail public publie.
     *
     * La capacite totale n'en fait pas partie : une famille n'a pas a savoir
     * combien de guichets l'ecole tient, et publier le couple
     * (capacite, restant) reviendrait a diffuser le rythme de remplissage de
     * l'etablissement. Seule la disponibilite la regarde.
     *
     * @return array{debut: string, fin: string, restant: ?int, complet: bool}
     */
    public function pourLePublic(): array
    {
        return [
            'debut' => $this->debut->format('Y-m-d H:i'),
            'fin' => $this->fin->format('Y-m-d H:i'),
            'restant' => $this->restant,
            'complet' => $this->estComplet(),
        ];
    }
}
