<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Domains\Patients\Actions\DeletePatientAction;
use App\Domains\Patients\Actions\UpdatePatientAction;
use App\Domains\Patients\Models\Patient;
use App\Domains\Patients\Requests\PatchPatientRequest;
use App\Domains\Patients\Requests\UpdatePatientRequest;
use App\Domains\Patients\Resources\PatientResource;
use App\Domains\Shared\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController
{
    public function __construct(
        private readonly UpdatePatientAction $updatePatientAction,
        private readonly DeletePatientAction $deletePatientAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);
        $patients = User::whereHas('roles', fn($q) => $q->where('slug', 'patient'))
            ->with(['patient', 'roles'])
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%");
            }))
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            PatientResource::collection($patients),
            __('Patients retrieved successfully'),
            pagination: ApiResponse::pagination($patients)
        );
    }

    public function show(Patient $patient): JsonResponse
    {
        $patient->load('user.roles');
        $user = $patient->user;
        $user->setRelation('patient', $patient);

        return ApiResponse::success(
            new PatientResource($user),
            __('Patient retrieved successfully')
        );
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $dto = \App\Domains\Patients\DTOs\UpdatePatientData::fromRequest($request);
        $user = $this->updatePatientAction->execute($patient, $dto);

        return ApiResponse::success(
            new PatientResource($user),
            __('Patient updated successfully')
        );
    }

    public function updatePartial(PatchPatientRequest $request, Patient $patient): JsonResponse
    {
        $dto = \App\Domains\Patients\DTOs\UpdatePatientData::fromRequestPartial($request);
        $user = $this->updatePatientAction->execute($patient, $dto);

        return ApiResponse::success(
            new PatientResource($user),
            __('Patient updated successfully')
        );
    }

    public function destroy(Patient $patient): JsonResponse
    {
        $this->deletePatientAction->execute($patient, request()->user());

        return ApiResponse::noContent(__('Patient deleted successfully'));
    }
}
