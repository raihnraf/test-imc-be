<?php

declare(strict_types=1);

namespace Imc\Domain\Level;

use Illuminate\Database\Query\Builder;

interface LevelRepositoryInterface
{
    public function findById(int $id): ?Level;
    public function findAll(array $filters = []): Builder;
    public function create(array $data): Level;
    public function update(int $id, array $data): Level;
    public function delete(int $id): bool;
}
