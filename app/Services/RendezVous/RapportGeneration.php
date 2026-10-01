<?php

namespace App\Services\RendezVous;

final class RapportGeneration
{
    public function __construct(
        public readonly int $crees,
        public readonly int $misAJour,
        public readonly int $fermes,
        public readonly int $conservesOccupes,
        /** Parmi les mis a jour, ceux qui etaient deja conformes (rien ne change). */
        public readonly int $inchanges = 0,
    ) {
    }
}
