<?php

declare(strict_types=1);

namespace Imc\Domain\User;

class User
{
    public function __construct(
        public ?int $id = null,
        public string $fullName = '',
        public string $username = '',
        public string $email = '',
        private string $password = '',
        public ?int $levelId = null,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
    ) {
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }

    public function getPasswordHash(): string
    {
        return $this->password;
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'username' => $this->username,
            'email' => $this->email,
            'level_id' => $this->levelId,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
