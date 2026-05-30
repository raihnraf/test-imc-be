<?php

declare(strict_types=1);

namespace Imc\Domain\Level;

use Illuminate\Database\Capsule\Manager as Capsule;

class LevelRepository implements LevelRepositoryInterface
{
    public function __construct(private Capsule $capsule) {}

    public function findById(int $id): ?Level
    {
        $row = Capsule::table('levels')->where('id', $id)->first();
        return $row ? $this->mapToEntity((array) $row) : null;
    }

    public function findAll(array $filters = []): array
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function create(array $data): Level
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function update(int $id, array $data): Level
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function delete(int $id): bool
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    private function mapToEntity(array $data): Level
    {
        return new Level(
            id: (int) $data['id'],
            namaLevel: $data['nama_level'],
            deskripsi: $data['deskripsi'] ?? null,
            isActive: (bool) $data['is_active'],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
