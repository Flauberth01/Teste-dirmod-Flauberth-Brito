<?php

namespace App\Actions;

use App\Exceptions\ExternalServiceUnavailableException;
use App\Exceptions\UnsupportedCurrencyException;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Support\DatabaseQueryExceptionMapper;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateExpenseAction
{
    public function __construct(private readonly ExchangeRateService $exchangeRateService)
    {
    }

    public function execute(User $user, array $payload): Expense
    {
        $rate = null;
        $amountBrl = null;
        $status = Expense::STATUS_CONVERTED;
        $failureReason = null;
        $convertedAt = null;

        try {
            $rate = $this->exchangeRateService->quoteToBrl($payload['currency']);
            $amountBrl = $this->calculateBrl((string) $payload['amount_original'], $rate);
            $convertedAt = Carbon::now();
        } catch (UnsupportedCurrencyException $exception) {
            throw ValidationException::withMessages([
                'currency' => ['Moeda não suportada para conversão.'],
            ]);
        } catch (ExternalServiceUnavailableException $exception) {
            $status = Expense::STATUS_PENDING;
            $failureReason = 'External service unavailable';
        }

        try {
            return DB::transaction(function () use (
                $user,
                $payload,
                $rate,
                $amountBrl,
                $status,
                $failureReason,
                $convertedAt
            ): Expense {
                return $user->expenses()->create([
                    'amount_original' => number_format((float) $payload['amount_original'], 2, '.', ''),
                    'currency' => strtoupper((string) $payload['currency']),
                    'exchange_rate' => $rate,
                    'amount_brl' => $amountBrl,
                    'status' => $status,
                    'failure_reason' => $failureReason,
                    'converted_at' => $convertedAt,
                ]);
            }, 3);
        } catch (QueryException $exception) {
            DatabaseQueryExceptionMapper::throwDatabaseWriteException($exception);
        }
    }

    private function calculateBrl(string $amountOriginal, string $rate): string
    {
        if (function_exists('bcmul')) {
            return number_format((float) bcmul($amountOriginal, $rate, 6), 2, '.', '');
        }

        return number_format(((float) $amountOriginal) * ((float) $rate), 2, '.', '');
    }
}
