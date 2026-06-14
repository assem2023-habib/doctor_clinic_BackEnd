<?php

namespace App\Domains\Supervisions\Controllers;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\Patients\Models\Patient;
use App\Domains\Shared\Responses\ApiResponse;
use App\Domains\Supervisions\Actions\ApproveSupervisionRequestAction;
use App\Domains\Supervisions\Actions\CancelSupervisionRequestAction;
use App\Domains\Supervisions\Actions\CreateSupervisionRequestAction;
use App\Domains\Supervisions\Actions\RejectSupervisionRequestAction;
use App\Domains\Supervisions\Models\SupervisionRequest;
use App\Domains\Supervisions\Requests\StoreSupervisionRequest;
use App\Domains\Supervisions\Resources\SupervisionRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupervisionRequestController
{
    public function __construct(
        private readonly CreateSupervisionRequestAction $createAction,
        private readonly ApproveSupervisionRequestAction $approveAction,
        private readonly RejectSupervisionRequestAction $rejectAction,
        private readonly CancelSupervisionRequestAction $cancelAction,
    ) {}

    public function store(StoreSupervisionRequest $request, Patient $patient): JsonResponse
    {
        $user = $request->user();
        $isPatient = $user->id === $patient->user_id;
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);

        if (!$isPatient && !$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to create supervision request'));
        }

        $doctor = Doctor::where('user_id', $request->doctor_id)->firstOrFail();

        $result = $this->createAction->execute($patient, $doctor);

        return ApiResponse::created(
            new SupervisionRequestResource($result),
            __('Supervision request created successfully')
        );
    }

    public function indexPatient(Request $request, Patient $patient): JsonResponse
    {
        $user = $request->user();
        $isPatient = $user->id === $patient->user_id;
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);

        if (!$isPatient && !$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to view these requests'));
        }

        $requests = SupervisionRequest::where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success(
            SupervisionRequestResource::collection($requests),
            __('Supervision requests retrieved successfully')
        );
    }

    public function indexDoctor(Request $request, Doctor $doctor): JsonResponse
    {
        $user = $request->user();
        $isDoctor = $user->id === $doctor->user_id;
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);

        if (!$isDoctor && !$isStaff) {
            return ApiResponse::forbidden(__('Unauthorized to view these requests'));
        }

        $status = $request->query('status', 'pending');

        $requests = SupervisionRequest::where('doctor_id', $doctor->id)
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('created_at', 'desc')
            ->get();

        $requests->loadMissing('patient.user');

        return ApiResponse::success(
            SupervisionRequestResource::collection($requests),
            __('Supervision requests retrieved successfully')
        );
    }

    public function approve(Request $request, SupervisionRequest $supervisionRequest): JsonResponse
    {
        $user = $request->user();
        $isDoctor = $user->id === $supervisionRequest->doctor->user_id;

        if (!$isDoctor) {
            return ApiResponse::forbidden(__('Only the doctor can approve this request'));
        }

        $this->approveAction->execute($supervisionRequest, $user);

        return ApiResponse::success(null, __('Supervision request approved'));
    }

    public function reject(Request $request, SupervisionRequest $supervisionRequest): JsonResponse
    {
        $user = $request->user();
        $isDoctor = $user->id === $supervisionRequest->doctor->user_id;

        if (!$isDoctor) {
            return ApiResponse::forbidden(__('Only the doctor can reject this request'));
        }

        $this->rejectAction->execute($supervisionRequest);

        return ApiResponse::success(null, __('Supervision request rejected'));
    }

    public function cancel(Request $request, SupervisionRequest $supervisionRequest): JsonResponse
    {
        $user = $request->user();
        $isPatient = $user->id === $supervisionRequest->patient->user_id;
        $isStaff = $user->hasAnyRole(['admin', 'receptionist']);

        if (!$isPatient && !$isStaff) {
            return ApiResponse::forbidden(__('Only the patient can cancel this request'));
        }

        $this->cancelAction->execute($supervisionRequest);

        return ApiResponse::success(null, __('Supervision request cancelled'));
    }
}
