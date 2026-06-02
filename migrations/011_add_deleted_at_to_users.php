<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->table('users', function (Blueprint $table): void {
    $table->timestamp('deleted_at')->nullable()->after('updated_at');
    $table->index('deleted_at', 'idx_users_deleted_at');
});
