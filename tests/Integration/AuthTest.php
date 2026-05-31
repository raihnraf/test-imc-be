<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Illuminate\Database\Capsule\Manager as Capsule;
use Imc\Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure inactive test user exists
        if (Capsule::table('users')->where('username', 'inactive_user')->count() === 0) {
            Capsule::table('users')->insert([
                'nama_lengkap' => 'Inactive User',
                'username' => 'inactive_user',
                'email' => 'inactive@imc.local',
                'password' => password_hash('password123', PASSWORD_ARGON2ID),
                'level_id' => 4,
                'is_active' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function testLoginWithUsername(): void
    {
        $response = $this->handle('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertArrayHasKey('access_token', $body['data']);
        $this->assertArrayHasKey('refresh_token', $body['data']);
        $this->assertEquals('Bearer', $body['data']['token_type']);
        $this->assertArrayHasKey('user', $body['data']);
        $this->assertEquals('admin', $body['data']['user']['username']);
    }

    public function testLoginWithEmail(): void
    {
        $response = $this->handle('POST', '/auth/login', [
            'email' => 'admin@imc.local',
            'password' => 'admin123',
        ]);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(200, $response);
        $this->assertArrayHasKey('access_token', $body['data']);
    }

    public function testLoginInvalidPassword(): void
    {
        $response = $this->handle('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(401, $response);
        $this->assertEquals('INVALID_CREDENTIALS', $body['error']['type']);
    }

    public function testLoginUserNotFound(): void
    {
        $response = $this->handle('POST', '/auth/login', [
            'username' => 'nonexistent',
            'password' => 'password123',
        ]);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(401, $response);
        $this->assertEquals('INVALID_CREDENTIALS', $body['error']['type']);
    }

    public function testLoginInactiveUser(): void
    {
        $response = $this->handle('POST', '/auth/login', [
            'username' => 'inactive_user',
            'password' => 'password123',
        ]);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(401, $response);
        $this->assertEquals('ACCOUNT_INACTIVE', $body['error']['type']);
    }

    public function testLoginMissingIdentifier(): void
    {
        $response = $this->handle('POST', '/auth/login', [
            'password' => 'admin123',
        ]);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertArrayHasKey('username', $body['error']['errors']);
    }

    public function testLoginMissingPassword(): void
    {
        $response = $this->handle('POST', '/auth/login', [
            'username' => 'admin',
        ]);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertArrayHasKey('password', $body['error']['errors']);
    }

    public function testLoginEmptyBody(): void
    {
        $response = $this->handle('POST', '/auth/login', []);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
    }
}
