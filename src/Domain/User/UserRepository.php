<?php

declare(strict_types=1);

namespace Imc\Domain\User;

use Illuminate\Database\Capsule\Manager as Capsule;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private Capsule $capsule) {}

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

    public function findAll(array $filters = []): array
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function create(array $data): User
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function update(int $id, array $data): User
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function delete(int $id): bool
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function existsByUsername(string $username, ?int $excludeId = null): bool
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        throw new \RuntimeException('Not implemented in Phase 1');
    }

    private function mapToEntity(array $data): User
    {
        return new User(
            id: (int) $data['id'],
            namaLengkap: $data['nama_lengkap'],
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
