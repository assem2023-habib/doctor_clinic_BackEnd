<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Resources\DoctorResource;
use App\Domains\Shared\Responses\ApiResponse;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 20);
        $doctors = User::where('role', RoleEnum::Doctor)
            ->with('doctor.schedules')
            ->paginate(min($perPage, 100));

        return ApiResponse::success(
            DoctorResource::collection($doctors),
            __('Doctors retrieved successfully'),
            pagination: [
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'per_page' => $doctors->perPage(),
                'total' => $doctors->total(),
                'from' => $doctors->firstItem(),
                'to' => $doctors->lastItem(),
            ]
        );
    }

    public function show(Doctor $doctor): JsonResponse
    {
        $doctor->load('user', 'schedules');
        $user = $doctor->user;
        $user->setRelation('doctor', $doctor);

        return ApiResponse::success(
            new DoctorResource($user),
            __('Doctor retrieved successfully')
        );
    }
}
