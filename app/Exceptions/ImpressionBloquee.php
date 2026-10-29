<?php

namespace App\Exceptions;

use App\Services\PrintDecision;
use RuntimeException;

class ImpressionBloquee extends RuntimeException
{
    public function __construct(public readonly PrintDecision $decision)
    {
        parent::__construct($decision->message());
    }
}
