<?php

declare(strict_types=1);

namespace Imc\Domain\Level;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;

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

    public function findAll(array $filters = []): Builder
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
            'nama_level' => $data['nama_level'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->findById($id);
    }

    public function update(int $id, array $data): Level
    {
        $updateData = [];
        if (array_key_exists('nama_level', $data)) {
            $updateData['nama_level'] = $data['nama_level'];
        }
        if (array_key_exists('deskripsi', $data)) {
            $updateData['deskripsi'] = $data['deskripsi'];
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
            namaLevel: $data['nama_level'],
            deskripsi: $data['deskripsi'] ?? null,
            isActive: (bool) $data['is_active'],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            deletedAt: $data['deleted_at'] ?? null,
        );
    }
}
