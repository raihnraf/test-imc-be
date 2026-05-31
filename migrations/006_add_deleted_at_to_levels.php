<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->table('levels', function (Blueprint $table): void {
    $table->timestamp('deleted_at')->nullable()->after('updated_at');
});
