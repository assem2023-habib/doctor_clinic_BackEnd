<?php

namespace App\Domains\RBAC\Services;

use App\Domains\RBAC\Models\Role;
use App\Models\User;

class PermissionService
{
    public static function hasRole(User $user, string $slug): bool
    {
        return $user->roles()->where('slug', $slug)->exists();
    }

    public static function hasAnyRole(User $user, array $slugs): bool
    {
        return $user->roles()->whereIn('slug', $slugs)->exists();
    }

    public static function hasAllRoles(User $user, array $slugs): bool
    {
        $count = $user->roles()->whereIn('slug', $slugs)->count();
        return $count === count($slugs);
    }

    public static function hasPermission(User $user, string $slug): bool
    {
        return $user->roles()->whereHas('permissions', fn ($q) => $q->where('slug', $slug))->exists();
    }

    public static function hasAnyPermission(User $user, array $slugs): bool
    {
        return $user->roles()->whereHas('permissions', fn ($q) => $q->whereIn('slug', $slugs))->exists();
    }

    public static function hasAllPermissions(User $user, array $slugs): bool
    {
        $userPermissionSlugs = self::getUserPermissionSlugs($user);
        return empty(array_diff($slugs, $userPermissionSlugs));
    }

    public static function getUserRoles(User $user): array
    {
        return $user->roles()->pluck('slug')->toArray();
    }

    public static function getUserPermissions(User $user): array
    {
        return $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->values()
            ->toArray();
    }

    public static function getUserPermissionSlugs(User $user): array
    {
        return $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions.*.slug')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }

    public static function assignRole(User $user, string $slug): void
    {
        $role = Role::where('slug', $slug)->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    public static function removeRole(User $user, string $slug): void
    {
        $role = Role::where('slug', $slug)->first();
        if ($role) {
            $user->roles()->detach($role->id);
        }
    }

    public static function syncRoles(User $user, array $slugs): void
    {
        $roleIds = Role::whereIn('slug', $slugs)->pluck('id');
        $user->roles()->sync($roleIds);
    }
}
