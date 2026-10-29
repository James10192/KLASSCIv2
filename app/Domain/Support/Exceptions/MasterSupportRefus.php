<?php

namespace App\Domain\Support\Exceptions;

use RuntimeException;

/**
 * Le Master a refuse la demande (4xx). Reessayer ne changera rien : la
 * soumission, ou l'identifiant, est a corriger.
 */
class MasterSupportRefus extends RuntimeException
{
    public function __construct(
        public readonly int $statut,
        public readonly string $codeErreur,
        string $message,
        public readonly array $erreurs = [],
    ) {
        parent::__construct($message, $statut);
    }
}
