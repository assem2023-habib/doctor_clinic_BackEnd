<?php

namespace App\Domains\Auth\Actions;

use Illuminate\Support\Facades\Auth;

class LogoutAction
{
    public function execute(): void
    {
        $user = Auth::user();
        $token = $user->token();

        if ($token) {
            $token->revoke();
        }
    }
}
