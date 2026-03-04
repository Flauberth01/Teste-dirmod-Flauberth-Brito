<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CepController;
use App\Http\Controllers\Api\CpfValidationController;
use App\Http\Controllers\Api\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::get('/cep/{cep}', CepController::class);
Route::get('/validation/cpf/{cpf}', CpfValidationController::class)->middleware('throttle:30,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show']);
    Route::post('/expenses/{expense}/retry-conversion', [ExpenseController::class, 'retry']);
});
