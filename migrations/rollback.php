<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$tables = ['user_permissions', 'level_permissions', 'refresh_tokens', 'login_attempts', 'users', 'pages', 'levels', 'schema_migrations'];
$count = 0;

foreach ($tables as $table) {
    Capsule::schema()->dropIfExists($table);
    $count++;
}

echo "Rollback complete: {$count} tables dropped\n";
