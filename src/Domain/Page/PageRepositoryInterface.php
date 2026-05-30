<?php

declare(strict_types=1);

namespace Imc\Domain\Page;

interface PageRepositoryInterface
{
    public function findById(int $id): ?Page;
    public function findAll(array $filters = []): array;
    public function create(array $data): Page;
    public function update(int $id, array $data): Page;
    public function delete(int $id): bool;
    public function findByRoute(string $route): ?Page;
    public function existsByRoute(string $route, ?int $excludeId = null): bool;
}
