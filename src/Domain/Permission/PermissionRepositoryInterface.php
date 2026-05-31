<?php

declare(strict_types=1);

namespace Imc\Domain\Permission;

interface PermissionRepositoryInterface
{
    public function hasAccess(int $userId, int $levelId, string $pagePath): bool;
    public function getLevelMatrix(int $levelId): array;
    public function assignLevelPermission(int $levelId, int $pageId): void;
    public function removeLevelPermission(int $levelId, int $pageId): bool;
    public function assignUserPermission(int $userId, int $pageId, bool $isGranted): void;
    public function removeUserPermission(int $userId, int $pageId): bool;
    public function getUserMatrix(int $userId, int $levelId): array;
    public function getAllPages(): array;
}
