<?php

namespace App\Domains\Auth\Contracts;

use App\Domains\Auth\DTOs\LoginAttemptData;

interface LoginAttemptRepositoryInterface
{
    public function recordAttempt(LoginAttemptData $data): void;

    public function countRecentFailuresByEmail(string $email, int $minutes): int;

    public function countRecentFailuresByIp(string $ip, int $minutes): int;

    public function countRecentFailuresByFingerprint(string $fingerprint, int $minutes): int;

    public function countRecentFailuresByEmailFromDifferentFingerprints(string $email, int $minutes): int;

    public function isDeviceBlocked(string $fingerprint): bool;

    public function isIpBlocked(string $ip): bool;

    public function blockDevice(string $fingerprint, int $durationMinutes, string $reason): void;

    public function blockIp(string $ip, int $durationMinutes, string $reason): void;

    public function getLastSuccessfulAttempt(string $email): ?LoginAttemptData;
}
