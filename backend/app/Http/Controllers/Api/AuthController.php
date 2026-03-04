<?php

namespace App\Http\Controllers\Api;

use App\Actions\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\DatabaseQueryExceptionMapper;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request, RegisterUserAction $action): JsonResponse
    {
        $user = $action->execute($request->validated());
        $token = $this->createTokenInTransaction($user);

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            throw new AuthenticationException();
        }

        $token = $this->createTokenInTransaction($user);

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): Response
    {
        try {
            DB::transaction(function () use ($request): void {
                $request->user()?->currentAccessToken()?->delete();
            }, 3);
        } catch (QueryException $exception) {
            DatabaseQueryExceptionMapper::throwDatabaseWriteException($exception);
        }

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function createTokenInTransaction(User $user): string
    {
        try {
            return DB::transaction(fn (): string => $user->createToken('api-token')->plainTextToken, 3);
        } catch (QueryException $exception) {
            DatabaseQueryExceptionMapper::throwDatabaseWriteException($exception);
        }
    }
}
