<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidCepException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid CEP.');
    }
}
