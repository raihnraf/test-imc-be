<?php

declare(strict_types=1);

namespace Imc\Domain\Page;

use Imc\Domain\Shared\PaginatedResult;

interface PageRepositoryInterface
{
    public function findById(int $id): ?Page;
    public function count(array $filters = []): int;
    public function findPaginated(array $filters = [], int $page = 1, int $perPage = 15): PaginatedResult;
    public function create(array $data): Page;
    public function update(int $id, array $data): Page;
    public function delete(int $id): bool;
    public function findByRoute(string $route): ?Page;
    public function existsByRoute(string $route, ?int $excludeId = null): bool;
}
