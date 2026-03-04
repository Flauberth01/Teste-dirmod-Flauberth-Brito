<?php

use App\Exceptions\ExternalServiceUnavailableException;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates a converted expense when exchange service is available', function () {
    app()->bind(ExchangeRateService::class, fn () => new class extends ExchangeRateService
    {
        public function quoteToBrl(string $currency): string
        {
            return '5.500000';
        }
    });

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/expenses', [
        'amount_original' => '10.00',
        'currency' => 'USD',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.status', Expense::STATUS_CONVERTED)
        ->assertJsonPath('data.exchange_rate', '5.500000')
        ->assertJsonPath('data.amount_brl', '55.00');
});

it('creates a pending expense when exchange service is unavailable', function () {
    app()->bind(ExchangeRateService::class, fn () => new class extends ExchangeRateService
    {
        public function quoteToBrl(string $currency): string
        {
            throw new ExternalServiceUnavailableException(503);
        }
    });

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/expenses', [
        'amount_original' => '10.00',
        'currency' => 'USD',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.status', Expense::STATUS_PENDING)
        ->assertJsonPath('data.failure_reason', 'External service unavailable');
});

it('blocks access to expenses from another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $expense = Expense::factory()->for($owner)->create();

    Sanctum::actingAs($intruder);

    $this->getJson("/api/expenses/{$expense->id}")
        ->assertStatus(403)
        ->assertJsonPath('message', 'Forbidden')
        ->assertJsonPath('code', 'FORBIDDEN');
});

it('retries conversion for a pending expense', function () {
    app()->bind(ExchangeRateService::class, fn () => new class extends ExchangeRateService
    {
        public function quoteToBrl(string $currency): string
        {
            return '4.250000';
        }
    });

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $expense = Expense::factory()->pending()->for($user)->create([
        'amount_original' => '20.00',
        'currency' => 'USD',
    ]);

    $this->postJson("/api/expenses/{$expense->id}/retry-conversion")
        ->assertOk()
        ->assertJsonPath('data.status', Expense::STATUS_CONVERTED)
        ->assertJsonPath('data.amount_brl', '85.00');
});

it('blocks retry when expense is not pending', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $expense = Expense::factory()->for($user)->create([
        'status' => Expense::STATUS_CONVERTED,
    ]);

    $this->postJson("/api/expenses/{$expense->id}/retry-conversion")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Validation error')
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.status.0', 'Apenas despesas pendentes podem ser reprocessadas.');
});
