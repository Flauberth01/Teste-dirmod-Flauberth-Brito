<?php

use App\Exceptions\ExternalServiceUnavailableException;
use App\Exceptions\InvalidCepException;
use App\Services\CepService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a user with resolved address when cpf and cep are valid', function () {
    app()->bind(CepService::class, fn () => new class extends CepService
    {
        public function lookup(string $cep): array
        {
            return [
                'street' => 'Praça da Sé',
                'neighborhood' => 'Sé',
                'city' => 'São Paulo',
                'state' => 'SP',
            ];
        }
    });

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'cpf' => '529.982.247-25',
        'cep' => '01001-000',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.user.address_status', 'resolved')
        ->assertJsonPath('data.user.city', 'São Paulo')
        ->assertJsonPath('data.token', fn ($token) => is_string($token) && $token !== '');

    $this->assertDatabaseHas('users', [
        'email' => 'ana@example.com',
        'cpf' => '52998224725',
    ]);
});

it('returns validation error for invalid cpf', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'cpf' => '11111111111',
        'cep' => '01001000',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', 'Validation error')
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['cpf']]);
});

it('returns validation error for duplicate cpf', function () {
    app()->bind(CepService::class, fn () => new class extends CepService
    {
        public function lookup(string $cep): array
        {
            return [
                'street' => 'Rua 1',
                'neighborhood' => 'Centro',
                'city' => 'Rio Branco',
                'state' => 'AC',
            ];
        }
    });

    $payload = [
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'cpf' => '52998224725',
        'cep' => '69900000',
    ];

    $this->postJson('/api/auth/register', $payload)->assertCreated();

    $response = $this->postJson('/api/auth/register', [
        ...$payload,
        'email' => 'ana2@example.com',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.cpf.0', 'Este CPF já está cadastrado.')
        ->assertJsonStructure(['errors' => ['cpf']]);
});

it('returns validation error for duplicate email', function () {
    app()->bind(CepService::class, fn () => new class extends CepService
    {
        public function lookup(string $cep): array
        {
            return [
                'street' => 'Rua 1',
                'neighborhood' => 'Centro',
                'city' => 'Rio Branco',
                'state' => 'AC',
            ];
        }
    });

    $payload = [
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'cpf' => '52998224725',
        'cep' => '69900000',
    ];

    $this->postJson('/api/auth/register', $payload)->assertCreated();

    $response = $this->postJson('/api/auth/register', [
        ...$payload,
        'cpf' => '34707804047',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.email.0', 'Este e-mail já está cadastrado.')
        ->assertJsonStructure(['errors' => ['email']]);
});

it('blocks register when cep is invalid or nonexistent', function () {
    app()->bind(CepService::class, fn () => new class extends CepService
    {
        public function lookup(string $cep): array
        {
            throw new InvalidCepException();
        }
    });

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'cpf' => '52998224725',
        'cep' => '01001000',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['cep']]);
});

it('allows register as pending when cep api is unavailable', function () {
    app()->bind(CepService::class, fn () => new class extends CepService
    {
        public function lookup(string $cep): array
        {
            throw new ExternalServiceUnavailableException(503);
        }
    });

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'cpf' => '52998224725',
        'cep' => '01001000',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.user.address_status', 'pending');
});
