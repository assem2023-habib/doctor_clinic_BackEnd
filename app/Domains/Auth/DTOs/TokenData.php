<?php

namespace App\Domains\Auth\DTOs;

class TokenData
{
    private function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly int $expiresIn,
    ) {}

    public static function fromPassportResponse(array $response): self
    {
        return new self(
            accessToken: $response['access_token'],
            refreshToken: $response['refresh_token'],
            expiresIn: $response['expires_in'],
        );
    }
}
