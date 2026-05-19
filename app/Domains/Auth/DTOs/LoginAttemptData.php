<?php

namespace App\Domains\Auth\DTOs;

class LoginAttemptData
{
    public function __construct(
        public readonly ?string $email,
        public readonly string $ip,
        public readonly ?string $deviceFingerprint,
        public readonly ?string $userAgent,
        public readonly bool $success,
        public readonly ?string $failureReason = null,
    ) {}
}
