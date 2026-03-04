<?php

namespace App\Actions;

use App\Exceptions\ExternalServiceUnavailableException;
use App\Exceptions\UnsupportedCurrencyException;
use App\Models\Expense;
use App\Services\ExchangeRateService;
use App\Support\DatabaseQueryExceptionMapper;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RetryExpenseConversionAction
{
    public function __construct(private readonly ExchangeRateService $exchangeRateService)
    {
    }

    public function execute(Expense $expense): Expense
    {
        if ($expense->status !== Expense::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Apenas despesas pendentes podem ser reprocessadas.'],
            ]);
        }

        try {
            $rate = $this->exchangeRateService->quoteToBrl($expense->currency);
            $amountBrl = $this->calculateBrl((string) $expense->amount_original, $rate);

            try {
                return DB::transaction(function () use ($expense, $rate, $amountBrl): Expense {
                    $expense->update([
                        'exchange_rate' => $rate,
                        'amount_brl' => $amountBrl,
                        'status' => Expense::STATUS_CONVERTED,
                        'failure_reason' => null,
                        'converted_at' => Carbon::now(),
                    ]);

                    return $expense->refresh();
                }, 3);
            } catch (QueryException $exception) {
                DatabaseQueryExceptionMapper::throwDatabaseWriteException($exception);
            }
        } catch (UnsupportedCurrencyException $exception) {
            throw ValidationException::withMessages([
                'currency' => ['Moeda não suportada para conversão.'],
            ]);
        } catch (ExternalServiceUnavailableException $exception) {
            try {
                DB::transaction(function () use ($expense): void {
                    $expense->update([
                        'status' => Expense::STATUS_PENDING,
                        'failure_reason' => 'External service unavailable',
                    ]);
                }, 3);
            } catch (QueryException $queryException) {
                DatabaseQueryExceptionMapper::throwDatabaseWriteException($queryException);
            }

            throw $exception;
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
