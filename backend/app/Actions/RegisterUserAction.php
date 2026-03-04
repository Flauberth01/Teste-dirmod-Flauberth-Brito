<?php

namespace App\Actions;

use App\Exceptions\ExternalServiceUnavailableException;
use App\Exceptions\InvalidCepException;
use App\Models\User;
use App\Services\CepService;
use App\Support\DatabaseQueryExceptionMapper;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterUserAction
{
    public function __construct(private readonly CepService $cepService)
    {
    }

    public function execute(array $payload): User
    {
        $this->ensureUserAvailability($payload);

        $addressStatus = 'resolved';
        $address = [
            'street' => null,
            'neighborhood' => null,
            'city' => null,
            'state' => null,
        ];

        try {
            $address = $this->cepService->lookup($payload['cep']);
        } catch (InvalidCepException $exception) {
            throw ValidationException::withMessages([
                'cep' => ['CEP inválido ou inexistente.'],
            ]);
        } catch (ExternalServiceUnavailableException $exception) {
            $addressStatus = 'pending';
        }

        try {
            return DB::transaction(function () use ($payload, $address, $addressStatus): User {
                return User::query()->create([
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'password' => $payload['password'],
                    'cpf' => $payload['cpf'],
                    'cep' => $payload['cep'],
                    'street' => $address['street'],
                    'neighborhood' => $address['neighborhood'],
                    'city' => $address['city'],
                    'state' => $address['state'],
                    'address_status' => $addressStatus,
                ]);
            }, 3);
        } catch (QueryException $exception) {
            DatabaseQueryExceptionMapper::throwValidationOrDatabaseException($exception, [
                'users_cpf_unique' => [
                    'field' => 'cpf',
                    'message' => 'Este CPF já está cadastrado.',
                ],
                'users_email_unique' => [
                    'field' => 'email',
                    'message' => 'Este e-mail já está cadastrado.',
                ],
            ]);
        }
    }

    private function ensureUserAvailability(array $payload): void
    {
        if (User::query()->where('cpf', $payload['cpf'])->exists()) {
            throw ValidationException::withMessages([
                'cpf' => ['Este CPF já está cadastrado.'],
            ]);
        }

        if (User::query()->where('email', $payload['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Este e-mail já está cadastrado.'],
            ]);
        }
    }
}
