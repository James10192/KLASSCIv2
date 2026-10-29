<?php

namespace App\Services\RendezVous\Renvoi;

use RuntimeException;

/** Le renvoi d'une reservation est refuse apres coup : la transaction est annulee. */
class RenvoiRefuse extends RuntimeException
{
    public function __construct(public readonly string $raison)
    {
        parent::__construct($raison);
    }
}
