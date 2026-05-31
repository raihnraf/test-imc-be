<?php

declare(strict_types=1);

namespace Imc\Domain\Level;

class Level
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public ?string $description = null,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $deletedAt = null,
    ) {
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
