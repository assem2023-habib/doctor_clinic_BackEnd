<?php

namespace App\Domains\Auth\Repositories;

use App\Domains\Auth\Contracts\LoginAttemptRepositoryInterface;
use App\Domains\Auth\DTOs\LoginAttemptData;
use App\Domains\Auth\Models\DeviceFingerprint;
use App\Domains\Auth\Models\LoginAttempt;
use Illuminate\Support\Facades\DB;

class EloquentLoginAttemptRepository implements LoginAttemptRepositoryInterface
{
    public function recordAttempt(LoginAttemptData $data): void
    {
        LoginAttempt::create([
            'email' => $data->email,
            'ip' => $data->ip,
            'device_fingerprint' => $data->deviceFingerprint,
            'user_agent' => $data->userAgent,
            'success' => $data->success,
            'failure_reason' => $data->failureReason,
            'attempted_at' => now(),
        ]);
    }

    public function countRecentFailuresByEmail(string $email, int $minutes): int
    {
        return LoginAttempt::where('email', $email)
            ->where('success', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    public function countRecentFailuresByIp(string $ip, int $minutes): int
    {
        return LoginAttempt::where('ip', $ip)
            ->where('success', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    public function countRecentFailuresByFingerprint(string $fingerprint, int $minutes): int
    {
        return LoginAttempt::where('device_fingerprint', $fingerprint)
            ->where('success', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    public function countRecentFailuresByEmailFromDifferentFingerprints(string $email, int $minutes): int
    {
        return LoginAttempt::where('email', $email)
            ->where('success', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->whereNotNull('device_fingerprint')
            ->distinct('device_fingerprint')
            ->count('device_fingerprint');
    }

    public function isDeviceBlocked(string $fingerprint): bool
    {
        return DeviceFingerprint::where('fingerprint_hash', $fingerprint)
            ->where('blocked_until', '>', now())
            ->exists();
    }

    public function isIpBlocked(string $ip): bool
    {
        return DeviceFingerprint::where('fingerprint_hash', 'ip:' . $ip)
            ->where('blocked_until', '>', now())
            ->exists();
    }

    public function blockDevice(string $fingerprint, int $durationMinutes, string $reason): void
    {
        DeviceFingerprint::updateOrCreate(
            ['fingerprint_hash' => $fingerprint],
            [
                'blocked_until' => now()->addMinutes($durationMinutes),
                'block_reason' => $reason,
                'ip_last_seen' => request()->ip(),
                'last_seen_at' => now(),
            ]
        );
    }

    public function blockIp(string $ip, int $durationMinutes, string $reason): void
    {
        DeviceFingerprint::updateOrCreate(
            ['fingerprint_hash' => 'ip:' . $ip],
            [
                'blocked_until' => now()->addMinutes($durationMinutes),
                'block_reason' => $reason,
                'ip_first_seen' => $ip,
                'ip_last_seen' => $ip,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]
        );
    }

    public function getLastSuccessfulAttempt(string $email): ?LoginAttemptData
    {
        $attempt = LoginAttempt::where('email', $email)
            ->where('success', true)
            ->latest('attempted_at')
            ->first();

        if ($attempt === null) {
            return null;
        }

        return new LoginAttemptData(
            email: $attempt->email,
            ip: $attempt->ip,
            deviceFingerprint: $attempt->device_fingerprint,
            userAgent: $attempt->user_agent,
            success: true,
        );
    }
}
