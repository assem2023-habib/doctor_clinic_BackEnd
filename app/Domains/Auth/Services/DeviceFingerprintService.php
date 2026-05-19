<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\DeviceFingerprintServiceInterface;
use App\Domains\Auth\Models\DeviceFingerprint;
use App\Domains\Auth\Models\KnownUserDevice;

class DeviceFingerprintService implements DeviceFingerprintServiceInterface
{
    public function isKnownDevice(string $userId, string $fingerprint): bool
    {
        return KnownUserDevice::where('user_id', $userId)
            ->where('device_fingerprint', $fingerprint)
            ->exists();
    }

    public function registerDevice(string $userId, string $fingerprint, string $ip, ?string $userAgent, ?string $deviceName): void
    {
        $device = KnownUserDevice::firstOrNew([
            'user_id' => $userId,
            'device_fingerprint' => $fingerprint,
        ]);

        if (!$device->exists) {
            $device->fill([
                'device_name' => $deviceName ?? $userAgent,
                'ip_first_seen' => $ip,
                'first_seen_at' => now(),
            ]);
        }

        $device->fill([
            'ip_last_seen' => $ip,
            'last_seen_at' => now(),
        ]);

        $device->save();

        DeviceFingerprint::updateOrCreate(
            ['fingerprint_hash' => $fingerprint],
            [
                'user_agent' => $userAgent,
                'ip_first_seen' => $ip,
                'ip_last_seen' => $ip,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]
        );
    }

    public function isBlocked(string $fingerprint): bool
    {
        return DeviceFingerprint::where('fingerprint_hash', $fingerprint)
            ->where('blocked_until', '>', now())
            ->exists();
    }

    public function block(string $fingerprint, int $durationMinutes, string $reason): void
    {
        DeviceFingerprint::updateOrCreate(
            ['fingerprint_hash' => $fingerprint],
            [
                'ip_first_seen' => request()->ip(),
                'first_seen_at' => now(),
                'blocked_until' => now()->addMinutes($durationMinutes),
                'block_reason' => $reason,
                'last_seen_at' => now(),
            ]
        );
    }

    public function getTrustedDevices(string $userId): array
    {
        return KnownUserDevice::where('user_id', $userId)
            ->trusted()
            ->get()
            ->toArray();
    }
}
