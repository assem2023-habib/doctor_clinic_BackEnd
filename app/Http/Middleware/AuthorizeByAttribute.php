<?php

namespace App\Http\Middleware;

use App\Domains\RBAC\Attributes\Role;
use App\Domains\RBAC\Services\PermissionService;
use App\Domains\Shared\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;

class AuthorizeByAttribute
{
    public function handle(Request $request, Closure $next)
    {
        $controller = $request->route()->getController();
        $method = $request->route()->getActionMethod();
        $user = $request->user();

        $allowedRoles = $this->resolveRoles($controller, $method);

        if (empty($allowedRoles)) {
            return $next($request);
        }

        if (!PermissionService::hasAnyRole($user, $allowedRoles)) {
            return ApiResponse::forbidden(__('Unauthorized'));
        }

        return $next($request);
    }

    private function resolveRoles(object $controller, string $method): array
    {
        $roles = [];

        $reflection = new ReflectionClass($controller);
        $classAttrs = $reflection->getAttributes(Role::class);
        foreach ($classAttrs as $attr) {
            $roles = array_merge($roles, $attr->newInstance()->roles);
        }

        $methodReflection = new ReflectionMethod($controller, $method);
        $methodAttrs = $methodReflection->getAttributes(Role::class);
        foreach ($methodAttrs as $attr) {
            $roles = array_merge($roles, $attr->newInstance()->roles);
        }

        return array_unique($roles);
    }
}
