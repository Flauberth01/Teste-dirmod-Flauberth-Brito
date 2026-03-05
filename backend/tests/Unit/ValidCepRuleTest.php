<?php

use App\Rules\ValidCep;
use Illuminate\Support\Facades\Validator;

it('accepts a valid cep in numeric and formatted forms', function () {
    $numericValidation = Validator::make(
        ['cep' => '01001000'],
        ['cep' => ['required', new ValidCep()]]
    );

    $formattedValidation = Validator::make(
        ['cep' => '01001-000'],
        ['cep' => ['required', new ValidCep()]]
    );

    expect($numericValidation->fails())->toBeFalse();
    expect($formattedValidation->fails())->toBeFalse();
});

it('rejects a zeroed cep', function () {
    $validation = Validator::make(
        ['cep' => '00000000'],
        ['cep' => ['required', new ValidCep()]]
    );

    expect($validation->fails())->toBeTrue();
    expect($validation->errors()->first('cep'))->toBe('CEP inválido ou inexistente.');
});

it('rejects cep with invalid format', function () {
    $validation = Validator::make(
        ['cep' => '123'],
        ['cep' => ['required', new ValidCep()]]
    );

    expect($validation->fails())->toBeTrue();
    expect($validation->errors()->first('cep'))->toBe('CEP inválido ou inexistente.');
});

