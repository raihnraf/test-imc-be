<?php

declare(strict_types=1);

namespace Imc\Domain\Shared;

class PaginatedResult
{
    /**
     * @param array<object> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
    ) {
    }

    public function totalPages(): int
    {
        return $this->total > 0 ? (int) ceil($this->total / $this->perPage) : 0;
    }
}
