<?php

declare(strict_types=1);

namespace Imc\Domain\RateLimit;

interface RateLimitRepositoryInterface
{
    public function isRateLimited(string $ipAddress, int $maxAttempts = 5, int $windowSeconds = 60): bool;
    public function recordAttempt(string $ipAddress): void;
    public function cleanupOldRecords(int $cleanupSeconds = 300): void;
}
