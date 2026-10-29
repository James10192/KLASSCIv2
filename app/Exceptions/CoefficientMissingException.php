<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class CoefficientMissingException extends RuntimeException
{
    private array $context;

    public function __construct(string $message, array $context = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
