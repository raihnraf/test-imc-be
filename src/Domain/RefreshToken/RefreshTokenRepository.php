<?php

declare(strict_types=1);

namespace Imc\Domain\RefreshToken;

use Illuminate\Database\Capsule\Manager as Capsule;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function store(int $userId, string $tokenHash, \DateTimeInterface $expiresAt): void
    {
        Capsule::table('refresh_tokens')->insert([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByHash(string $tokenHash): ?object
    {
        return Capsule::table('refresh_tokens')->where('token_hash', $tokenHash)->first();
    }

    public function revoke(int $tokenId): bool
    {
        $affected = Capsule::table('refresh_tokens')
            ->where('id', $tokenId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);

        return $affected > 0;
    }

    public function cleanupExpired(): void
    {
        Capsule::table('refresh_tokens')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->orWhere(function ($q) {
                $q->whereNotNull('revoked_at')
                  ->where('revoked_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')));
            })
            ->delete();
    }
}
