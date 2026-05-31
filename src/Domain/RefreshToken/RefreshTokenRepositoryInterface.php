<?php

declare(strict_types=1);

namespace Imc\Domain\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function store(int $userId, string $tokenHash, \DateTimeInterface $expiresAt): void;
    public function findByHash(string $tokenHash): ?object;
    public function revoke(int $tokenId): bool;
    public function rotateToken(int $oldTokenId, int $userId, string $newTokenHash, \DateTimeInterface $expiresAt): void;
    public function cleanupExpired(): void;
}
