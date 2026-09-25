<?php

namespace App\Domain\Assistant\Harnais;

/**
 * Ce que la boucle rend à l'orchestrateur, et ce que le journal enregistre.
 *
 * statut : ok | erreur | interrompu | limite
 */
final class ResultatBoucle
{
    public function __construct(
        public readonly string $statut,
        public readonly string $texte,
        public readonly string $texteDernierTour,
        public readonly array $appels,
        public readonly ?string $modele,
        public readonly ?string $fournisseur,
        public readonly array $essais,
        public readonly int $tokensEntree,
        public readonly int $tokensSortie,
        public readonly int $tours,
        public readonly int $latenceMs,
        /** Messages neutres produits pendant l'échange (appels d'outil et leurs résultats compacts), rejoués dans l'historique. */
        public readonly array $trace = [],
    ) {
    }

    public function estErreur(): bool
    {
        return $this->statut === 'erreur';
    }

    public function estInterrompu(): bool
    {
        return $this->statut === 'interrompu';
    }
}
