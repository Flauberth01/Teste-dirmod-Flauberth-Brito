<?php

namespace App\Support;

use App\Exceptions\DatabaseWriteException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class DatabaseQueryExceptionMapper
{
    public static function isConnectionIssue(QueryException $exception): bool
    {
        $sqlState = self::sqlState($exception);

        if ($sqlState === null) {
            return false;
        }

        if (str_starts_with($sqlState, '08')) {
            return true;
        }

        return in_array($sqlState, ['57P01', '57P02', '57P03'], true);
    }

    public static function isUniqueViolation(QueryException $exception): bool
    {
        return self::sqlState($exception) === '23505';
    }

    public static function sqlState(QueryException $exception): ?string
    {
        if (isset($exception->errorInfo[0]) && is_string($exception->errorInfo[0]) && $exception->errorInfo[0] !== '') {
            return $exception->errorInfo[0];
        }

        $code = $exception->getCode();

        return is_string($code) && $code !== '' ? $code : null;
    }

    public static function uniqueConstraint(QueryException $exception): ?string
    {
        $detail = $exception->errorInfo[2] ?? $exception->getMessage();

        if (! is_string($detail) || $detail === '') {
            return null;
        }

        if (preg_match('/unique constraint "([^"]+)"/i', $detail, $matches) !== 1) {
            return null;
        }

        return $matches[1] ?? null;
    }

    public static function throwValidationOrDatabaseException(QueryException $exception, array $constraintMap): never
    {
        if (self::isUniqueViolation($exception)) {
            $constraint = self::uniqueConstraint($exception);

            if ($constraint && isset($constraintMap[$constraint])) {
                $field = $constraintMap[$constraint]['field'];
                $message = $constraintMap[$constraint]['message'];

                throw ValidationException::withMessages([
                    $field => [$message],
                ]);
            }
        }

        self::throwDatabaseWriteException($exception);
    }

    public static function throwDatabaseWriteException(QueryException $exception): never
    {
        throw new DatabaseWriteException(previous: $exception);
    }
}
