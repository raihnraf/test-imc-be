<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->create('users', function (Blueprint $table): void {
    $table->id();
    $table->string('nama_lengkap', 150);
    $table->string('username', 50)->unique();
    $table->string('email', 100)->unique();
    $table->string('password', 255);
    $table->foreignId('level_id')->nullable()->constrained('levels')->nullOnDelete();
    $table->boolean('is_active')->default(true);
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent();
});
