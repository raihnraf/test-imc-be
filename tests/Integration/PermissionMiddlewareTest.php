<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Imc\Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();

        // Grant level 1 access to Dashboard and Level Management pages
        // so admin can call these endpoints (needed for tests)
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 1], $this->token);
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 3], $this->token);

        // Remove page 2 (User Management) access for level 1 and user 1
        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 2], $this->token);
        $this->handle('DELETE', '/api/users/1/permissions', ['page_id' => 2], $this->token);
    }

    protected function tearDown(): void
    {
        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 1], $this->token);
        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 2], $this->token);
        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 3], $this->token);
        $this->handle('DELETE', '/api/users/1/permissions', ['page_id' => 2], $this->token);
        parent::tearDown();
    }

    public function testMiddlewareReturns403WhenUserLacksPermission(): void
    {
        $response = $this->handle('GET', '/api/users', null, $this->token);

        $this->assertStatusCode(403, $response);

        $body = $this->getJsonBody($response);
        $this->assertEquals(403, $body['statusCode']);
        $this->assertEquals('FORBIDDEN', $body['error']['type']);
    }

    public function testMiddlewareAllowsAccessWhenUserHasLevelPermission(): void
    {
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 2], $this->token);

        $response = $this->handle('GET', '/api/users', null, $this->token);

        $this->assertStatusCode(200, $response);

        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 2], $this->token);
    }

    public function testMiddlewareAllowsAccessWhenUserHasOverrideGrant(): void
    {
        // First restore level permission so we can call /api/users/1/permissions
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 2], $this->token);

        // Now grant user override
        $this->handle('POST', '/api/users/1/permissions', [
            'page_id' => 2,
            'is_granted' => true,
        ], $this->token);

        // Remove level permission — user should still have access via override
        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 2], $this->token);

        $response = $this->handle('GET', '/api/users', null, $this->token);

        $this->assertStatusCode(200, $response);

        $this->handle('DELETE', '/api/users/1/permissions', ['page_id' => 2], $this->token);
    }

    public function testPermissionChangesTakeEffectImmediately(): void
    {
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 2], $this->token);

        // With level permission, access should work
        $response = $this->handle('GET', '/api/users', null, $this->token);
        $this->assertStatusCode(200, $response);

        $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 2], $this->token);

        // Immediately after removal, access should be denied
        $response = $this->handle('GET', '/api/users', null, $this->token);
        $this->assertStatusCode(403, $response);
    }
}
