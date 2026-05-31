<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Illuminate\Database\Capsule\Manager as Capsule;
use Imc\Tests\TestCase;

class TokenRefreshTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Capsule::table('refresh_tokens')->truncate();
        Capsule::table('login_attempts')->truncate();
    }

    public function testLoginReturnsAccessTokenAndRefreshToken(): void
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
        $this->assertEquals(900, $body['data']['expires_in']);
    }

    public function testRefreshTokenReturnsNewTokenPair(): void
    {
        // Login to get tokens
        $loginResp = $this->handle('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $loginBody = $this->getJsonBody($loginResp);
        $oldAccessToken = $loginBody['data']['access_token'];
        $oldRefreshToken = $loginBody['data']['refresh_token'];

        // Refresh
        $refreshResp = $this->handle('POST', '/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);
        $refreshBody = $this->getJsonBody($refreshResp);

        $this->assertStatusCode(200, $refreshResp);
        $this->assertNotEquals($oldAccessToken, $refreshBody['data']['access_token']);
        $this->assertNotEquals($oldRefreshToken, $refreshBody['data']['refresh_token']);
        $this->assertEquals('Bearer', $refreshBody['data']['token_type']);
        $this->assertEquals(900, $refreshBody['data']['expires_in']);
    }

    public function testRefreshTokenRotationRevokesOldToken(): void
    {
        // Login
        $loginResp = $this->handle('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $loginBody = $this->getJsonBody($loginResp);
        $oldRefreshToken = $loginBody['data']['refresh_token'];

        // First refresh succeeds
        $firstResp = $this->handle('POST', '/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);
        $this->assertStatusCode(200, $firstResp);

        // Second refresh with same old token should fail
        $secondResp = $this->handle('POST', '/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);
        $secondBody = $this->getJsonBody($secondResp);

        $this->assertStatusCode(401, $secondResp);
        $this->assertEquals('TOKEN_REVOKED', $secondBody['error']['type']);
    }

    public function testExpiredRefreshTokenReturns401(): void
    {
        // Login
        $loginResp = $this->handle('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $loginBody = $this->getJsonBody($loginResp);
        $refreshToken = $loginBody['data']['refresh_token'];

        // Manually expire the refresh token in DB
        Capsule::table('refresh_tokens')->update([
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);

        $response = $this->handle('POST', '/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(401, $response);
        $this->assertEquals('TOKEN_EXPIRED', $body['error']['type']);
    }

    public function testInvalidRefreshTokenReturns401(): void
    {
        $response = $this->handle('POST', '/auth/refresh', [
            'refresh_token' => 'invalid-refresh-token-value',
        ]);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(401, $response);
        $this->assertEquals('INVALID_TOKEN', $body['error']['type']);
    }

    public function testMissingRefreshTokenReturns422(): void
    {
        $response = $this->handle('POST', '/auth/refresh', []);
        $body = $this->getJsonBody($response);

        $this->assertStatusCode(422, $response);
        $this->assertEquals('VALIDATION_ERROR', $body['error']['type']);
    }
}
