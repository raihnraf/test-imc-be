<?php

declare(strict_types=1);

namespace Imc\Tests\Unit\Domain;

use Imc\Domain\Exceptions\AuthenticationException;
use Imc\Domain\Token\TokenService;
use PHPUnit\Framework\TestCase;

class TokenServiceTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new TokenService([
            'secret' => 'test-unit-secret-key',
            'algorithm' => 'HS256',
            'expiry' => 3600,
        ]);
    }

    public function testGenerateTokenReturnsString(): void
    {
        $token = $this->tokenService->generateToken([
            'user_id' => 1,
            'level_id' => 1,
            'username' => 'admin',
        ]);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testValidateTokenReturnsUserData(): void
    {
        $userData = [
            'user_id' => 42,
            'level_id' => 2,
            'username' => 'staff_user',
        ];

        $token = $this->tokenService->generateToken($userData);
        $decoded = $this->tokenService->validateToken($token);

        $this->assertEquals($userData['user_id'], $decoded['user_id']);
        $this->assertEquals($userData['level_id'], $decoded['level_id']);
        $this->assertEquals($userData['username'], $decoded['username']);
    }

    public function testValidateTokenRejectsInvalidToken(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->tokenService->validateToken('not.a.valid.jwt');
    }

    public function testValidateTokenRejectsEmptyString(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->tokenService->validateToken('');
    }

    public function testValidateTokenRejectsWrongSecret(): void
    {
        $token = $this->tokenService->generateToken([
            'user_id' => 1,
            'level_id' => 1,
            'username' => 'admin',
        ]);

        $otherService = new TokenService([
            'secret' => 'different-secret-key',
            'algorithm' => 'HS256',
            'expiry' => 3600,
        ]);

        $this->expectException(AuthenticationException::class);

        $otherService->validateToken($token);
    }

    public function testValidateTokenRejectsExpiredToken(): void
    {
        $token = $this->tokenService->generateToken(
            ['user_id' => 1, 'level_id' => 1, 'username' => 'admin'],
            expiry: 0
        );

        // Sleep 1 second to ensure expiration
        sleep(1);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token has expired');

        $this->tokenService->validateToken($token);
    }

    public function testGenerateRefreshTokenReturnsBothTokens(): void
    {
        $result = $this->tokenService->generateRefreshToken();

        $this->assertArrayHasKey('raw_token', $result);
        $this->assertArrayHasKey('hash', $result);

        $this->assertIsString($result['raw_token']);
        $this->assertIsString($result['hash']);

        // raw_token should be 64 hex chars (32 bytes)
        $this->assertEquals(64, strlen($result['raw_token']));
        // hash should be 64 hex chars (sha256)
        $this->assertEquals(64, strlen($result['hash']));
    }

    public function testGenerateRefreshTokenProducesUniqueTokens(): void
    {
        $result1 = $this->tokenService->generateRefreshToken();
        $result2 = $this->tokenService->generateRefreshToken();

        $this->assertNotEquals($result1['raw_token'], $result2['raw_token']);
        $this->assertNotEquals($result1['hash'], $result2['hash']);
    }

    public function testGenerateRefreshTokenHashMatchesRawToken(): void
    {
        $result = $this->tokenService->generateRefreshToken();

        $expectedHash = hash('sha256', $result['raw_token']);
        $this->assertEquals($expectedHash, $result['hash']);
    }
}
