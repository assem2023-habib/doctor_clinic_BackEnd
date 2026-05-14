<?php

namespace App\Http\Middleware;

use App\Domains\Shared\Responses\ApiResponse;
use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;

class CheckStaffRole
{
    public function handle(Request $request, Closure $next)
    {
        $role = $request->user()?->role;

        if ($role !== RoleEnum::Admin && $role !== RoleEnum::Receptionist) {
            return ApiResponse::forbidden(__('Only admin or receptionist can perform this action'));
        }

        return $next($request);
    }
}
