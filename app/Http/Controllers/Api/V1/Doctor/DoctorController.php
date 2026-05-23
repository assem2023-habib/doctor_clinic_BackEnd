<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Domains\Doctors\Actions\ActivateDoctorAccountAction;
use App\Domains\Doctors\Actions\DeleteDoctorAction;
use App\Domains\Doctors\Actions\UpdateDoctorAction;
use App\Domains\Doctors\Models\Doctor;
use App\Domains\Doctors\Requests\PatchDoctorRequest;
use App\Domains\Doctors\Requests\UpdateDoctorRequest;
use App\Domains\Doctors\Resources\DoctorResource;
use App\Domains\Shared\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController
{
    public function __construct(
        private readonly UpdateDoctorAction $updateDoctorAction,
        private readonly DeleteDoctorAction $deleteDoctorAction,
        private readonly ActivateDoctorAccountAction $activateDoctorAccountAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $doctors = User::whereHas('roles', fn($q) => $q->where('slug', 'doctor'))
            ->with(['doctor.schedules', 'roles'])
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%");
            }))
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            DoctorResource::collection($doctors),
            __('Doctors retrieved successfully'),
            pagination: ApiResponse::pagination($doctors)
        );
    }

    public function show(Doctor $doctor): JsonResponse
    {
        $doctor->load('user.roles', 'schedules');
        $user = $doctor->user;
        $user->setRelation('doctor', $doctor);

        return ApiResponse::success(
            new DoctorResource($user),
            __('Doctor retrieved successfully')
        );
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $dto = \App\Domains\Doctors\DTOs\UpdateDoctorData::fromRequest($request);
        $user = $this->updateDoctorAction->execute($doctor, $dto);

        return ApiResponse::success(
            new DoctorResource($user),
            __('Doctor updated successfully')
        );
    }

    public function updatePartial(PatchDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $dto = \App\Domains\Doctors\DTOs\UpdateDoctorData::fromRequestPartial($request);
        $user = $this->updateDoctorAction->execute($doctor, $dto);

        return ApiResponse::success(
            new DoctorResource($user),
            __('Doctor updated successfully')
        );
    }

    public function destroy(Doctor $doctor): JsonResponse
    {
        $this->deleteDoctorAction->execute($doctor, request()->user());

        return ApiResponse::noContent(__('Doctor deleted successfully'));
    }

    public function activateAccount(Doctor $doctor): JsonResponse
    {
        $doctor = $this->activateDoctorAccountAction->execute($doctor);

        return ApiResponse::success(
            new DoctorResource($doctor->user),
            __('auth.account_activated')
        );
    }
}
