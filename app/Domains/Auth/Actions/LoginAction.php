<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Auth\DTOs\LoginData;
use App\Domains\Auth\DTOs\LoginSecurityContext;
use App\Domains\Auth\DTOs\TokenData;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Auth\Services\LoginSecurityManager;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;

class LoginAction
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly LoginSecurityManager $securityManager,
    ) {}

    public function execute(LoginData $data): TokenData
    {
        $context = new LoginSecurityContext(
            email: $data->email,
            password: $data->password,
            ip: request()->ip(),
            deviceFingerprint: $data->deviceFingerprint,
            userAgent: $data->userAgent,
        );

        $blockDecision = $this->securityManager->checkBlocked($context);

        if ($blockDecision !== null && $blockDecision->blocked) {
            throw new AuthenticationException($blockDecision->reason);
        }

        $user = User::where('email', $data->email)->first();

        if (!$user || !Hash::check($data->password, $user->password)) {
            $this->securityManager->handleFailure($context);
            throw new AuthenticationException(__('auth.failed'));
        }

        if (!$user->is_active) {
            $this->securityManager->handleFailure($context);
            throw new AuthenticationException(__('auth.not_activated'));
        }

        $this->securityManager->handleSuccess($context);

        return $this->authService->issueToken($data);
    }
}
