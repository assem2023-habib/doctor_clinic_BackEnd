<?php

namespace App\Http\Middleware;

use App\Domains\Shared\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()?->hasRole('admin')) {
            return ApiResponse::forbidden(__('Only admin can perform this action'));
        }

        return $next($request);
    }
}
