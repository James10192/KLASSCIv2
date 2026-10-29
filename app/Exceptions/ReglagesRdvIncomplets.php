<?php

namespace App\Exceptions;

use RuntimeException;

class ReglagesRdvIncomplets extends RuntimeException
{
    /**
     * @param  list<string>  $cles
     */
    public function __construct(public readonly array $cles)
    {
        parent::__construct(
            'Réglages rendez-vous manquants ou illisibles : '.implode(', ', $cles)
        );
    }
}
