<?php

/**
 * Loads config/database.php with the given environment, then restores the previous one.
 *
 * @param  array<string, string|null>  $vars  null unsets the variable
 * @return array<string, mixed> the pgsql connection
 */
function pgsqlConnectionWith(array $vars): array
{
    $previous = [];
    foreach ($vars as $name => $value) {
        $previous[$name] = getenv($name);
        $value === null ? putenv($name) : putenv("$name=$value");
        unset($_ENV[$name], $_SERVER[$name]);
    }

    try {
        return (require base_path('config/database.php'))['connections']['pgsql'];
    } finally {
        foreach ($previous as $name => $value) {
            $value === false ? putenv($name) : putenv("$name=$value");
        }
    }
}

it('reads the postgres image variables when DB_* are not set (production env files)', function () {
    $pgsql = pgsqlConnectionWith([
        'DB_DATABASE' => null, 'DB_USERNAME' => null, 'DB_PASSWORD' => null,
        'POSTGRES_DB' => 'prod_db', 'POSTGRES_USER' => 'prod_user', 'POSTGRES_PASSWORD' => 'prod_pass',
    ]);

    expect($pgsql['database'])->toBe('prod_db')
        ->and($pgsql['username'])->toBe('prod_user')
        ->and($pgsql['password'])->toBe('prod_pass');
});

it('prefers DB_* over the postgres image variables (local .env)', function () {
    $pgsql = pgsqlConnectionWith([
        'DB_DATABASE' => 'dev_db', 'DB_USERNAME' => 'dev_user', 'DB_PASSWORD' => 'dev_pass',
        'POSTGRES_DB' => 'prod_db', 'POSTGRES_USER' => 'prod_user', 'POSTGRES_PASSWORD' => 'prod_pass',
    ]);

    expect($pgsql['database'])->toBe('dev_db')
        ->and($pgsql['username'])->toBe('dev_user')
        ->and($pgsql['password'])->toBe('dev_pass');
});
