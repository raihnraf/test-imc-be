<?php

declare(strict_types=1);

namespace Imc\Tests\Unit\Domain;

use Illuminate\Database\Capsule\Manager as Capsule;
use Imc\Domain\Permission\PermissionRepository;
use Imc\Tests\TestCase;

class PermissionRepositoryTest extends TestCase
{
    private PermissionRepository $repo;
    private int $testUserId;
    private int $testLevelId;
    private int $testPageId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new PermissionRepository();

        Capsule::table('user_permissions')->truncate();
        Capsule::table('level_permissions')->truncate();

        $suffix = substr(uniqid('', true), 0, 8);

        if (!Capsule::table('levels')->where('nama_level', "Test Level {$suffix}")->exists()) {
            Capsule::table('levels')->insert([
                'nama_level' => "Test Level {$suffix}",
                'deskripsi' => 'Test level',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->testLevelId = (int) Capsule::table('levels')->where('nama_level', "Test Level {$suffix}")->value('id');

        if (!Capsule::table('pages')->where('route_path', "/test-page-{$suffix}")->exists()) {
            Capsule::table('pages')->insert([
                'nama_page' => "Test Page {$suffix}",
                'route_path' => "/test-page-{$suffix}",
                'deskripsi' => 'Test page',
                'urutan_tampil' => 99,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->testPageId = (int) Capsule::table('pages')->where('route_path', "/test-page-{$suffix}")->value('id');

        if (!Capsule::table('users')->where('username', "testuser_{$suffix}")->exists()) {
            Capsule::table('users')->insert([
                'nama_lengkap' => "Test User {$suffix}",
                'username' => "testuser_{$suffix}",
                'email' => "testuser_{$suffix}@example.com",
                'password' => password_hash('password', PASSWORD_ARGON2ID),
                'level_id' => $this->testLevelId,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->testUserId = (int) Capsule::table('users')->where('username', "testuser_{$suffix}")->value('id');
    }

    public function test_assignLevelPermission_creates_entry(): void
    {
        $this->repo->assignLevelPermission($this->testLevelId, $this->testPageId);

        $exists = Capsule::table('level_permissions')
            ->where('level_id', $this->testLevelId)
            ->where('page_id', $this->testPageId)
            ->exists();

        $this->assertTrue($exists);
    }

    public function test_removeLevelPermission_deletes_entry(): void
    {
        Capsule::table('level_permissions')->insert([
            'level_id' => $this->testLevelId,
            'page_id' => $this->testPageId,
        ]);

        $result = $this->repo->removeLevelPermission($this->testLevelId, $this->testPageId);

        $this->assertTrue($result);
        $this->assertFalse(
            Capsule::table('level_permissions')
                ->where('level_id', $this->testLevelId)
                ->where('page_id', $this->testPageId)
                ->exists()
        );
    }

    public function test_removeLevelPermission_returns_false_when_not_found(): void
    {
        $result = $this->repo->removeLevelPermission(99999, 99999);

        $this->assertFalse($result);
    }

    public function test_assignUserPermission_creates_entry(): void
    {
        $this->repo->assignUserPermission($this->testUserId, $this->testPageId, true);

        $row = Capsule::table('user_permissions')
            ->where('user_id', $this->testUserId)
            ->where('page_id', $this->testPageId)
            ->first();

        $this->assertNotNull($row);
        $this->assertTrue((bool) $row->is_granted);
    }

    public function test_assignUserPermission_updates_existing_entry(): void
    {
        Capsule::table('user_permissions')->insert([
            'user_id' => $this->testUserId,
            'page_id' => $this->testPageId,
            'is_granted' => true,
        ]);

        $this->repo->assignUserPermission($this->testUserId, $this->testPageId, false);

        $row = Capsule::table('user_permissions')
            ->where('user_id', $this->testUserId)
            ->where('page_id', $this->testPageId)
            ->first();

        $this->assertFalse((bool) $row->is_granted);
    }

    public function test_removeUserPermission_deletes_entry(): void
    {
        Capsule::table('user_permissions')->insert([
            'user_id' => $this->testUserId,
            'page_id' => $this->testPageId,
            'is_granted' => true,
        ]);

        $result = $this->repo->removeUserPermission($this->testUserId, $this->testPageId);

        $this->assertTrue($result);
        $this->assertFalse(
            Capsule::table('user_permissions')
                ->where('user_id', $this->testUserId)
                ->where('page_id', $this->testPageId)
                ->exists()
        );
    }

    public function test_removeUserPermission_returns_false_when_not_found(): void
    {
        $result = $this->repo->removeUserPermission(99999, 99999);

        $this->assertFalse($result);
    }

    public function test_getLevelMatrix_returns_all_active_pages_with_access_flags(): void
    {
        Capsule::table('level_permissions')->insert([
            'level_id' => $this->testLevelId,
            'page_id' => $this->testPageId,
        ]);

        $matrix = $this->repo->getLevelMatrix($this->testLevelId);

        $this->assertIsArray($matrix);
        $this->assertNotEmpty($matrix);

        $testPage = null;
        foreach ($matrix as $page) {
            if ($page['id'] === $this->testPageId) {
                $testPage = $page;
                break;
            }
        }

        $this->assertNotNull($testPage);
        $this->assertTrue($testPage['has_access']);
        $this->assertArrayHasKey('name', $testPage);
        $this->assertArrayHasKey('route_path', $testPage);
    }

    public function test_getLevelMatrix_shows_no_access_for_unassigned_pages(): void
    {
        $matrix = $this->repo->getLevelMatrix($this->testLevelId);

        $testPage = null;
        foreach ($matrix as $page) {
            if ($page['id'] === $this->testPageId) {
                $testPage = $page;
                break;
            }
        }

        $this->assertNotNull($testPage);
        $this->assertFalse($testPage['has_access']);
    }

    public function test_getLevelMatrix_excludes_inactive_pages(): void
    {
        $suffix = substr(uniqid('', true), 0, 8);
        Capsule::table('pages')->insert([
            'nama_page' => "Inactive Page {$suffix}",
            'route_path' => "/inactive-{$suffix}",
            'deskripsi' => 'Inactive',
            'urutan_tampil' => 999,
            'is_active' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $matrix = $this->repo->getLevelMatrix($this->testLevelId);

        foreach ($matrix as $page) {
            $this->assertNotEquals("/inactive-{$suffix}", $page['route_path']);
        }
    }

    public function test_getUserMatrix_returns_pages_with_correct_access_resolution(): void
    {
        Capsule::table('level_permissions')->insert([
            'level_id' => $this->testLevelId,
            'page_id' => $this->testPageId,
        ]);

        $matrix = $this->repo->getUserMatrix($this->testUserId, $this->testLevelId);

        $testPage = null;
        foreach ($matrix as $page) {
            if ($page['id'] === $this->testPageId) {
                $testPage = $page;
                break;
            }
        }

        $this->assertNotNull($testPage);
        $this->assertTrue($testPage['has_access']);
    }

    public function test_getUserMatrix_user_deny_overrides_level_access(): void
    {
        Capsule::table('level_permissions')->insert([
            'level_id' => $this->testLevelId,
            'page_id' => $this->testPageId,
        ]);
        Capsule::table('user_permissions')->insert([
            'user_id' => $this->testUserId,
            'page_id' => $this->testPageId,
            'is_granted' => false,
        ]);

        $matrix = $this->repo->getUserMatrix($this->testUserId, $this->testLevelId);

        $testPage = null;
        foreach ($matrix as $page) {
            if ($page['id'] === $this->testPageId) {
                $testPage = $page;
                break;
            }
        }

        $this->assertNotNull($testPage);
        $this->assertFalse($testPage['has_access']);
    }

    public function test_getAllPages_returns_all_active_pages(): void
    {
        $pages = $this->repo->getAllPages();

        $this->assertIsArray($pages);
        $this->assertNotEmpty($pages);

        foreach ($pages as $page) {
            $this->assertArrayHasKey('id', $page);
            $this->assertArrayHasKey('name', $page);
            $this->assertArrayHasKey('route_path', $page);
            $this->assertIsInt($page['id']);
            $this->assertIsString($page['name']);
            $this->assertIsString($page['route_path']);
        }
    }

    public function test_getAllPages_excludes_inactive_pages(): void
    {
        $suffix = substr(uniqid('', true), 0, 8);
        $inactivePageId = Capsule::table('pages')->insertGetId([
            'nama_page' => "Inactive {$suffix}",
            'route_path' => "/inactive-all-{$suffix}",
            'deskripsi' => 'Inactive',
            'urutan_tampil' => 999,
            'is_active' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $pages = $this->repo->getAllPages();

        foreach ($pages as $page) {
            $this->assertNotEquals($inactivePageId, $page['id']);
        }
    }

    public function test_hasAccess_nonexistent_page_returns_false(): void
    {
        $result = $this->repo->hasAccess($this->testUserId, $this->testLevelId, '/nonexistent-page-xyz');

        $this->assertFalse($result);
    }
}
