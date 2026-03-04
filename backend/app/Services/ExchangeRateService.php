<?php

namespace App\Services;

use App\Exceptions\ExternalServiceUnavailableException;
use App\Exceptions\UnsupportedCurrencyException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ExchangeRateService
{
    public function quoteToBrl(string $currency): string
    {
        $normalizedCurrency = strtoupper($currency);

        if ($normalizedCurrency === 'BRL') {
            return '1.000000';
        }

        $baseUrl = rtrim((string) config('services.exchange_rate.url', 'https://api.frankfurter.app'), '/');

        try {
            $response = Http::timeout(6)
                ->retry(2, 250)
                ->acceptJson()
                ->get("{$baseUrl}/latest", [
                    'from' => $normalizedCurrency,
                    'to' => 'BRL',
                ]);
        } catch (ConnectionException $exception) {
            throw new ExternalServiceUnavailableException($this->statusForConnectionError($exception), $exception);
        }

        if ($response->status() === Response::HTTP_GATEWAY_TIMEOUT) {
            throw new ExternalServiceUnavailableException(Response::HTTP_GATEWAY_TIMEOUT);
        }

        if ($response->clientError()) {
            throw new UnsupportedCurrencyException();
        }

        if (! $response->ok() || $response->serverError()) {
            throw new ExternalServiceUnavailableException(Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $payload = $response->json();
        $rate = $payload['rates']['BRL'] ?? null;

        if (! is_numeric($rate)) {
            throw new ExternalServiceUnavailableException(Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return number_format((float) $rate, 6, '.', '');
    }

    private function statusForConnectionError(ConnectionException $exception): int
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timed out')
            ? Response::HTTP_GATEWAY_TIMEOUT
            : Response::HTTP_SERVICE_UNAVAILABLE;
    }
}
