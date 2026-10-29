<?php

namespace App\Exceptions;

use RuntimeException;

final class LastActiveSuperAdminException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Le dernier super administrateur actif ne peut pas être désactivé.');
    }
}
