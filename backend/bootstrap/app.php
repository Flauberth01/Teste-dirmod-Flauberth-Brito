<?php

use App\Exceptions\DatabaseWriteException;
use App\Exceptions\ExternalServiceUnavailableException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(static fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Validation error',
                'errors' => $exception->errors(),
                'code' => 'VALIDATION_ERROR',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => null,
                'code' => 'UNAUTHENTICATED',
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Forbidden',
                'errors' => null,
                'code' => 'FORBIDDEN',
            ], Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Forbidden',
                'errors' => null,
                'code' => 'FORBIDDEN',
            ], Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (ExternalServiceUnavailableException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'External service unavailable',
                'errors' => null,
                'code' => 'EXTERNAL_SERVICE_UNAVAILABLE',
            ], $exception->status());
        });

        $exceptions->render(function (DatabaseWriteException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Erro ao processar',
                'errors' => null,
                'code' => 'GENERIC_ERROR',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Erro ao processar',
                'errors' => null,
                'code' => 'GENERIC_ERROR',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
