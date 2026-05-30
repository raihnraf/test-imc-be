<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->create('levels', function (Blueprint $table): void {
    $table->id();
    $table->string('nama_level', 100);
    $table->text('deskripsi')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent();
});
