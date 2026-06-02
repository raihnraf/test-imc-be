<?php

declare(strict_types=1);

namespace Imc\Domain\RateLimit;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Carbon;

class RateLimitRepository implements RateLimitRepositoryInterface
{
    public function isRateLimited(string $ipAddress, int $maxAttempts = 5, int $windowSeconds = 60): bool
    {
        // Probability-based cleanup: only run ~1% of the time to avoid overhead
        if (random_int(1, 100) === 1) {
            $this->cleanupOldRecords();
        }

        $count = Capsule::table('login_attempts')
            ->where('ip_address', $ipAddress)
            ->where('attempted_at', '>', Carbon::now()->subSeconds($windowSeconds))
            ->count();

        return $count >= $maxAttempts;
    }

    public function recordAttempt(string $ipAddress): void
    {
        Capsule::table('login_attempts')->insert([
            'ip_address' => $ipAddress,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function cleanupOldRecords(int $cleanupSeconds = 300): void
    {
        Capsule::table('login_attempts')
            ->where('attempted_at', '<', Carbon::now()->subSeconds($cleanupSeconds))
            ->delete();
    }

    public function getSecondsUntilReset(string $ipAddress, int $windowSeconds = 60): int
    {
        $oldest = Capsule::table('login_attempts')
            ->where('ip_address', $ipAddress)
            ->where('attempted_at', '>', Carbon::now()->subSeconds($windowSeconds))
            ->orderBy('attempted_at', 'asc')
            ->value('attempted_at');

        if ($oldest === null) {
            return 0;
        }

        $resetAt = Carbon::parse($oldest)->addSeconds($windowSeconds);
        
        return max(0, $resetAt->getTimestamp() - time());
    }
}
