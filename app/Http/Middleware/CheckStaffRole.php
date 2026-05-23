<?php

namespace App\Http\Middleware;

use App\Domains\Shared\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class CheckStaffRole
{
    public function handle(Request $request, Closure $next, ...$extraRoles)
    {
        $roles = array_merge(['admin', 'receptionist'], $extraRoles);

        if (!$request->user()?->hasAnyRole($roles)) {
            return ApiResponse::forbidden(__('Only staff can perform this action'));
        }

        return $next($request);
    }
}
