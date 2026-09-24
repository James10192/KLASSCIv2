<?php

namespace App\Services\Emails\Fautes;

use RuntimeException;

/** Une ligne du groupe a change entre la preparation et le verrou : le groupe est annule. */
class ValeurModifiee extends RuntimeException
{
    public function __construct(public readonly string $cle)
    {
        parent::__construct('Valeur modifiee : '.$cle);
    }
}
