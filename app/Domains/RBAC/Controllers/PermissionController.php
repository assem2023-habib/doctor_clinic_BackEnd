<?php

namespace App\Domains\RBAC\Controllers;

use App\Domains\RBAC\Models\Permission;
use App\Domains\RBAC\Requests\StorePermissionRequest;
use App\Domains\RBAC\Requests\UpdatePermissionRequest;
use App\Domains\RBAC\Resources\PermissionResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PermissionController
{
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 50);
        $version = Cache::get('permissions:cache_version', 0);
        $cacheKey = 'permissions:index:v' . $version . ':' . md5(serialize($request->only(['search', 'group', 'page', 'limit'])));

        $permissions = Cache::remember($cacheKey, 172800, function () use ($request, $limit) {
            return Permission::query()
                ->when($request->group, fn ($q, $v) => $q->where('group', $v))
                ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%")
                    ->orWhere('slug', 'like', "%{$v}%"))
                ->paginate(min($limit, 200));
        });

        return ApiResponse::success(
            PermissionResource::collection($permissions),
            __('Permissions retrieved successfully'),
            pagination: ApiResponse::pagination($permissions)
        );
    }

    public function show(Permission $permission): JsonResponse
    {
        $version = Cache::get('permissions:cache_version', 0);
        $cacheKey = 'permissions:show:v' . $version . ':' . $permission->id;

        $permission = Cache::remember($cacheKey, 172800, function () use ($permission) {
            return $permission;
        });

        return ApiResponse::success(
            new PermissionResource($permission),
            __('Permission retrieved successfully')
        );
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());

        return ApiResponse::created(
            new PermissionResource($permission),
            __('Permission created successfully')
        );
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission->update($request->validated());

        return ApiResponse::success(
            new PermissionResource($permission->fresh()),
            __('Permission updated successfully')
        );
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $permission->delete();

        return ApiResponse::noContent(__('Permission deleted successfully'));
    }
}
