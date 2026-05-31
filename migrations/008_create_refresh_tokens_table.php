<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->create('refresh_tokens', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('token_hash', 64)->unique();
    $table->timestamp('expires_at');
    $table->timestamp('revoked_at')->nullable();
    $table->timestamp('created_at')->useCurrent();
    $table->index('user_id', 'idx_refresh_tokens_user');
});
