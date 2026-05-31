<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use Illuminate\Database\Capsule\Manager as Capsule;

$explicitHost = $_ENV['DB_HOST'] ?? null;
$isDocker = $explicitHost !== null && $explicitHost !== 'localhost' && $explicitHost !== '127.0.0.1';

$capsule = new Capsule();
$capsule->addConnection([
    'driver' => 'pgsql',
    'host' => $explicitHost ?? ($isDocker ? 'db' : 'localhost'),
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_DATABASE'] ?? 'imc',
    'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? 'postgres',
    'charset' => 'utf8',
    'prefix' => '',
    'schema' => 'public',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();
