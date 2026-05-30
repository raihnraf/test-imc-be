<?php

declare(strict_types=1);

namespace Imc\Domain\Token;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
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

    public function generateToken(array $userData): string
    {
        $now = time();

        $payload = [
            'iss' => 'imc-be',
            'sub' => $userData['user_id'],
            'iat' => $now,
            'exp' => $now + $this->expiry,
            'data' => [
                'user_id' => $userData['user_id'],
                'level_id' => $userData['level_id'],
                'username' => $userData['username'],
            ],
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function validateToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return (array) $decoded->data;
        } catch (ExpiredException) {
            throw new AuthenticationException('Token has expired', 401);
        } catch (SignatureInvalidException) {
            throw new AuthenticationException('Invalid token signature', 401);
        } catch (\Exception) {
            throw new AuthenticationException('Invalid token', 401);
        }
    }
}
