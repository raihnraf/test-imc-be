<?php

declare(strict_types=1);

namespace Imc\Domain\Page;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Imc\Domain\Shared\PaginatedResult;

class PageRepository implements PageRepositoryInterface
{
    public function findById(int $id): ?Page
    {
        $row = Capsule::table('pages')->where('id', $id)->first();
        return $row ? $this->mapToEntity((array) $row) : null;
    }

    public function count(array $filters = []): int
    {
        return $this->buildFilteredQuery($filters)->count();
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
        $query = Capsule::table('pages')->orderBy('urutan_tampil', 'asc')->orderBy('id', 'asc');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_page', 'ILIKE', "%{$search}%")
                  ->orWhere('deskripsi', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    public function create(array $data): Page
    {
        $id = Capsule::table('pages')->insertGetId([
            'nama_page' => $data['name'],
            'route_path' => $data['route_path'],
            'deskripsi' => $data['description'] ?? null,
            'urutan_tampil' => $data['display_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->findById($id);
    }

    public function update(int $id, array $data): Page
    {
        $updateData = [];

        if (array_key_exists('name', $data)) {
            $updateData['nama_page'] = $data['name'];
        }
        if (array_key_exists('route_path', $data)) {
            $updateData['route_path'] = $data['route_path'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['deskripsi'] = $data['description'];
        }
        if (array_key_exists('display_order', $data)) {
            $updateData['urutan_tampil'] = $data['display_order'];
        }
        if (array_key_exists('is_active', $data)) {
            $updateData['is_active'] = $data['is_active'];
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        Capsule::table('pages')->where('id', $id)->update($updateData);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return Capsule::table('pages')->where('id', $id)->delete() > 0;
    }

    public function findByRoute(string $route): ?Page
    {
        $row = Capsule::table('pages')->where('route_path', $route)->first();
        return $row ? $this->mapToEntity((array) $row) : null;
    }

    public function existsByRoute(string $route, ?int $excludeId = null): bool
    {
        $query = Capsule::table('pages')->where('route_path', $route);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function mapToEntity(array $data): Page
    {
        return new Page(
            id: (int) $data['id'],
            name: $data['nama_page'],
            routePath: $data['route_path'],
            description: $data['deskripsi'] ?? null,
            displayOrder: (int) ($data['urutan_tampil'] ?? 0),
            isActive: (bool) $data['is_active'],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
