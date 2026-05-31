<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Create schema_migrations tracking table if not exists
if (!Capsule::getSchemaBuilder()->hasTable('schema_migrations')) {
    Capsule::getSchemaBuilder()->create('schema_migrations', function ($table) {
        $table->string('version')->primary();
        $table->string('filename');
        $table->timestamp('applied_at')->useCurrent();
    });
}

// Get already applied migrations
$applied = Capsule::table('schema_migrations')->pluck('version')->toArray();

$migrations = [
    '001_create_levels_table.php',
    '002_create_users_table.php',
    '003_create_pages_table.php',
    '004_create_level_permissions_table.php',
    '005_create_user_permissions_table.php',
    '006_add_deleted_at_to_levels.php',
    '007_create_login_attempts_table.php',
    '008_create_refresh_tokens_table.php',
    '009_add_nama_level_unique_constraint.php',
];

$count = 0;
$skipped = 0;

foreach ($migrations as $filename) {
    $version = pathinfo($filename, PATHINFO_FILENAME);

    if (in_array($version, $applied, true)) {
        echo "Skipping {$filename} (already applied)\n";
        $skipped++;
        continue;
    }

    require __DIR__ . '/' . $filename;

    Capsule::table('schema_migrations')->insert([
        'version' => $version,
        'filename' => $filename,
    ]);

    echo "Applied {$filename}\n";
    $count++;
}

echo "\nMigration complete: {$count} applied, {$skipped} skipped\n";
