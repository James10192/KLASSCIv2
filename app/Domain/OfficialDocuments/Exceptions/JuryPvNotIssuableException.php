<?php

namespace App\Domain\OfficialDocuments\Exceptions;

use RuntimeException;

class JuryPvNotIssuableException extends RuntimeException
{
    public function __construct(public readonly array $reasons)
    {
        parent::__construct('Le jury ne satisfait pas les conditions d émission du PV officiel.');
    }
}
