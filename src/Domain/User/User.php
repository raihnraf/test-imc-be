<?php

declare(strict_types=1);

namespace Imc\Domain\User;

class User
{
    public function __construct(
        public ?int $id = null,
        public string $namaLengkap = '',
        public string $username = '',
        public string $email = '',
        public string $password = '',
        public ?int $levelId = null,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
