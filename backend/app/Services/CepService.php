<?php

namespace App\Services;

use App\Exceptions\ExternalServiceUnavailableException;
use App\Exceptions\InvalidCepException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CepService
{
    public function lookup(string $cep): array
    {
        $baseUrl = rtrim((string) config('services.cep.url', 'https://viacep.com.br/ws'), '/');
        $normalizedCep = preg_replace('/\D/', '', $cep);

        try {
            $response = Http::timeout(6)
                ->retry(2, 250)
                ->acceptJson()
                ->get("{$baseUrl}/{$normalizedCep}/json/");
        } catch (ConnectionException $exception) {
            throw new ExternalServiceUnavailableException($this->statusForConnectionError($exception), $exception);
        }

        if (in_array($response->status(), [Response::HTTP_BAD_REQUEST, Response::HTTP_NOT_FOUND], true)) {
            throw new InvalidCepException();
        }

        if ($response->status() === Response::HTTP_GATEWAY_TIMEOUT) {
            throw new ExternalServiceUnavailableException(Response::HTTP_GATEWAY_TIMEOUT);
        }

        if (! $response->ok() || $response->serverError()) {
            throw new ExternalServiceUnavailableException(Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['erro'] ?? false) === true) {
            throw new InvalidCepException();
        }

        return [
            'street' => trim((string) ($payload['logradouro'] ?? '')) ?: null,
            'neighborhood' => trim((string) ($payload['bairro'] ?? '')) ?: null,
            'city' => trim((string) ($payload['localidade'] ?? '')) ?: null,
            'state' => trim((string) ($payload['uf'] ?? '')) ?: null,
        ];
    }

    private function statusForConnectionError(ConnectionException $exception): int
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timed out')
            ? Response::HTTP_GATEWAY_TIMEOUT
            : Response::HTTP_SERVICE_UNAVAILABLE;
    }
}
