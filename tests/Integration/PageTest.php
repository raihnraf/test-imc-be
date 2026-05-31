<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Imc\Tests\TestCase;

class PageTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();
    }

    public function testListPages(): void
    {
        $response = $this->handle('GET', '/api/pages', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('meta', $body);
    }

    public function testListPagesWithSearch(): void
    {
        $response = $this->handle('GET', '/api/pages?search=User', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertGreaterThan(0, count($body['data']));
    }

    public function testGetPage(): void
    {
        $response = $this->handle('GET', '/api/pages/1', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertEquals('Dashboard', $body['data']['nama_page']);
    }

    public function testGetPageNotFound(): void
    {
        $response = $this->handle('GET', '/api/pages/99999', null, $this->token);
        $this->assertStatusCode(404, $response);
    }

    public function testCreatePage(): void
    {
        $response = $this->handle('POST', '/api/pages', [
            'nama_page' => 'Test Page',
            'route_path' => '/test-page',
            'deskripsi' => 'A test page',
            'urutan_tampil' => 10,
            'is_active' => true,
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(201, $response);
        $this->assertEquals('Test Page', $body['data']['nama_page']);
        $this->assertEquals('/test-page', $body['data']['route_path']);
    }

    public function testCreatePageDuplicateRoute(): void
    {
        $this->handle('POST', '/api/pages', [
            'nama_page' => 'First',
            'route_path' => '/dup-route',
        ], $this->token);

        $response = $this->handle('POST', '/api/pages', [
            'nama_page' => 'Second',
            'route_path' => '/dup-route',
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(409, $response);
        $this->assertEquals('DUPLICATE_ENTRY', $body['error']['type']);
        $this->assertEquals('route_path', $body['error']['field']);
    }

    public function testCreatePageValidationErrors(): void
    {
        $response = $this->handle('POST', '/api/pages', [], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertArrayHasKey('errors', $body['error']);
    }

    public function testCreatePageRoutePathNoLeadingSlash(): void
    {
        $response = $this->handle('POST', '/api/pages', [
            'nama_page' => 'Bad Route',
            'route_path' => 'no-slash',
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertArrayHasKey('route_path', $body['error']['errors']);
    }

    public function testUpdatePage(): void
    {
        $createResp = $this->handle('POST', '/api/pages', [
            'nama_page' => 'To Update',
            'route_path' => '/to-update',
        ], $this->token);
        $createBody = $this->getJsonBody($createResp);
        $pageId = $createBody['data']['id'];

        $response = $this->handle('PUT', "/api/pages/{$pageId}", [
            'nama_page' => 'Updated Name',
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertEquals('Updated Name', $body['data']['nama_page']);
    }

    public function testDeletePage(): void
    {
        $createResp = $this->handle('POST', '/api/pages', [
            'nama_page' => 'Delete Me',
            'route_path' => '/delete-me',
        ], $this->token);
        $createBody = $this->getJsonBody($createResp);
        $pageId = $createBody['data']['id'];

        $response = $this->handle('DELETE', "/api/pages/{$pageId}", null, $this->token);
        $this->assertStatusCode(200, $response);

        $getResp = $this->handle('GET', "/api/pages/{$pageId}", null, $this->token);
        $this->assertStatusCode(404, $getResp);
    }

    public function testUnauthenticatedAccess(): void
    {
        $response = $this->handle('GET', '/api/pages');
        $this->assertStatusCode(401, $response);
    }

    public function testUpdatePageEmptyBody(): void
    {
        $createResp = $this->handle('POST', '/api/pages', [
            'nama_page' => 'Empty Body Test',
            'route_path' => '/empty-body',
        ], $this->token);
        $createBody = $this->getJsonBody($createResp);
        $pageId = $createBody['data']['id'];

        $response = $this->handle('PUT', "/api/pages/{$pageId}", [], $this->token);
        $this->assertStatusCode(422, $response);
    }
}
