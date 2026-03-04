<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns cpf as valid and available when not registered', function () {
    $this->getJson('/api/validation/cpf/52998224725')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'cpf' => '52998224725',
                'valid' => true,
                'available' => true,
                'reason' => null,
            ],
        ]);
});

it('returns cpf as unavailable when already registered', function () {
    User::factory()->create([
        'cpf' => '52998224725',
    ]);

    $this->getJson('/api/validation/cpf/52998224725')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'cpf' => '52998224725',
                'valid' => true,
                'available' => false,
                'reason' => 'CPF_ALREADY_EXISTS',
            ],
        ]);
});

it('returns validation error when cpf is invalid', function () {
    $this->getJson('/api/validation/cpf/11111111111')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Validation error')
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.cpf.0', 'CPF inválido.');
});
