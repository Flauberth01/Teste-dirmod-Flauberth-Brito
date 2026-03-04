<?php

use App\Exceptions\DatabaseWriteException;
use App\Support\DatabaseQueryExceptionMapper;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

function makeQueryException(string $sqlState, string $detail): QueryException
{
    $previous = new PDOException($detail);
    $previous->errorInfo = [$sqlState, 7, $detail];

    return new QueryException('pgsql', 'insert into users ...', [], $previous);
}

it('classifies unique violation by sqlstate', function () {
    $exception = makeQueryException(
        '23505',
        'SQLSTATE[23505]: duplicate key value violates unique constraint "users_cpf_unique"'
    );

    expect(DatabaseQueryExceptionMapper::isUniqueViolation($exception))->toBeTrue();
    expect(DatabaseQueryExceptionMapper::sqlState($exception))->toBe('23505');
    expect(DatabaseQueryExceptionMapper::uniqueConstraint($exception))->toBe('users_cpf_unique');
});

it('maps unique violation to validation error by field', function () {
    $exception = makeQueryException(
        '23505',
        'SQLSTATE[23505]: duplicate key value violates unique constraint "users_cpf_unique"'
    );

    try {
        DatabaseQueryExceptionMapper::throwValidationOrDatabaseException($exception, [
            'users_cpf_unique' => [
                'field' => 'cpf',
                'message' => 'Este CPF já está cadastrado.',
            ],
        ]);

        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $validationException) {
        expect($validationException->errors())->toBe([
            'cpf' => ['Este CPF já está cadastrado.'],
        ]);
    }
});

it('maps non unique query exception to database write exception', function () {
    $exception = makeQueryException(
        '08006',
        'SQLSTATE[08006]: connection failure'
    );

    expect(function () use ($exception): void {
        DatabaseQueryExceptionMapper::throwValidationOrDatabaseException($exception, []);
    })->toThrow(DatabaseWriteException::class);
});
