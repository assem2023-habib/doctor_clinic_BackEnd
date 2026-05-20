<?php

namespace App\Domains\RBAC\Controllers;

use App\Domains\RBAC\Models\Permission;
use App\Domains\RBAC\Requests\StorePermissionRequest;
use App\Domains\RBAC\Requests\UpdatePermissionRequest;
use App\Domains\RBAC\Resources\PermissionResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController
{
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 50);
        $permissions = Permission::query()
            ->when($request->group, fn ($q, $v) => $q->where('group', $v))
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%")
                ->orWhere('slug', 'like', "%{$v}%"))
            ->paginate(min($limit, 200));

        return ApiResponse::success(
            PermissionResource::collection($permissions),
            __('Permissions retrieved successfully'),
            pagination: ApiResponse::pagination($permissions)
        );
    }

    public function show(Permission $permission): JsonResponse
    {
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
