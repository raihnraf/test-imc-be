<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->create('login_attempts', function (Blueprint $table): void {
    $table->string('ip_address', 45);
    $table->timestamp('attempted_at')->useCurrent();
    $table->index(['ip_address', 'attempted_at'], 'idx_login_attempts_ip_time');
});
