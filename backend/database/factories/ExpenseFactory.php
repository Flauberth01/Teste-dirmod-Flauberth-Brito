<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount_original' => fake()->randomFloat(2, 10, 10000),
            'currency' => 'USD',
            'exchange_rate' => fake()->randomFloat(6, 4, 7),
            'amount_brl' => fake()->randomFloat(2, 40, 70000),
            'status' => Expense::STATUS_CONVERTED,
            'failure_reason' => null,
            'converted_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'exchange_rate' => null,
            'amount_brl' => null,
            'status' => Expense::STATUS_PENDING,
            'failure_reason' => 'External service unavailable',
            'converted_at' => null,
        ]);
    }
}
