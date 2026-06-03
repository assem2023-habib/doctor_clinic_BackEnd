<?php

namespace App\Domains\Supervisions\Controllers;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use App\Domains\Patients\Models\Patient;
use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Supervisions\Actions\AssignPatientToDoctorAction;
use App\Domains\Supervisions\Actions\BulkAssignPatientsToDoctorAction;
use App\Domains\Supervisions\Actions\RemovePatientFromDoctorAction;
use App\Domains\Supervisions\Enums\SupervisionRequestStatusEnum;
use App\Domains\Supervisions\Models\SupervisionRequest;
use App\Domains\Supervisions\Requests\AssignPatientRequest;
use App\Domains\Supervisions\Requests\BulkAssignPatientRequest;
use App\Domains\Supervisions\Resources\SupervisionDoctorResource;
use App\Domains\Supervisions\Resources\SupervisionPatientResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupervisionController
{
    public function __construct(
        private readonly AssignPatientToDoctorAction $assignAction,
        private readonly RemovePatientFromDoctorAction $removeAction,
        private readonly BulkAssignPatientsToDoctorAction $bulkAssignAction,
        private readonly NotificationManager $notificationManager,
    ) {}

    public function doctorPatients(Request $request, Doctor $doctor): JsonResponse
    {
        $user = $request->user();
        $isDoctor = $user->id === $doctor->user_id;
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);

        if (!$isDoctor && !$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to view these patients'));
        }

        $limit = (int) $request->integer('limit', 20);

        $patientQuery = $doctor->patients();
        if ($request->filled('status')) {
            $patientQuery->wherePivotIn('supervision_status', (array) $request->status);
        }

        $patients = User::whereHas('patient', fn ($q) => $q->whereIn('id', $patientQuery->pluck('patient_id')))
            ->with(['patient', 'image', 'roles'])
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%");
            }))
            ->paginate(min($limit, 100));

        $patients->getCollection()->each(function ($user) use ($doctor) {
            $pivot = $doctor->patients()->find($user->patient->id)?->pivot;
            $user->setRelation('pivot', $pivot);
        });

        return ApiResponse::success(
            SupervisionPatientResource::collection($patients),
            __('Patients retrieved successfully'),
            pagination: ApiResponse::pagination($patients)
        );
    }

    public function patientDoctors(Request $request, Patient $patient): JsonResponse
    {
        $user = $request->user();
        $isPatient = $user->id === $patient->user_id;
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);

        if (!$isPatient && !$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to view these doctors'));
        }

        $limit = (int) $request->integer('limit', 20);

        $doctors = User::whereHas('doctor', fn ($q) => $q->whereIn('id', $patient->doctors()->pluck('doctor_id')))
            ->with(['doctor', 'image', 'roles'])
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%");
            }))
            ->paginate(min($limit, 100));

        $doctors->getCollection()->each(function ($user) use ($patient) {
            $pivot = $patient->doctors()->find($user->doctor->id)?->pivot;
            $user->setRelation('pivot', $pivot);
        });

        return ApiResponse::success(
            SupervisionDoctorResource::collection($doctors),
            __('Doctors retrieved successfully'),
            pagination: ApiResponse::pagination($doctors)
        );
    }

    public function selfAssign(Request $request, Doctor $doctor): JsonResponse
    {
        $user = $request->user();
        $isDoctor = $user->id === $doctor->user_id;

        if (!$isDoctor) {
            return ApiResponse::forbidden(__('Unauthorized to self-assign patients'));
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'string', 'exists:patients,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        $this->assignAction->execute($doctor, $patient, $user, $validated['notes'] ?? null);

        return ApiResponse::success(null, __('Patient assigned to doctor successfully'));
    }

    public function assign(AssignPatientRequest $request, Doctor $doctor): JsonResponse
    {
        $user = $request->user();
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);

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
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);
        $isDoctor = $user->id === $doctor->user_id;

        if (!$isStaff && !$isDoctor) {
            return ApiResponse::forbidden(__('Unauthorized to remove patients'));
        }

        $role = $isDoctor ? 'doctor' : 'receptionist';
        $this->removeAction->execute($doctor, $patient, $user, $role);

        return ApiResponse::success(null, __('Patient removed from doctor successfully'));
    }

    public function patientRemoveDoctor(Request $request, Patient $patient, Doctor $doctor): JsonResponse
    {
        $user = $request->user();
        $isPatient = $user->id === $patient->user_id;

        if (!$isPatient) {
            return ApiResponse::forbidden(__('Unauthorized to remove doctor'));
        }

        $doctor->loadMissing('user');

        SupervisionRequest::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => SupervisionRequestStatusEnum::Cancelled,
            'responded_at' => now(),
        ]);

        $this->removeAction->execute($doctor, $patient, $user, 'patient');

        $this->notificationManager->send('supervision.cancelled', new NotificationData(
            topic: 'supervision.cancelled',
            title: __('Patient removed you from supervision'),
            body: [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
            ],
            userIds: [$doctor->user_id],
        ));

        return ApiResponse::success(null, __('Doctor removed successfully'));
    }

    public function availableDoctors(Request $request, Patient $patient): JsonResponse
    {
        $user = $request->user();
        $isPatient = $user->id === $patient->user_id;
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);

        if (!$isPatient && !$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to view available doctors'));
        }

        $assignedDoctorIds = $patient->doctors()->pluck('doctor_id');

        $limit = (int) $request->integer('limit', 20);

        $doctors = User::whereHas('doctor', fn ($q) => $q->whereNotIn('id', $assignedDoctorIds))
            ->with(['doctor', 'image', 'roles'])
            ->when($request->specialization_id, fn ($q, $v) => $q->whereHas('doctor', fn ($q) => $q->where('specialization_id', $v)))
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('first_name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%");
            }))
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            \App\Domains\Doctors\Resources\DoctorResource::collection($doctors),
            __('Available doctors retrieved successfully'),
            pagination: ApiResponse::pagination($doctors)
        );
    }

    public function bulkAssign(BulkAssignPatientRequest $request, Doctor $doctor): JsonResponse
    {
        $user = $request->user();
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);

        if (!$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to assign patients'));
        }

        $result = $this->bulkAssignAction->execute($doctor, $request->patient_ids, $user, $request->notes);

        $data = [
            'assigned_count' => count($result['assigned']),
            'skipped_count' => count($result['skipped']),
            'error_count' => count($result['errors']),
            'assigned' => $result['assigned'],
        ];

        if (!empty($result['skipped'])) {
            $data['skipped'] = $result['skipped'];
        }

        if (!empty($result['errors'])) {
            $data['errors'] = $result['errors'];
        }

        return ApiResponse::success($data, __('Bulk assign completed'));
    }
}
