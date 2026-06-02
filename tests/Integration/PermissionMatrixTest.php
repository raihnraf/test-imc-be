<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Illuminate\Database\Capsule\Manager as Capsule;
use Imc\Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();

        // Grant level 2 access to /permissions page so non-admin can reach the action
        // (action's own authorization logic handles self-check)
        $permissionsPage = Capsule::table('pages')
            ->where('route_path', '/permissions')
            ->first();
        if ($permissionsPage) {
            $exists = Capsule::table('level_permissions')
                ->where('level_id', 2)
                ->where('page_id', $permissionsPage->id)
                ->exists();
            if (!$exists) {
                Capsule::table('level_permissions')->insert([
                    'level_id' => 2,
                    'page_id' => $permissionsPage->id,
                ]);
            }
        }
    }

    public function testGetLevelMatrixViaQueryParam(): void
    {
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 1], $this->token);

        $response = $this->handle('GET', '/api/permissions/matrix?level_id=1', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertEquals('level', $body['type']);
        $this->assertEquals(1, $body['level_id']);
        $this->assertIsArray($body['data']);
        $this->assertArrayHasKey('has_access', $body['data'][0]);
        $this->assertIsBool($body['data'][0]['has_access']);

        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 1], $this->token);
    }

    public function testGetUserMatrixViaQueryParam(): void
    {
        $response = $this->handle('GET', '/api/permissions/matrix?user_id=1', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertEquals('user', $body['type']);
        $this->assertEquals(1, $body['user_id']);
        $this->assertIsArray($body['data']);
        $this->assertArrayHasKey('has_access', $body['data'][0]);
    }

    public function testGetAllPagesWithoutParams(): void
    {
        $response = $this->handle('GET', '/api/permissions/matrix', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertEquals('all', $body['type']);
        $this->assertIsArray($body['data']);
        $this->assertNull($body['data'][0]['has_access']);
    }

    public function testBothParamsReturns422(): void
    {
        $response = $this->handle('GET', '/api/permissions/matrix?level_id=1&user_id=1', null, $this->token);
        $this->assertStatusCode(422, $response);
    }

    public function testNonExistentLevelReturns404(): void
    {
        $response = $this->handle('GET', '/api/permissions/matrix?level_id=9999', null, $this->token);
        $this->assertStatusCode(404, $response);
    }

    public function testNonAdminCannotViewLevelMatrix(): void
    {
        $nonAdminToken = $this->createNonAdminUserAndGetToken();

        $response = $this->handle('GET', '/api/permissions/matrix?level_id=1', null, $nonAdminToken);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(403, $response);
        $this->assertEquals('FORBIDDEN', $body['error']['type']);
    }

    public function testNonAdminCannotViewOtherUserMatrix(): void
    {
        $nonAdminToken = $this->createNonAdminUserAndGetToken();

        $response = $this->handle('GET', '/api/permissions/matrix?user_id=1', null, $nonAdminToken);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(403, $response);
        $this->assertEquals('FORBIDDEN', $body['error']['type']);
        $this->assertStringContainsString('own permissions', $body['error']['description']);
    }

    public function testNonAdminCanViewOwnMatrix(): void
    {
        $nonAdminUserId = $this->createNonAdminUserAndGetId();
        $nonAdminToken = $this->getAuthTokenForUser($nonAdminUserId);

        $response = $this->handle('GET', '/api/permissions/matrix?user_id=' . $nonAdminUserId, null, $nonAdminToken);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertEquals('user', $body['type']);
        $this->assertEquals($nonAdminUserId, $body['user_id']);
    }

    public function testNonAdminCannotViewAllPages(): void
    {
        $nonAdminToken = $this->createNonAdminUserAndGetToken();

        $response = $this->handle('GET', '/api/permissions/matrix', null, $nonAdminToken);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(403, $response);
        $this->assertEquals('FORBIDDEN', $body['error']['type']);
    }

    private function createNonAdminUserAndGetToken(): string
    {
        $userId = $this->createNonAdminUserAndGetId();
        return $this->getAuthTokenForUser($userId);
    }

    private function createNonAdminUserAndGetId(): int
    {
        $response = $this->handle('POST', '/api/users', [
            'full_name' => 'Non Admin User',
            'username' => 'nonadmin' . uniqid(),
            'email' => 'nonadmin' . uniqid() . '@example.com',
            'password' => 'password123',
            'level_id' => 2,
            'is_active' => true,
        ], $this->token);

        $body = $this->getJsonBody($response);
        return $body['data']['id'];
    }

    private function getAuthTokenForUser(int $userId): string
    {
        $user = Capsule::table('users')->where('id', $userId)->first();

        $response = $this->handle('POST', '/auth/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        $body = $this->getJsonBody($response);
        return $body['data']['access_token'];
    }
}
