<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidCepException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveCepRequest;
use App\Services\CepService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CepController extends Controller
{
    public function __invoke(ResolveCepRequest $request, CepService $service): JsonResponse
    {
        try {
            $address = $service->lookup($request->validated('cep'));
        } catch (InvalidCepException $exception) {
            throw ValidationException::withMessages([
                'cep' => ['CEP inválido ou inexistente.'],
            ]);
        }

        return response()->json([
            'data' => $address,
        ]);
    }
}
