<?php

namespace App\Domains\Auth\Contracts;

interface DeviceFingerprintServiceInterface
{
    public function isKnownDevice(string $userId, string $fingerprint): bool;

    public function registerDevice(string $userId, string $fingerprint, string $ip, ?string $userAgent, ?string $deviceName): void;

    public function isBlocked(string $fingerprint): bool;

    public function block(string $fingerprint, int $durationMinutes, string $reason): void;

    public function getTrustedDevices(string $userId): array;
}
