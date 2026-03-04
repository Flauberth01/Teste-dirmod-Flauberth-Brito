<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ExternalServiceUnavailableException extends RuntimeException
{
    public function __construct(
        private readonly int $status = 503,
        ?Throwable $previous = null,
    ) {
        parent::__construct('External service unavailable', 0, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }
}
