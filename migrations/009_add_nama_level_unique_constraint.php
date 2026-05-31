<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::statement('CREATE UNIQUE INDEX IF NOT EXISTS levels_nama_level_unique ON levels (nama_level) WHERE deleted_at IS NULL');
