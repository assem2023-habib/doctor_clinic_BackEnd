<?php

namespace App\Domains\Supervisions\Controllers;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Supervisions\Actions\AssignPatientToDoctorAction;
use App\Domains\Supervisions\Actions\RemovePatientFromDoctorAction;
use App\Domains\Supervisions\Requests\AssignPatientRequest;
use App\Domains\Supervisions\Resources\SupervisionDoctorResource;
use App\Domains\Supervisions\Resources\SupervisionPatientResource;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupervisionController
{
    public function __construct(
        private readonly AssignPatientToDoctorAction $assignAction,
        private readonly RemovePatientFromDoctorAction $removeAction,
    ) {}

    public function doctorPatients(Request $request, Doctor $doctor): JsonResponse
    {
        $user = $request->user();
        $isDoctor = $user->id === $doctor->user_id;
        $isStaff = in_array($user->role->value, [RoleEnum::Admin->value, RoleEnum::Receptionist->value]);

        if (!$isDoctor && !$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to view these patients'));
        }

        $patients = User::whereHas('patient', fn ($q) => $q->whereIn('id', $doctor->patients()->pluck('patient_id')))
            ->with(['patient', 'image'])
            ->get();

        $patients->each(function ($user) use ($doctor) {
            $pivot = $doctor->patients()->find($user->patient->id)?->pivot;
            $user->setRelation('pivot', $pivot);
        });

        return ApiResponse::success(
            SupervisionPatientResource::collection($patients),
            __('Patients retrieved successfully')
        );
    }

    public function patientDoctors(Request $request, Patient $patient): JsonResponse
    {
        $user = $request->user();
        $isPatient = $user->id === $patient->user_id;
        $isStaff = in_array($user->role->value, [RoleEnum::Admin->value, RoleEnum::Receptionist->value]);

        if (!$isPatient && !$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to view these doctors'));
        }

        $doctors = User::whereHas('doctor', fn ($q) => $q->whereIn('id', $patient->doctors()->pluck('doctor_id')))
            ->with(['doctor', 'image'])
            ->get();

        $doctors->each(function ($user) use ($patient) {
            $pivot = $patient->doctors()->find($user->doctor->id)?->pivot;
            $user->setRelation('pivot', $pivot);
        });

        return ApiResponse::success(
            SupervisionDoctorResource::collection($doctors),
            __('Doctors retrieved successfully')
        );
    }

    public function assign(AssignPatientRequest $request, Doctor $doctor): JsonResponse
    {
        $user = $request->user();
        $isStaff = in_array($user->role->value, [RoleEnum::Admin->value, RoleEnum::Receptionist->value]);

        if (!$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to assign patients'));
        }

        $patient = Patient::findOrFail($request->patient_id);

        $this->assignAction->execute($doctor, $patient, $user, $request->notes);

        return ApiResponse::success(null, __('Patient assigned to doctor successfully'));
    }

    public function remove(Request $request, Doctor $doctor, Patient $patient): JsonResponse
    {
        $user = $request->user();
        $isStaff = in_array($user->role->value, [RoleEnum::Admin->value, RoleEnum::Receptionist->value]);

        if (!$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to remove patients'));
        }

        $this->removeAction->execute($doctor, $patient);

        return ApiResponse::success(null, __('Patient removed from doctor successfully'));
    }
}
