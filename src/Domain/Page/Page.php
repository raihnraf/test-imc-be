<?php

declare(strict_types=1);

namespace Imc\Domain\Page;

class Page
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $routePath = '',
        public ?string $description = null,
        public int $displayOrder = 0,
        public bool $isActive = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'route_path' => $this->routePath,
            'description' => $this->description,
            'display_order' => $this->displayOrder,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
