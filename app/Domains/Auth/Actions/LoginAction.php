<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\LoginData;
use App\Domains\Auth\DTOs\TokenData;
use App\Domains\Auth\Services\AuthService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\AuthenticationException;

class LoginAction
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function execute(LoginData $data): TokenData
    {
        $user = User::where('email', $data->email)->first();

        if (!$user || !Hash::check($data->password, $user->password)) {
            throw new AuthenticationException(__('auth.failed'));
        }

        if (!$user->is_active) {
            throw new AuthenticationException(__('auth.not_activated'));
        }

        return $this->authService->issueToken($data);
    }
}
