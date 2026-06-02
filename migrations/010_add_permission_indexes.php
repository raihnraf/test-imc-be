<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->table('user_permissions', function (Blueprint $table): void {
    $table->index('page_id', 'idx_user_permissions_page_id');
});

Capsule::schema()->table('level_permissions', function (Blueprint $table): void {
    $table->index('page_id', 'idx_level_permissions_page_id');
});
