<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateCpfRealtimeRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CpfValidationController extends Controller
{
    public function __invoke(ValidateCpfRealtimeRequest $request): JsonResponse
    {
        $cpf = $request->validated('cpf');
        $alreadyExists = User::query()->where('cpf', $cpf)->exists();

        return response()->json([
            'data' => [
                'cpf' => $cpf,
                'valid' => true,
                'available' => ! $alreadyExists,
                'reason' => $alreadyExists ? 'CPF_ALREADY_EXISTS' : null,
            ],
        ]);
    }
}
