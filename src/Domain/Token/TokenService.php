<?php

declare(strict_types=1);

namespace Imc\Domain\Token;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Imc\Domain\Exceptions\AuthenticationException;

class TokenService
{
    private string $secret;
    private string $algorithm;
    private int $expiry;

    public function __construct(array $settings)
    {
        $this->secret = $settings['secret'] ?? '';
        $this->algorithm = $settings['algorithm'] ?? 'HS256';
        $this->expiry = (int) ($settings['expiry'] ?? 3600);
    }

    public function generateToken(array $userData, ?int $expiry = null): string
    {
        $now = time();
        $effectiveExpiry = $expiry ?? $this->expiry;

        $payload = [
            'iss' => 'imc-be',
            'sub' => $userData['user_id'],
            'iat' => $now,
            'exp' => $now + $effectiveExpiry,
            'jti' => bin2hex(random_bytes(16)),
            'data' => [
                'user_id' => $userData['user_id'],
                'level_id' => $userData['level_id'],
                'username' => $userData['username'],
            ],
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function generateRefreshToken(): array
    {
        $rawToken = bin2hex(random_bytes(32));
        $hash = hash('sha256', $rawToken);
        return ['raw_token' => $rawToken, 'hash' => $hash];
    }

    public function validateToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return (array) $decoded->data;
        } catch (ExpiredException) {
            throw new AuthenticationException('Token has expired', 'TOKEN_EXPIRED');
        } catch (SignatureInvalidException) {
            throw new AuthenticationException('Invalid token signature', 'INVALID_TOKEN');
        } catch (\Exception) {
            throw new AuthenticationException('Invalid token', 'INVALID_TOKEN');
        }
    }
}
