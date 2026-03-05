<?php

it('returns validation error for zeroed cep on lookup endpoint', function () {
    $this->getJson('/api/cep/00000000')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Validation error')
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.cep.0', 'CEP inválido ou inexistente.');
});

