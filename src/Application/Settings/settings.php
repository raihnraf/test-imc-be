<?php

declare(strict_types=1);

// Auto-detect environment:
// - Docker: DB_HOST is explicitly set (e.g. 'db') via docker-compose environment
// - Local:  DB_HOST is not set → default to localhost
// This allows the same codebase to work in both environments without .env changes.
$explicitHost = $_ENV['DB_HOST'] ?? null;
$isDocker = $explicitHost !== null && $explicitHost !== 'localhost' && $explicitHost !== '127.0.0.1';

return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
    ],

    'db' => [
        'driver' => 'pgsql',
        'host' => $explicitHost ?? ($isDocker ? 'db' : 'localhost'),
        'port' => $_ENV['DB_PORT'] ?? '5432',
        'database' => $_ENV['DB_DATABASE'] ?? 'imc',
        'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
        'password' => $_ENV['DB_PASSWORD'] ?? 'postgres',
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
    ],

    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
        'expiry' => (int) ($_ENV['JWT_EXPIRY'] ?? 900),
        'access_token_expiry' => (int) ($_ENV['JWT_ACCESS_TOKEN_EXPIRY'] ?? 900),
        'refresh_token_expiry' => (int) ($_ENV['JWT_REFRESH_TOKEN_EXPIRY'] ?? 604800),
    ],
];
