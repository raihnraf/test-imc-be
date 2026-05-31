<?php

declare(strict_types=1);

return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
    ],

    'db' => [
        'driver' => 'pgsql',
        'host' => $_ENV['DB_HOST'] ?? 'db',
        'port' => $_ENV['DB_PORT'] ?? '5432',
        'database' => $_ENV['DB_DATABASE'] ?? 'imc',
        'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
    ],

    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
        'expiry' => (int) ($_ENV['JWT_EXPIRY'] ?? 3600),
        'access_token_expiry' => (int) ($_ENV['JWT_ACCESS_TOKEN_EXPIRY'] ?? 900),
        'refresh_token_expiry' => (int) ($_ENV['JWT_REFRESH_TOKEN_EXPIRY'] ?? 604800),
    ],
];
