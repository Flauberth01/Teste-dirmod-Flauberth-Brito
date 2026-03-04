<?php

use App\Actions\CreateExpenseAction;
use App\Exceptions\DatabaseWriteException;
use App\Exceptions\ExternalServiceUnavailableException;
use App\Models\Expense;
use App\Models\User;
use App\Services\CepService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns fixed 401 contract for unauthenticated access', function () {
    $this->getJson('/api/auth/me')
        ->assertStatus(401)
        ->assertExactJson([
            'message' => 'Unauthenticated',
            'errors' => null,
            'code' => 'UNAUTHENTICATED',
        ]);
});

it('returns fixed 422 contract for validation errors', function () {
    $this->postJson('/api/auth/register', [
        'name' => '',
        'email' => 'invalid-email',
        'password' => '123',
        'password_confirmation' => '456',
        'cpf' => '123',
        'cep' => 'abc',
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Validation error')
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors']);
});

it('returns fixed 403 contract for forbidden resource access', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $expense = Expense::factory()->for($owner)->create();

    Sanctum::actingAs($intruder);

    $this->getJson("/api/expenses/{$expense->id}")
        ->assertStatus(403)
        ->assertExactJson([
            'message' => 'Forbidden',
            'errors' => null,
            'code' => 'FORBIDDEN',
        ]);
});

it('returns fixed 503 contract when external service is unavailable', function () {
    app()->bind(CepService::class, fn () => new class extends CepService
    {
        public function lookup(string $cep): array
        {
            throw new ExternalServiceUnavailableException(503);
        }
    });

    $this->getJson('/api/cep/01001000')
        ->assertStatus(503)
        ->assertExactJson([
            'message' => 'External service unavailable',
            'errors' => null,
            'code' => 'EXTERNAL_SERVICE_UNAVAILABLE',
        ]);
});

it('returns fixed 504 contract when external service times out', function () {
    app()->bind(CepService::class, fn () => new class extends CepService
    {
        public function lookup(string $cep): array
        {
            throw new ExternalServiceUnavailableException(504);
        }
    });

    $this->getJson('/api/cep/01001000')
        ->assertStatus(504)
        ->assertExactJson([
            'message' => 'External service unavailable',
            'errors' => null,
            'code' => 'EXTERNAL_SERVICE_UNAVAILABLE',
        ]);
});

it('returns fixed 500 generic contract when database write fails internally', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $action = Mockery::mock(CreateExpenseAction::class);
    $action
        ->shouldReceive('execute')
        ->once()
        ->andThrow(new DatabaseWriteException());

    app()->instance(CreateExpenseAction::class, $action);

    $this->postJson('/api/expenses', [
        'amount_original' => '10.00',
        'currency' => 'USD',
    ])
        ->assertStatus(500)
        ->assertExactJson([
            'message' => 'Erro ao processar',
            'errors' => null,
            'code' => 'GENERIC_ERROR',
        ]);
});
