<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Imc\Tests\TestCase;

class LevelPermissionTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();
    }

    public function testAssignPermissionToLevel(): void
    {
        // Use level 4 (Viewer) which has no baseline permissions
        $response = $this->handle('POST', '/api/levels/4/permissions', ['page_id' => 1], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(201, $response);
        $this->assertEquals('Permission assigned', $body['message']);

        $matrixResponse = $this->handle('GET', '/api/levels/4/permissions', null, $this->token);
        $matrixBody = $this->getJsonBody($matrixResponse);

        $this->assertStatusCode(200, $matrixResponse);
        $assignedPage = null;
        foreach ($matrixBody['data'] as $page) {
            if ($page['id'] === 1) {
                $assignedPage = $page;
                break;
            }
        }
        $this->assertNotNull($assignedPage);
        $this->assertTrue($assignedPage['has_access']);

        // Cleanup
        $this->handle('DELETE', '/api/levels/4/permissions', ['page_id' => 1], $this->token);
    }

    public function testRemovePermissionFromLevel(): void
    {
        $this->handle('POST', '/api/levels/1/permissions', ['page_id' => 1], $this->token);

        $response = $this->handle('DELETE', '/api/levels/1/permissions', ['page_id' => 1], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertEquals('Permission removed', $body['message']);

        $matrixResponse = $this->handle('GET', '/api/levels/1/permissions', null, $this->token);
        $matrixBody = $this->getJsonBody($matrixResponse);

        $removedPage = null;
        foreach ($matrixBody['data'] as $page) {
            if ($page['id'] === 1) {
                $removedPage = $page;
                break;
            }
        }
        $this->assertNotNull($removedPage);
        $this->assertFalse($removedPage['has_access']);
    }

    public function testAssignPermissionToNonExistentLevelReturns404(): void
    {
        $response = $this->handle('POST', '/api/levels/9999/permissions', ['page_id' => 1], $this->token);
        $this->assertStatusCode(404, $response);
    }
}
