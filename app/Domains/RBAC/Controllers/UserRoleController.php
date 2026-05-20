<?php

namespace App\Domains\RBAC\Controllers;

use App\Domains\RBAC\Requests\SyncUserRolesRequest;
use App\Domains\RBAC\Resources\RoleResource;
use App\Domains\RBAC\Services\PermissionService;
use App\Domains\Shared\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserRoleController
{
    public function getUserRoles(User $user): JsonResponse
    {
        $roles = $user->roles()->with('permissions')->get();

        return ApiResponse::success(
            RoleResource::collection($roles),
            __('User roles retrieved successfully')
        );
    }

    public function syncUserRoles(SyncUserRolesRequest $request, User $user): JsonResponse
    {
        PermissionService::syncRoles($user, $request->roles);

        $roles = $user->roles()->with('permissions')->get();

        return ApiResponse::success(
            RoleResource::collection($roles),
            __('User roles synced successfully')
        );
    }
}
