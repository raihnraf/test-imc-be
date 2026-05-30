<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule();
$capsule->addConnection([
    'driver' => 'pgsql',
    'host' => $_ENV['DB_HOST'] ?? 'db',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_DATABASE'] ?? 'imc',
    'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8',
    'prefix' => '',
    'schema' => 'public',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$migrations = [
    __DIR__ . '/001_create_levels_table.php',
    __DIR__ . '/002_create_users_table.php',
    __DIR__ . '/003_create_pages_table.php',
    __DIR__ . '/004_create_level_permissions_table.php',
    __DIR__ . '/005_create_user_permissions_table.php',
];

$count = 0;
foreach ($migrations as $migration) {
    require $migration;
    $count++;
}

echo "Migration complete: {$count} tables created\n";
