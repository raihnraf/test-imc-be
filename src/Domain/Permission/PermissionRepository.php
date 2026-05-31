<?php

declare(strict_types=1);

namespace Imc\Domain\Permission;

use Illuminate\Database\Capsule\Manager as Capsule;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function hasAccess(int $userId, int $levelId, string $pagePath): bool
    {
        $row = Capsule::table('pages as p')
            ->selectRaw("
                CASE
                    WHEN up.is_granted = false THEN false
                    WHEN up.is_granted = true THEN true
                    WHEN lp.page_id IS NOT NULL THEN true
                    ELSE false
                END AS has_access
            ")
            ->leftJoin('level_permissions as lp', function ($join) use ($levelId) {
                $join->on('lp.page_id', '=', 'p.id')
                     ->where('lp.level_id', '=', $levelId);
            })
            ->leftJoin('user_permissions as up', function ($join) use ($userId) {
                $join->on('up.page_id', '=', 'p.id')
                     ->where('up.user_id', '=', $userId);
            })
            ->where('p.route_path', '=', $pagePath)
            ->where('p.is_active', '=', true)
            ->first();

        if ($row === null) {
            return false;
        }

        return (bool) $row->has_access;
    }

    public function getLevelMatrix(int $levelId): array
    {
        $rows = Capsule::table('pages as p')
            ->select('p.id', 'p.nama_page', 'p.route_path')
            ->selectRaw('CASE WHEN lp.page_id IS NOT NULL THEN true ELSE false END AS has_access')
            ->leftJoin('level_permissions as lp', function ($join) use ($levelId) {
                $join->on('lp.page_id', '=', 'p.id')
                     ->where('lp.level_id', '=', $levelId);
            })
            ->where('p.is_active', '=', true)
            ->orderBy('p.urutan_tampil')
            ->get();

        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'nama_page' => $row->nama_page,
            'route_path' => $row->route_path,
            'has_access' => (bool) $row->has_access,
        ])->toArray();
    }

    public function assignLevelPermission(int $levelId, int $pageId): void
    {
        Capsule::table('level_permissions')->insert([
            'level_id' => $levelId,
            'page_id' => $pageId,
        ]);
    }

    public function removeLevelPermission(int $levelId, int $pageId): bool
    {
        $affected = Capsule::table('level_permissions')
            ->where('level_id', $levelId)
            ->where('page_id', $pageId)
            ->delete();

        return $affected > 0;
    }

    public function assignUserPermission(int $userId, int $pageId, bool $isGranted): void
    {
        Capsule::table('user_permissions')->updateOrInsert(
            ['user_id' => $userId, 'page_id' => $pageId],
            ['is_granted' => $isGranted]
        );
    }

    public function removeUserPermission(int $userId, int $pageId): bool
    {
        $affected = Capsule::table('user_permissions')
            ->where('user_id', $userId)
            ->where('page_id', $pageId)
            ->delete();

        return $affected > 0;
    }

    public function getUserMatrix(int $userId, int $levelId): array
    {
        $rows = Capsule::table('pages as p')
            ->select('p.id', 'p.nama_page', 'p.route_path')
            ->selectRaw("
                CASE
                    WHEN up.is_granted = false THEN false
                    WHEN up.is_granted = true THEN true
                    WHEN lp.page_id IS NOT NULL THEN true
                    ELSE false
                END AS has_access
            ")
            ->leftJoin('level_permissions as lp', function ($join) use ($levelId) {
                $join->on('lp.page_id', '=', 'p.id')
                     ->where('lp.level_id', '=', $levelId);
            })
            ->leftJoin('user_permissions as up', function ($join) use ($userId) {
                $join->on('up.page_id', '=', 'p.id')
                     ->where('up.user_id', '=', $userId);
            })
            ->where('p.is_active', '=', true)
            ->orderBy('p.urutan_tampil')
            ->get();

        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'nama_page' => $row->nama_page,
            'route_path' => $row->route_path,
            'has_access' => (bool) $row->has_access,
        ])->toArray();
    }

    public function getAllPages(): array
    {
        $rows = Capsule::table('pages')
            ->select('id', 'nama_page', 'route_path')
            ->where('is_active', '=', true)
            ->orderBy('urutan_tampil')
            ->get();

        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'nama_page' => $row->nama_page,
            'route_path' => $row->route_path,
        ])->toArray();
    }
}
