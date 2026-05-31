<?php

declare(strict_types=1);

namespace Imc\Tests\Integration;

use Imc\Tests\TestCase;

class RateLimitTest extends TestCase
{
    private const TEST_IP = '192.168.1.100';
    private const OTHER_IP = '192.168.1.200';

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Database\Capsule\Manager::table('login_attempts')->truncate();
    }

    public function testRateLimitBlocksAfter5Attempts(): void
    {
        // Send 5 requests from same IP — all allowed (returns 401, not 429)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->handleWithIp('POST', '/auth/login', self::TEST_IP, [
                'username' => 'wrong',
                'password' => 'wrong',
            ]);

            $this->assertNotEquals(429, $response->getStatusCode(), "Attempt {$i} should not be rate limited");
        }

        // 6th request should be rate limited
        $response = $this->handleWithIp('POST', '/auth/login', self::TEST_IP, [
            'username' => 'wrong',
            'password' => 'wrong',
        ]);

        $this->assertStatusCode(429, $response);
        $body = $this->getJsonBody($response);
        $this->assertEquals('RATE_LIMITED', $body['error']['type']);
    }

    public function testSuccessfulLoginDoesNotCountTowardRateLimit(): void
    {
        // Successful login — should NOT count toward rate limit
        $response = $this->handleWithIp('POST', '/auth/login', self::TEST_IP, [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $this->assertStatusCode(200, $response);

        // 4 failed attempts (total failed: 4)
        for ($i = 0; $i < 4; $i++) {
            $response = $this->handleWithIp('POST', '/auth/login', self::TEST_IP, [
                'username' => 'wrong',
                'password' => 'wrong',
            ]);
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // Another successful login still works (successes don't count toward limit)
        $response = $this->handleWithIp('POST', '/auth/login', self::TEST_IP, [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $this->assertStatusCode(200, $response);

        // 5th failed attempt (total failed: 5) — still within limit
        $response = $this->handleWithIp('POST', '/auth/login', self::TEST_IP, [
            'username' => 'wrong',
            'password' => 'wrong',
        ]);
        $this->assertNotEquals(429, $response->getStatusCode());

        // 6th attempt from same IP — should be rate limited
        $response = $this->handleWithIp('POST', '/auth/login', self::TEST_IP, [
            'username' => 'wrong',
            'password' => 'wrong',
        ]);
        $this->assertStatusCode(429, $response);
    }

    public function testRateLimitIsPerIP(): void
    {
        // Exhaust first IP
        for ($i = 0; $i < 5; $i++) {
            $this->handleWithIp('POST', '/auth/login', self::TEST_IP, [
                'username' => 'wrong',
                'password' => 'wrong',
            ]);
        }

        // First IP is rate limited
        $response = $this->handleWithIp('POST', '/auth/login', self::TEST_IP, [
            'username' => 'wrong',
            'password' => 'wrong',
        ]);
        $this->assertStatusCode(429, $response);

        // Different IP should NOT be rate limited
        $response = $this->handleWithIp('POST', '/auth/login', self::OTHER_IP, [
            'username' => 'wrong',
            'password' => 'wrong',
        ]);

        $this->assertNotEquals(429, $response->getStatusCode());
    }
}
