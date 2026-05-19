<?php

namespace App\Domains\Auth\DTOs;

class LoginSecurityContext
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $ip,
        public readonly ?string $deviceFingerprint = null,
        public readonly ?string $userAgent = null,
    ) {}
}
