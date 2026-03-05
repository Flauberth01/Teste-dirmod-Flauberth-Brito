<?php

use App\Actions\CreateExpenseAction;
use App\Exceptions\DatabaseWriteException;
use App\Exceptions\ExternalServiceUnavailableException;
use App\Models\Expense;
use App\Models\User;
use App\Services\CepService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns fixed 401 contract for unauthenticated access', function () {
    $this->getJson('/api/auth/me')
        ->assertStatus(401)
        ->assertJsonPath('message', 'Unauthenticated')
        ->assertJsonPath('errors', null)
        ->assertJsonPath('code', 'UNAUTHENTICATED')
        ->assertJsonPath('status', 401)
        ->assertJsonStructure(['request_id']);
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
        ->assertJsonPath('status', 422)
        ->assertJsonStructure(['errors', 'request_id']);
});

it('returns fixed 403 contract for forbidden resource access', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $expense = Expense::factory()->for($owner)->create();

    Sanctum::actingAs($intruder);

    $this->getJson("/api/expenses/{$expense->id}")
        ->assertStatus(403)
        ->assertJsonPath('message', 'Forbidden')
        ->assertJsonPath('errors', null)
        ->assertJsonPath('code', 'FORBIDDEN')
        ->assertJsonPath('status', 403)
        ->assertJsonStructure(['request_id']);
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
        ->assertJsonPath('message', 'External service unavailable')
        ->assertJsonPath('errors', null)
        ->assertJsonPath('code', 'EXTERNAL_SERVICE_UNAVAILABLE')
        ->assertJsonPath('status', 503)
        ->assertJsonStructure(['request_id']);
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
        ->assertJsonPath('message', 'External service unavailable')
        ->assertJsonPath('errors', null)
        ->assertJsonPath('code', 'EXTERNAL_SERVICE_UNAVAILABLE')
        ->assertJsonPath('status', 504)
        ->assertJsonStructure(['request_id']);
});

it('returns fixed 500 contract when database write fails internally', function () {
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
        ->assertJsonPath('message', 'Database write failed')
        ->assertJsonPath('errors', null)
        ->assertJsonPath('code', 'DATABASE_WRITE_ERROR')
        ->assertJsonPath('status', 500)
        ->assertJsonStructure(['request_id']);
});

it('returns fixed 503 contract when database connection is unavailable', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $pdoException = new \PDOException('connection refused');
    $pdoException->errorInfo = ['08006'];
    $queryException = new QueryException('pgsql', 'insert into expenses', [], $pdoException);

    $action = Mockery::mock(CreateExpenseAction::class);
    $action
        ->shouldReceive('execute')
        ->once()
        ->andThrow(new DatabaseWriteException(previous: $queryException));

    app()->instance(CreateExpenseAction::class, $action);

    $this->postJson('/api/expenses', [
        'amount_original' => '10.00',
        'currency' => 'USD',
    ])
        ->assertStatus(503)
        ->assertJsonPath('message', 'Database unavailable')
        ->assertJsonPath('errors', null)
        ->assertJsonPath('code', 'DATABASE_UNAVAILABLE')
        ->assertJsonPath('status', 503)
        ->assertJsonStructure(['request_id']);
});
