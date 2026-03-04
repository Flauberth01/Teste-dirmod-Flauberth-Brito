<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (! is_string($cpf) || strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('CPF inválido.');

            return;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            for ($index = 0; $index < $position; $index++) {
                $sum += ((int) $cpf[$index]) * (($position + 1) - $index);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if (((int) $cpf[$position]) !== $digit) {
                $fail('CPF inválido.');

                return;
            }
        }
    }
}
