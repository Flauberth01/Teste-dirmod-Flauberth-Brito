<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'cpf' => $this->validCpf(),
            'cep' => '01001000',
            'street' => fake()->streetName(),
            'neighborhood' => fake()->streetSuffix(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'address_status' => 'resolved',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    private function validCpf(): string
    {
        $base = '';

        for ($index = 0; $index < 9; $index++) {
            $base .= (string) random_int(0, 9);
        }

        $digitOne = $this->calculateDigit($base, 10);
        $digitTwo = $this->calculateDigit($base.$digitOne, 11);

        return $base.$digitOne.$digitTwo;
    }

    private function calculateDigit(string $value, int $factor): int
    {
        $sum = 0;

        for ($index = 0; $index < strlen($value); $index++) {
            $sum += ((int) $value[$index]) * ($factor - $index);
        }

        return ((10 * $sum) % 11) % 10;
    }
}
