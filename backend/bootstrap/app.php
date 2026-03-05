<?php

use App\Exceptions\DatabaseWriteException;
use App\Exceptions\ExternalServiceUnavailableException;
use App\Support\DatabaseQueryExceptionMapper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $errorResponse = static function (Request $request, string $message, ?array $errors, string $code, int $status) {
            $requestId = $request->attributes->get('request_id');

            if (! is_string($requestId) || $requestId === '') {
                $requestId = trim((string) $request->headers->get('X-Request-Id', ''));

                if ($requestId === '') {
                    $requestId = (string) Str::ulid();
                }

                $request->attributes->set('request_id', $requestId);
            }

            return response()->json([
                'message' => $message,
                'errors' => $errors,
                'code' => $code,
                'status' => $status,
                'request_id' => $requestId,
            ], $status)->header('X-Request-Id', $requestId);
        };

        $exceptions->render(function (ValidationException $exception, Request $request) use ($errorResponse) {
            if (! $request->expectsJson()) {
                return null;
            }

            return $errorResponse(
                $request,
                'Validation error',
                $exception->errors(),
                'VALIDATION_ERROR',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($errorResponse) {
            if (! $request->expectsJson()) {
                return null;
            }

            return $errorResponse(
                $request,
                'Unauthenticated',
                null,
                'UNAUTHENTICATED',
                Response::HTTP_UNAUTHORIZED
            );
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($errorResponse) {
            if (! $request->expectsJson()) {
                return null;
            }

            return $errorResponse(
                $request,
                'Forbidden',
                null,
                'FORBIDDEN',
                Response::HTTP_FORBIDDEN
            );
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) use ($errorResponse) {
            if (! $request->expectsJson()) {
                return null;
            }

            return $errorResponse(
                $request,
                'Forbidden',
                null,
                'FORBIDDEN',
                Response::HTTP_FORBIDDEN
            );
        });

        $exceptions->render(function (ExternalServiceUnavailableException $exception, Request $request) use ($errorResponse) {
            if (! $request->expectsJson()) {
                return null;
            }

            return $errorResponse(
                $request,
                'External service unavailable',
                null,
                'EXTERNAL_SERVICE_UNAVAILABLE',
                $exception->status()
            );
        });

        $exceptions->render(function (DatabaseWriteException $exception, Request $request) use ($errorResponse) {
            if (! $request->expectsJson()) {
                return null;
            }

            $previous = $exception->getPrevious();

            if ($previous instanceof QueryException && DatabaseQueryExceptionMapper::isConnectionIssue($previous)) {
                return $errorResponse(
                    $request,
                    'Database unavailable',
                    null,
                    'DATABASE_UNAVAILABLE',
                    Response::HTTP_SERVICE_UNAVAILABLE
                );
            }

            return $errorResponse(
                $request,
                'Database write failed',
                null,
                'DATABASE_WRITE_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        });

        $exceptions->render(function (QueryException $exception, Request $request) use ($errorResponse) {
            if (! $request->expectsJson()) {
                return null;
            }

            if (DatabaseQueryExceptionMapper::isConnectionIssue($exception)) {
                return $errorResponse(
                    $request,
                    'Database unavailable',
                    null,
                    'DATABASE_UNAVAILABLE',
                    Response::HTTP_SERVICE_UNAVAILABLE
                );
            }

            return $errorResponse(
                $request,
                'Database query failed',
                null,
                'DATABASE_QUERY_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        });

        $exceptions->render(function (\Throwable $exception, Request $request) use ($errorResponse) {
            if (! $request->expectsJson()) {
                return null;
            }

            return $errorResponse(
                $request,
                'Erro ao processar',
                null,
                'INTERNAL_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        });
    })->create();
