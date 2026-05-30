<?php

declare(strict_types=1);

namespace Imc\Domain\Level;

class Level
{
    public function __construct(
        public ?int $id = null,
        public string $namaLevel = '',
        public ?string $deskripsi = null,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
