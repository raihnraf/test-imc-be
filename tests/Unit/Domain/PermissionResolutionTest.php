<?php

declare(strict_types=1);

namespace Imc\Tests\Unit\Domain;

use Illuminate\Database\Capsule\Manager as Capsule;
use Imc\Domain\Permission\PermissionRepository;
use Imc\Tests\TestCase;

class PermissionResolutionTest extends TestCase
{
    private PermissionRepository $permissionRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionRepo = new PermissionRepository();

        Capsule::table('level_permissions')->truncate();
        Capsule::table('user_permissions')->truncate();

        Capsule::table('levels')->whereNotIn('id', [1, 2, 3, 4])->delete();
        Capsule::table('pages')->whereNotIn('id', [1, 2, 3])->delete();

        Capsule::table('users')->whereNotIn('id', [1])->delete();
    }

    public function testUserDenyOverridesLevelAccess(): void
    {
        Capsule::table('level_permissions')->insert([
            'level_id' => 1,
            'page_id' => 1,
        ]);
        Capsule::table('user_permissions')->insert([
            'user_id' => 1,
            'page_id' => 1,
            'is_granted' => false,
        ]);

        $result = $this->permissionRepo->hasAccess(1, 1, '/dashboard');

        $this->assertFalse($result);
    }

    public function testUserGrantOverridesLevelDeny(): void
    {
        Capsule::table('level_permissions')->where('level_id', 1)->where('page_id', 1)->delete();
        Capsule::table('user_permissions')->insert([
            'user_id' => 1,
            'page_id' => 1,
            'is_granted' => true,
        ]);

        $result = $this->permissionRepo->hasAccess(1, 1, '/dashboard');

        $this->assertTrue($result);
    }

    public function testLevelAccessWhenNoUserOverride(): void
    {
        Capsule::table('user_permissions')->where('user_id', 1)->where('page_id', 1)->delete();
        Capsule::table('level_permissions')->where('level_id', 1)->where('page_id', 1)->delete();

        Capsule::table('level_permissions')->insert([
            'level_id' => 1,
            'page_id' => 1,
        ]);

        $result = $this->permissionRepo->hasAccess(1, 1, '/dashboard');

        $this->assertTrue($result);
    }

    public function testDefaultDenyWhenNoLevelOrUserAccess(): void
    {
        Capsule::table('user_permissions')->where('user_id', 1)->where('page_id', 1)->delete();
        Capsule::table('level_permissions')->where('level_id', 1)->where('page_id', 1)->delete();

        $result = $this->permissionRepo->hasAccess(1, 1, '/dashboard');

        $this->assertFalse($result);
    }

    public function testInactivePageDenied(): void
    {
        Capsule::table('pages')->where('id', 1)->update(['is_active' => false]);

        Capsule::table('level_permissions')->insert([
            'level_id' => 1,
            'page_id' => 1,
        ]);

        $result = $this->permissionRepo->hasAccess(1, 1, '/dashboard');

        $this->assertFalse($result);

        Capsule::table('pages')->where('id', 1)->update(['is_active' => true]);
    }
}
