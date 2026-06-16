<?php

namespace App\Domains\RBAC\Controllers;

use App\Domains\RBAC\Models\Role;
use App\Domains\RBAC\Requests\StoreRoleRequest;
use App\Domains\RBAC\Requests\SyncRolePermissionsRequest;
use App\Domains\RBAC\Requests\UpdateRoleRequest;
use App\Domains\RBAC\Resources\RoleResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RoleController
{
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $version = Cache::get('roles:cache_version', 0);
        $cacheKey = 'roles:index:v' . $version . ':' . md5(serialize($request->only(['search', 'page', 'limit'])));

        $roles = Cache::remember($cacheKey, 172800, function () use ($request, $limit) {
            return Role::withCount('users')
                ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%")
                    ->orWhere('slug', 'like', "%{$v}%"))
                ->paginate(min($limit, 100));
        });

        return ApiResponse::success(
            RoleResource::collection($roles),
            __('Roles retrieved successfully'),
            pagination: ApiResponse::pagination($roles)
        );
    }

    public function show(Role $role): JsonResponse
    {
        $version = Cache::get('roles:cache_version', 0);
        $cacheKey = 'roles:show:v' . $version . ':' . $role->id;

        $role = Cache::remember($cacheKey, 172800, function () use ($role) {
            return $role->load('permissions');
        });

        return ApiResponse::success(
            new RoleResource($role),
            __('Role retrieved successfully')
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create($request->only(['name', 'slug', 'description', 'guard_name']));

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $role->load('permissions');

        return ApiResponse::created(
            new RoleResource($role),
            __('Role created successfully')
        );
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        if ($role->is_system) {
            return ApiResponse::forbidden(__('System roles cannot be modified'));
        }

        $role->update($request->only(['name', 'slug', 'description']));

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $role->load('permissions');

        return ApiResponse::success(
            new RoleResource($role),
            __('Role updated successfully')
        );
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return ApiResponse::forbidden(__('System roles cannot be deleted'));
        }

        $role->delete();

        return ApiResponse::noContent(__('Role deleted successfully'));
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $role->syncPermissions($request->permissions);
        $role->load('permissions');

        return ApiResponse::success(
            new RoleResource($role),
            __('Permissions synced successfully')
        );
    }
}
