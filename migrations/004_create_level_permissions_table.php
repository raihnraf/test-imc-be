<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->create('level_permissions', function (Blueprint $table): void {
    $table->foreignId('level_id')->constrained('levels')->cascadeOnDelete();
    $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
    $table->primary(['level_id', 'page_id']);
});
