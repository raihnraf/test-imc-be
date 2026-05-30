<?php

declare(strict_types=1);

namespace Imc\Domain\Page;

use Illuminate\Database\Capsule\Manager as Capsule;

class PageRepository implements PageRepositoryInterface
{
    public function __construct(private Capsule $capsule) {}

    public function findById(int $id): ?Page
    {
        $row = Capsule::table('pages')->where('id', $id)->first();
        return $row ? $this->mapToEntity((array) $row) : null;
    }

    public function findAll(array $filters = []): array
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function create(array $data): Page
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function update(int $id, array $data): Page
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function delete(int $id): bool
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function findByRoute(string $route): ?Page
    {
        $row = Capsule::table('pages')->where('route_path', $route)->first();
        return $row ? $this->mapToEntity((array) $row) : null;
    }

    public function existsByRoute(string $route, ?int $excludeId = null): bool
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    private function mapToEntity(array $data): Page
    {
        return new Page(
            id: (int) $data['id'],
            namaPage: $data['nama_page'],
            routePath: $data['route_path'],
            deskripsi: $data['deskripsi'] ?? null,
            urutanTampil: (int) ($data['urutan_tampil'] ?? 0),
            isActive: (bool) $data['is_active'],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
