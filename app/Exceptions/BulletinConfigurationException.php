<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class BulletinConfigurationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
