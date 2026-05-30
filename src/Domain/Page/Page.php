<?php

declare(strict_types=1);

namespace Imc\Domain\Page;

class Page
{
    public function __construct(
        public ?int $id = null,
        public string $namaPage = '',
        public string $routePath = '',
        public ?string $deskripsi = null,
        public int $urutanTampil = 0,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
