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

Capsule::table('levels')->truncate();
Capsule::table('pages')->truncate();
Capsule::table('users')->truncate();

// Insert levels
Capsule::table('levels')->insert([
    ['nama_level' => 'Super Admin', 'deskripsi' => 'Full system access', 'is_active' => true],
    ['nama_level' => 'Manager', 'deskripsi' => 'Content and user management', 'is_active' => true],
    ['nama_level' => 'Staff', 'deskripsi' => 'Limited operational access', 'is_active' => true],
    ['nama_level' => 'Viewer', 'deskripsi' => 'Read-only access', 'is_active' => true],
]);

// Insert pages
Capsule::table('pages')->insert([
    ['nama_page' => 'Dashboard', 'route_path' => '/dashboard', 'deskripsi' => 'Main dashboard', 'urutan_tampil' => 1, 'is_active' => true],
    ['nama_page' => 'User Management', 'route_path' => '/users', 'deskripsi' => 'Manage system users', 'urutan_tampil' => 2, 'is_active' => true],
    ['nama_page' => 'Level Management', 'route_path' => '/levels', 'deskripsi' => 'Manage access levels', 'urutan_tampil' => 3, 'is_active' => true],
    ['nama_page' => 'Page Management', 'route_path' => '/pages', 'deskripsi' => 'Manage menu pages', 'urutan_tampil' => 4, 'is_active' => true],
    ['nama_page' => 'Permission Matrix', 'route_path' => '/permissions', 'deskripsi' => 'View permission matrix', 'urutan_tampil' => 5, 'is_active' => true],
    ['nama_page' => 'My Profile', 'route_path' => '/me', 'deskripsi' => 'User profile page', 'urutan_tampil' => 6, 'is_active' => true],
]);

// Insert default admin
Capsule::table('users')->insert([
    'nama_lengkap' => 'Super Admin',
    'username' => 'admin',
    'email' => 'admin@imc.local',
    'password' => password_hash('admin123', PASSWORD_ARGON2ID),
    'level_id' => 1,
    'is_active' => true,
]);

$levelsCount = Capsule::table('levels')->count();
$pagesCount = Capsule::table('pages')->count();
$usersCount = Capsule::table('users')->count();

echo "Seed complete: {$levelsCount} levels, {$pagesCount} pages, {$usersCount} admin user created\n";
