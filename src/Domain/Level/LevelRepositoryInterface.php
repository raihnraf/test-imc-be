<?php

declare(strict_types=1);

namespace Imc\Domain\Level;

use Imc\Domain\Shared\PaginatedResult;

interface LevelRepositoryInterface
{
    public function findById(int $id): ?Level;
    public function count(array $filters = []): int;
    public function findPaginated(array $filters = [], int $page = 1, int $perPage = 15): PaginatedResult;
    public function existsByNama(string $namaLevel, ?int $excludeId = null): bool;
    public function countActiveUsers(int $levelId): int;
    public function isSuperAdmin(int $levelId): bool;
    public function create(array $data): Level;
    public function update(int $id, array $data): Level;
    public function delete(int $id): bool;
}
