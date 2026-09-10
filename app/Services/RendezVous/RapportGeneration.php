<?php

namespace App\Services\RendezVous;

final class RapportGeneration
{
    public function __construct(
        public readonly int $crees,
        public readonly int $misAJour,
        public readonly int $fermes,
        public readonly int $conservesOccupes,
    ) {
    }
}
