<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Imc\Tests\TestCase;

class UserPermissionTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();
    }

    public function testGrantUserPermissionBeyondLevel(): void
    {
        $matrixResponse = $this->handle('GET', '/api/levels/4/permissions', null, $this->token);
        $matrixBody = $this->getJsonBody($matrixResponse);
        $this->assertStatusCode(200, $matrixResponse);

        $page2 = null;
        foreach ($matrixBody['data'] as $page) {
            if ($page['id'] === 2) {
                $page2 = $page;
                break;
            }
        }
        $this->assertNotNull($page2);
        $this->assertFalse($page2['has_access']);

        $response = $this->handle('POST', '/api/users/1/permissions', [
            'page_id' => 2,
            'is_granted' => true,
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(201, $response);
        $this->assertEquals('Permission granted', $body['message']);

        $userMatrixResponse = $this->handle('GET', '/api/users/1/permissions', null, $this->token);
        $userMatrixBody = $this->getJsonBody($userMatrixResponse);

        $grantedPage = null;
        foreach ($userMatrixBody['data'] as $page) {
            if ($page['id'] === 2) {
                $grantedPage = $page;
                break;
            }
        }
        $this->assertNotNull($grantedPage);
        $this->assertTrue($grantedPage['has_access']);
    }

    public function testDenyUserPermissionThatLevelGrants(): void
    {
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 1], $this->token);

        $response = $this->handle('POST', '/api/users/1/permissions', [
            'page_id' => 1,
            'is_granted' => false,
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(201, $response);
        $this->assertEquals('Permission denied', $body['message']);

        $userMatrixResponse = $this->handle('GET', '/api/users/1/permissions', null, $this->token);
        $userMatrixBody = $this->getJsonBody($userMatrixResponse);

        $deniedPage = null;
        foreach ($userMatrixBody['data'] as $page) {
            if ($page['id'] === 1) {
                $deniedPage = $page;
                break;
            }
        }
        $this->assertNotNull($deniedPage);
        $this->assertFalse($deniedPage['has_access']);

        $this->handle('DELETE', '/api/users/1/permissions', ['page_id' => 1], $this->token);
        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 1], $this->token);
    }

    public function testRemoveUserPermissionOverride(): void
    {
        // Grant user override first (while level permission still exists)
        $this->handle('POST', '/api/users/1/permissions', [
            'page_id' => 2,
            'is_granted' => true,
        ], $this->token);

        // Remove level permission — user should still have access via override
        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 2], $this->token);

        // Verify access via middleware (not via matrix endpoint which needs /users permission)
        $response = $this->handle('GET', '/api/users', null, $this->token);
        $this->assertStatusCode(200, $response);

        // Restore level permission so we can access the matrix endpoint
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 2], $this->token);

        // Remove user override — should revert to level permission
        $deleteResponse = $this->handle('DELETE', '/api/users/1/permissions', ['page_id' => 2], $this->token);
        $body = $this->getJsonBody($deleteResponse);

        $this->assertStatusCode(200, $deleteResponse);
        $this->assertEquals('Permission override removed', $body['message']);

        // Now remove level permission again to verify user falls back to no access
        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 2], $this->token);

        // User should now be denied (no level perm, no user override)
        $response = $this->handle('GET', '/api/users', null, $this->token);
        $this->assertStatusCode(403, $response);

        // Final cleanup: restore level permission
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 2], $this->token);
    }

    public function testGrantPermissionToNonExistentUserReturns404(): void
    {
        $response = $this->handle('POST', '/api/users/9999/permissions', [
            'page_id' => 1,
            'is_granted' => true,
        ], $this->token);

        $this->assertStatusCode(404, $response);
    }
}
