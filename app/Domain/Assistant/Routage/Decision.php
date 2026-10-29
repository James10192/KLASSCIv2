<?php

namespace App\Domain\Assistant\Routage;

use App\Domain\Assistant\Modeles\ModeleIa;

/** Ce que le routeur a décidé pour un échange. */
final class Decision
{
    /** @param ModeleIa[] $candidats ordre d'essai */
    public function __construct(
        public readonly array $candidats,
        public readonly ?string $palier,
        public readonly string $raison,
        public readonly bool $pause = false,
    ) {
    }
}
