<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Imc\Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();
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
}
