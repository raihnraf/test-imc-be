<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

Capsule::schema()->create('pages', function (Blueprint $table): void {
    $table->id();
    $table->string('nama_page', 100);
    $table->string('route_path', 255)->unique();
    $table->text('deskripsi')->nullable();
    $table->integer('urutan_tampil')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('updated_at')->useCurrent();
});
