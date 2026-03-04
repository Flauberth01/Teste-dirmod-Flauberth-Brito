<?php

namespace App\Exceptions;

use RuntimeException;

class UnsupportedCurrencyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Unsupported currency.');
    }
}
