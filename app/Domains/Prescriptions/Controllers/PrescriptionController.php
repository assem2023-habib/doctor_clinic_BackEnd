<?php

namespace App\Domains\Prescriptions\Controllers;

use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Prescriptions\Actions\CreatePrescriptionAction;
use App\Domains\Prescriptions\Actions\DeletePrescriptionAction;
use App\Domains\Prescriptions\Actions\UpdatePrescriptionAction;
use App\Domains\Prescriptions\Models\Prescription;
use App\Domains\Prescriptions\Requests\StorePrescriptionRequest;
use App\Domains\Prescriptions\Requests\UpdatePrescriptionRequest;
use App\Domains\Prescriptions\Resources\PrescriptionResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionController
{
    public function __construct(
        private readonly CreatePrescriptionAction $createAction,
        private readonly UpdatePrescriptionAction $updateAction,
        private readonly DeletePrescriptionAction $deleteAction,
    ) {}

    public function index(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $user = $request->user();

        if (!$this->canAccessMedicalRecord($user, $medicalRecord)) {
            return ApiResponse::forbidden();
        }

        $limit = (int) $request->integer('limit', 20);

        $prescriptions = $medicalRecord->prescriptions()
            ->orderBy('created_at', 'desc')
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            PrescriptionResource::collection($prescriptions),
            __('Prescriptions retrieved successfully'),
            pagination: ApiResponse::pagination($prescriptions)
        );
    }

    public function show(Request $request, Prescription $prescription): JsonResponse
    {
        $user = $request->user();

        if (!$this->canAccessPrescription($user, $prescription)) {
            return ApiResponse::forbidden();
        }

        return ApiResponse::success(
            new PrescriptionResource($prescription),
            __('Prescription retrieved successfully')
        );
    }

    public function store(StorePrescriptionRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('doctor') && $user->doctor?->id !== $medicalRecord->doctor_id) {
            return ApiResponse::forbidden(__('You can only add prescriptions to your own patients'));
        }

        $prescription = $this->createAction->execute($medicalRecord, $request->validated());

        return ApiResponse::created(
            new PrescriptionResource($prescription),
            __('Prescription created successfully')
        );
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('doctor') && $user->doctor?->id !== $prescription->medicalRecord->doctor_id) {
            return ApiResponse::forbidden(__('You can only update your own prescriptions'));
        }

        $prescription = $this->updateAction->execute($prescription, $request->validated());

        return ApiResponse::success(
            new PrescriptionResource($prescription),
            __('Prescription updated successfully')
        );
    }

    public function destroy(Request $request, Prescription $prescription): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['admin', 'doctor'])) {
            return ApiResponse::forbidden();
        }

        if ($user->hasRole('doctor') && $user->doctor?->id !== $prescription->medicalRecord->doctor_id) {
            return ApiResponse::forbidden(__('You can only delete your own prescriptions'));
        }

        $this->deleteAction->execute($prescription);

        return ApiResponse::noContent(__('Prescription deleted successfully'));
    }

    private function canAccessMedicalRecord($user, MedicalRecord $medicalRecord): bool
    {
        if ($user->hasAnyRole(['admin', 'receptionist'])) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return $user->doctor?->id === $medicalRecord->doctor_id;
        }

        if ($user->hasRole('patient')) {
            return $user->id === $medicalRecord->patient_id;
        }

        return false;
    }

    private function canAccessPrescription($user, Prescription $prescription): bool
    {
        $medicalRecord = $prescription->medicalRecord;

        if ($user->hasAnyRole(['admin', 'receptionist'])) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return $user->doctor?->id === $medicalRecord->doctor_id;
        }

        if ($user->hasRole('patient')) {
            return $user->id === $medicalRecord->patient_id;
        }

        return false;
    }
}
