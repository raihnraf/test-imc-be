<?php

declare(strict_types=1);

namespace Imc\Domain\Level;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Imc\Domain\Shared\PaginatedResult;

class LevelRepository implements LevelRepositoryInterface
{
    public function findById(int $id): ?Level
    {
        $row = Capsule::table('levels')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        return $row ? $this->mapToEntity((array) $row) : null;
    }

    public function count(array $filters = []): int
    {
        return $this->buildFilteredQuery($filters)->count();
    }

    public function existsByNama(string $namaLevel, ?int $excludeId = null): bool
    {
        $query = Capsule::table('levels')
            ->where('nama_level', $namaLevel)
            ->whereNull('deleted_at');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function countActiveUsers(int $levelId): int
    {
        return (int) Capsule::table('users')
            ->where('level_id', $levelId)
            ->where('is_active', true)
            ->count();
    }

    public function isSuperAdmin(int $levelId): bool
    {
        return $levelId === 1;
    }

    public function findPaginated(array $filters = [], int $page = 1, int $perPage = 15): PaginatedResult
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));
        $query = $this->buildFilteredQuery($filters);
        $total = $query->count();
        $rows = $total > 0
            ? (clone $query)->forPage($page, $perPage)->get()
            : collect();

        return new PaginatedResult(
            $rows->map(fn ($row) => $this->mapToEntity((array) $row))->all(),
            $page,
            $perPage,
            $total,
        );
    }

    private function buildFilteredQuery(array $filters = []): Builder
    {
        $query = Capsule::table('levels')->whereNull('deleted_at');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_level', 'ILIKE', "%{$search}%")
                  ->orWhere('deskripsi', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    public function create(array $data): Level
    {
        $id = Capsule::table('levels')->insertGetId([
            'nama_level' => $data['name'],
            'deskripsi' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->findById($id);
    }

    public function update(int $id, array $data): Level
    {
        $updateData = [];
        if (array_key_exists('name', $data)) {
            $updateData['nama_level'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['deskripsi'] = $data['description'];
        }
        if (array_key_exists('is_active', $data)) {
            $updateData['is_active'] = $data['is_active'];
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        Capsule::table('levels')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update($updateData);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $affected = Capsule::table('levels')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return $affected > 0;
    }

    private function mapToEntity(array $data): Level
    {
        return new Level(
            id: (int) $data['id'],
            name: $data['nama_level'],
            description: $data['deskripsi'] ?? null,
            isActive: (bool) $data['is_active'],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            deletedAt: $data['deleted_at'] ?? null,
        );
    }
}
