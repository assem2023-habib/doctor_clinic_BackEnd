<?php

namespace App\Http\Middleware;

use App\Domains\Shared\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class CheckStaffRole
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()?->hasAnyRole(['admin', 'receptionist'])) {
            return ApiResponse::forbidden(__('Only admin or receptionist can perform this action'));
        }

        return $next($request);
    }
}
