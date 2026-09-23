<?php

namespace App\Domain\Permissions;

use DomainException;

/** Un acces temporaire que le logiciel refuse d'ouvrir, avec la raison lisible par l'ecole. */
class AccesTemporaireRefuse extends DomainException
{
}
