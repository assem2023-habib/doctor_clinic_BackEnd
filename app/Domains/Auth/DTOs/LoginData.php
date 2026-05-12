<?php

namespace App\Domains\Auth\DTOs;

use App\Domains\Auth\Requests\LoginRequest;

class LoginData
{
    private function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->email,
            password: $request->password,
        );
    }

    public static function fromCredentials(string $email, string $password): self
    {
        return new self(
            email: $email,
            password: $password,
        );
    }
}
