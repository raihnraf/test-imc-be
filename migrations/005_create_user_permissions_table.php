<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->create('user_permissions', function (Blueprint $table): void {
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
    $table->boolean('is_granted');
    $table->primary(['user_id', 'page_id']);
});
