<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCep implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cep = preg_replace('/\D/', '', (string) $value);

        if (! is_string($cep) || strlen($cep) !== 8 || $cep === '00000000') {
            $fail('CEP inválido ou inexistente.');
        }
    }
}

