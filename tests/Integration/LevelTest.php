<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Imc\Tests\TestCase;

class LevelTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();
    }

    public function testListLevels(): void
    {
        $response = $this->handle('GET', '/api/levels', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('meta', $body);
    }

    public function testListLevelsWithSearch(): void
    {
        $response = $this->handle('GET', '/api/levels?search=Super', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertGreaterThan(0, count($body['data']));
    }

    public function testGetLevel(): void
    {
        $response = $this->handle('GET', '/api/levels/1', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertNotNull($body['data']['name']);
        $this->assertArrayHasKey('id', $body['data']);
    }

    public function testGetLevelNotFound(): void
    {
        $response = $this->handle('GET', '/api/levels/99999', null, $this->token);
        $this->assertStatusCode(404, $response);
    }

    public function testCreateLevel(): void
    {
        $response = $this->handle('POST', '/api/levels', [
            'name' => 'Test Level',
            'description' => 'A test level',
            'is_active' => true,
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(201, $response);
        $this->assertEquals('Test Level', $body['data']['name']);
        $this->assertArrayHasKey('id', $body['data']);
    }

    public function testCreateLevelValidationError(): void
    {
        $response = $this->handle('POST', '/api/levels', [], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertEquals('VALIDATION_ERROR', $body['error']['type']);
    }

    public function testUpdateLevel(): void
    {
        $response = $this->handle('PUT', '/api/levels/1', [
            'name' => 'Super Admin Updated',
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertEquals('Super Admin Updated', $body['data']['name']);
    }

    public function testUpdateLevelNotFound(): void
    {
        $response = $this->handle('PUT', '/api/levels/99999', [
            'name' => 'Should fail',
        ], $this->token);

        $this->assertStatusCode(404, $response);
    }

    public function testDeleteLevel(): void
    {
        // Create a temp level to delete
        $createResp = $this->handle('POST', '/api/levels', ['name' => 'To Delete'], $this->token);
        $createBody = $this->getJsonBody($createResp);
        $levelId = $createBody['data']['id'];

        $response = $this->handle('DELETE', "/api/levels/{$levelId}", null, $this->token);
        $this->assertStatusCode(200, $response);

        // Verify it's no longer accessible
        $getResp = $this->handle('GET', "/api/levels/{$levelId}", null, $this->token);
        $this->assertStatusCode(404, $getResp);
    }

    public function testDeleteLevelAlreadyDeleted(): void
    {
        // Create and delete a level
        $createResp = $this->handle('POST', '/api/levels', ['name' => 'Double Delete'], $this->token);
        $createBody = $this->getJsonBody($createResp);
        $levelId = $createBody['data']['id'];

        $this->handle('DELETE', "/api/levels/{$levelId}", null, $this->token);

        // Second delete should return 404
        $response = $this->handle('DELETE', "/api/levels/{$levelId}", null, $this->token);
        $this->assertStatusCode(404, $response);
    }

    public function testUnauthenticatedAccess(): void
    {
        $response = $this->handle('GET', '/api/levels');
        $this->assertStatusCode(401, $response);
    }

    public function testUpdateLevelEmptyBody(): void
    {
        $response = $this->handle('PUT', '/api/levels/1', [], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertEquals('VALIDATION_ERROR', $body['error']['type']);
    }

    public function testCreateLevelNameTooLong(): void
    {
        $response = $this->handle('POST', '/api/levels', [
            'name' => str_repeat('a', 101),
        ], $this->token);

        $this->assertStatusCode(422, $response);
    }

    public function testDeleteLevelWithActiveUsersReturns409(): void
    {
        // Super Admin (level 1) has the admin user assigned and active
        $response = $this->handle('DELETE', '/api/levels/1', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(409, $response);
        $this->assertEquals('RESOURCE_IN_USE', $body['error']['type']);
        $this->assertStringContainsString('active user', $body['error']['description']);
        $this->assertEquals('level_id', $body['error']['field']);
    }
}
