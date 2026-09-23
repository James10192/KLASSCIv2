<?php

namespace App\Domain\Support\Exceptions;

use RuntimeException;

/** Le Master n'a pas repondu, ou a repondu par une erreur serveur. On peut reessayer. */
class MasterSupportIndisponible extends RuntimeException
{
}
