<?php

declare(strict_types=1);

namespace Imc\Domain\User;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Imc\Domain\Shared\PaginatedResult;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        $row = Capsule::table('users')->where('id', $id)->first();
        return $row ? $this->mapToEntity((array) $row) : null;
    }

    public function findByUsernameOrEmail(string $login): ?User
    {
        $row = Capsule::table('users')
            ->where('username', $login)
            ->orWhere('email', $login)
            ->first();

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
        $query = Capsule::table('users');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                  ->orWhere('username', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['level_id'])) {
            $query->where('level_id', (int) $filters['level_id']);
        }

        return $query;
    }

    public function create(array $data): User
    {
        $id = Capsule::table('users')->insertGetId([
            'nama_lengkap' => $data['full_name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
            'level_id' => $data['level_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->findById($id);
    }

    public function update(int $id, array $data): User
    {
        $updateData = [];

        if (array_key_exists('full_name', $data)) {
            $updateData['nama_lengkap'] = $data['full_name'];
        }
        if (array_key_exists('username', $data)) {
            $updateData['username'] = $data['username'];
        }
        if (array_key_exists('email', $data)) {
            $updateData['email'] = $data['email'];
        }
        if (array_key_exists('password', $data)) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }
        if (array_key_exists('level_id', $data)) {
            $updateData['level_id'] = $data['level_id'];
        }
        if (array_key_exists('is_active', $data)) {
            $updateData['is_active'] = $data['is_active'];
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        Capsule::table('users')->where('id', $id)->update($updateData);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return Capsule::table('users')->where('id', $id)->delete() > 0;
    }

    public function existsByUsername(string $username, ?int $excludeId = null): bool
    {
        $query = Capsule::table('users')->where('username', $username);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $query = Capsule::table('users')->where('email', $email);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function mapToEntity(array $data): User
    {
        return new User(
            id: (int) $data['id'],
            fullName: $data['nama_lengkap'],
            username: $data['username'],
            email: $data['email'],
            password: $data['password'],
            levelId: isset($data['level_id']) ? (int) $data['level_id'] : null,
            isActive: (bool) $data['is_active'],
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
