<?php

declare(strict_types=1);

require __DIR__ . '/../migrations/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::table('level_permissions')->truncate();
Capsule::table('user_permissions')->truncate();
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
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';
Capsule::table('users')->insert([
    'nama_lengkap' => 'Super Admin',
    'username' => 'admin',
    'email' => 'admin@imc.local',
    'password' => password_hash($adminPassword, PASSWORD_ARGON2ID),
    'level_id' => 1,
    'is_active' => true,
]);

// Grant all pages to Super Admin (level_id = 1)
$pages = Capsule::table('pages')->get();
$permissions = [];
foreach ($pages as $page) {
    $permissions[] = ['level_id' => 1, 'page_id' => $page->id];
}
Capsule::table('level_permissions')->insert($permissions);

$levelsCount = Capsule::table('levels')->count();
$pagesCount = Capsule::table('pages')->count();
$usersCount = Capsule::table('users')->count();
$permsCount = Capsule::table('level_permissions')->count();

echo "Seed complete: {$levelsCount} levels, {$pagesCount} pages, {$usersCount} admin user created, {$permsCount} level permissions granted\n";
