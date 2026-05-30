<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Doctors\Resources\DoctorResource;
use App\Domains\Patients\Resources\PatientResource;
use App\Domains\Receptionists\Resources\ReceptionistResource;
use App\Domains\Shared\Resources\UserResource;
use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Users\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController
{
    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);

        $users = User::with('roles', 'city', 'country')
            ->when($request->role, fn ($q, $v) => $q->whereHas('roles', fn ($q) => $q->where('slug', $v)))
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%")
                  ->orWhere('username', 'like', "%{$v}%");
            }))
            ->when($request->gender, fn ($q, $v) => $q->where('gender', $v))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            UserResource::collection($users),
            __('Users retrieved successfully'),
            pagination: ApiResponse::pagination($users)
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles', 'city', 'country');

        $resource = match (true) {
            $user->hasRole('patient') => new PatientResource($user),
            $user->hasRole('doctor') => new DoctorResource($user),
            $user->hasRole('receptionist') => new ReceptionistResource($user),
            default => new UserResource($user),
        };

        return ApiResponse::success($resource, __('User retrieved successfully'));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->only([
            'first_name', 'last_name', 'username', 'email',
            'phone', 'address', 'gender', 'birthday_date', 'city_id',
        ]));

        $user->load('roles', 'city', 'country');

        return ApiResponse::success(
            new UserResource($user),
            __('User updated successfully')
        );
    }

    public function toggleActive(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        return ApiResponse::success(
            ['is_active' => $user->is_active],
            $user->is_active ? __('User activated successfully') : __('User deactivated successfully')
        );
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->hasRole('admin')) {
            return ApiResponse::forbidden(__('Admin users cannot be deleted'));
        }

        $user->delete();

        return ApiResponse::noContent(__('User deleted successfully'));
    }
}
