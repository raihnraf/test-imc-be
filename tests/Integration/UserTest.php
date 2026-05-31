<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Imc\Tests\TestCase;

class UserTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();
    }

    public function testListUsers(): void
    {
        $response = $this->handle('GET', '/api/users', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('meta', $body);
    }

    public function testListUsersWithFilters(): void
    {
        $response = $this->handle('GET', '/api/users?search=admin&is_active=1', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertGreaterThan(0, count($body['data']));
    }

    public function testGetUser(): void
    {
        $response = $this->handle('GET', '/api/users/1', null, $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertArrayNotHasKey('password', $body['data']);
        $this->assertEquals('admin', $body['data']['username']);
    }

    public function testGetUserNotFound(): void
    {
        $response = $this->handle('GET', '/api/users/99999', null, $this->token);
        $this->assertStatusCode(404, $response);
    }

    public function testCreateUser(): void
    {
        $response = $this->handle('POST', '/api/users', [
            'nama_lengkap' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'level_id' => 2,
            'is_active' => true,
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(201, $response);
        $this->assertEquals('testuser', $body['data']['username']);
        $this->assertArrayNotHasKey('password', $body['data']);
    }

    public function testCreateUserDuplicateUsername(): void
    {
        $this->handle('POST', '/api/users', [
            'nama_lengkap' => 'First',
            'username' => 'duplicateme',
            'email' => 'first@example.com',
            'password' => 'password123',
        ], $this->token);

        $response = $this->handle('POST', '/api/users', [
            'nama_lengkap' => 'Second',
            'username' => 'duplicateme',
            'email' => 'second@example.com',
            'password' => 'password123',
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(409, $response);
        $this->assertEquals('DUPLICATE_ENTRY', $body['error']['type']);
        $this->assertEquals('username', $body['error']['field']);
    }

    public function testCreateUserDuplicateEmail(): void
    {
        $this->handle('POST', '/api/users', [
            'nama_lengkap' => 'First',
            'username' => 'firstone',
            'email' => 'dupemail@example.com',
            'password' => 'password123',
        ], $this->token);

        $response = $this->handle('POST', '/api/users', [
            'nama_lengkap' => 'Second',
            'username' => 'secondone',
            'email' => 'dupemail@example.com',
            'password' => 'password123',
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(409, $response);
        $this->assertEquals('DUPLICATE_ENTRY', $body['error']['type']);
        $this->assertEquals('email', $body['error']['field']);
    }

    public function testCreateUserValidationErrors(): void
    {
        $response = $this->handle('POST', '/api/users', [], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertArrayHasKey('errors', $body['error']);
    }

    public function testCreateUserInvalidEmail(): void
    {
        $response = $this->handle('POST', '/api/users', [
            'nama_lengkap' => 'Bad Email',
            'username' => 'bademail',
            'email' => 'not-an-email',
            'password' => 'password123',
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertArrayHasKey('email', $body['error']['errors']);
    }

    public function testCreateUserShortPassword(): void
    {
        $response = $this->handle('POST', '/api/users', [
            'nama_lengkap' => 'Short',
            'username' => 'shortpw',
            'email' => 'short@example.com',
            'password' => 'ab',
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertArrayHasKey('password', $body['error']['errors']);
    }

    public function testUpdateUser(): void
    {
        $response = $this->handle('PUT', '/api/users/1', [
            'nama_lengkap' => 'Admin Updated',
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertEquals('Admin Updated', $body['data']['nama_lengkap']);
        $this->assertArrayNotHasKey('password', $body['data']);
    }

    public function testUpdateUserPassword(): void
    {
        $response = $this->handle('PUT', '/api/users/1', [
            'password' => 'newpass123',
        ], $this->token);

        $this->assertStatusCode(200, $response);

        // Verify new password works for login
        $loginResp = $this->handle('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'newpass123',
        ]);
        $this->assertStatusCode(200, $loginResp);

        // Reset password back
        $this->handle('PUT', '/api/users/1', ['password' => 'admin123'], $this->token);
    }

    public function testDeleteUser(): void
    {
        $createResp = $this->handle('POST', '/api/users', [
            'nama_lengkap' => 'Delete Me',
            'username' => 'deleteme',
            'email' => 'delete@example.com',
            'password' => 'password123',
        ], $this->token);
        $createBody = $this->getJsonBody($createResp);
        $userId = $createBody['data']['id'];

        $response = $this->handle('DELETE', "/api/users/{$userId}", null, $this->token);
        $this->assertStatusCode(200, $response);

        $getResp = $this->handle('GET', "/api/users/{$userId}", null, $this->token);
        $this->assertStatusCode(404, $getResp);
    }

    public function testUnauthenticatedAccess(): void
    {
        $response = $this->handle('GET', '/api/users');
        $this->assertStatusCode(401, $response);
    }

    public function testCreateUserInvalidLevelId(): void
    {
        $response = $this->handle('POST', '/api/users', [
            'nama_lengkap' => 'Bad Level',
            'username' => 'badlevel',
            'email' => 'badlevel@example.com',
            'password' => 'password123',
            'level_id' => 99999,
        ], $this->token);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertArrayHasKey('level_id', $body['error']['errors']);
    }
}
