<?php

declare(strict_types=1);

namespace Imc\Domain\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByUsernameOrEmail(string $login): ?User;
    public function findAll(array $filters = []): array;
    public function create(array $data): User;
    public function update(int $id, array $data): User;
    public function delete(int $id): bool;
    public function existsByUsername(string $username, ?int $excludeId = null): bool;
    public function existsByEmail(string $email, ?int $excludeId = null): bool;
}
