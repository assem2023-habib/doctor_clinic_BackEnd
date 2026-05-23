<?php

namespace App\Http\Middleware;

use App\Domains\Shared\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()?->is_active) {
            return ApiResponse::forbidden(__('auth.user_inactive'));
        }

        return $next($request);
    }
}
