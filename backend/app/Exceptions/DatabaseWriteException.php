<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class DatabaseWriteException extends RuntimeException
{
    public function __construct(?string $message = null, ?Throwable $previous = null)
    {
        parent::__construct($message ?? 'Database write failed', 0, $previous);
    }
}
