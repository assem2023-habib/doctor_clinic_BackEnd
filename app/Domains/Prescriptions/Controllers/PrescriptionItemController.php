<?php

namespace App\Domains\Prescriptions\Controllers;

use App\Domains\Prescriptions\Actions\CreatePrescriptionItemAction;
use App\Domains\Prescriptions\Actions\DeletePrescriptionItemAction;
use App\Domains\Prescriptions\Actions\UpdatePrescriptionItemAction;
use App\Domains\Prescriptions\Models\Prescription;
use App\Domains\Prescriptions\Models\PrescriptionItem;
use App\Domains\Prescriptions\Requests\StorePrescriptionItemRequest;
use App\Domains\Prescriptions\Requests\UpdatePrescriptionItemRequest;
use App\Domains\Prescriptions\Resources\PrescriptionItemResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionItemController
{
    public function __construct(
        private readonly CreatePrescriptionItemAction $createAction,
        private readonly UpdatePrescriptionItemAction $updateAction,
        private readonly DeletePrescriptionItemAction $deleteAction,
    ) {}

    public function index(Request $request, Prescription $prescription): JsonResponse
    {
        $user = $request->user();

        if (!$this->canAccessPrescription($user, $prescription)) {
            return ApiResponse::forbidden();
        }

        $items = $prescription->items()->orderBy('created_at')->get();

        return ApiResponse::success(
            PrescriptionItemResource::collection($items),
            __('Prescription items retrieved successfully')
        );
    }

    public function store(StorePrescriptionItemRequest $request, Prescription $prescription): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('doctor') && $user->doctor?->id !== $prescription->medicalRecord->doctor_id) {
            return ApiResponse::forbidden(__('You can only add items to your own prescriptions'));
        }

        $item = $this->createAction->execute($prescription, $request->validated());

        return ApiResponse::created(
            new PrescriptionItemResource($item),
            __('Prescription item created successfully')
        );
    }

    public function show(Request $request, PrescriptionItem $prescriptionItem): JsonResponse
    {
        $user = $request->user();
        $prescription = $prescriptionItem->prescription;

        if (!$this->canAccessPrescription($user, $prescription)) {
            return ApiResponse::forbidden();
        }

        return ApiResponse::success(
            new PrescriptionItemResource($prescriptionItem),
            __('Prescription item retrieved successfully')
        );
    }

    public function update(UpdatePrescriptionItemRequest $request, PrescriptionItem $prescriptionItem): JsonResponse
    {
        $user = $request->user();
        $prescription = $prescriptionItem->prescription;

        if ($user->hasRole('doctor') && $user->doctor?->id !== $prescription->medicalRecord->doctor_id) {
            return ApiResponse::forbidden(__('You can only update items in your own prescriptions'));
        }

        $item = $this->updateAction->execute($prescriptionItem, $request->validated());

        return ApiResponse::success(
            new PrescriptionItemResource($item),
            __('Prescription item updated successfully')
        );
    }

    public function destroy(Request $request, PrescriptionItem $prescriptionItem): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['admin', 'doctor'])) {
            return ApiResponse::forbidden();
        }

        $prescription = $prescriptionItem->prescription;

        if ($user->hasRole('doctor') && $user->doctor?->id !== $prescription->medicalRecord->doctor_id) {
            return ApiResponse::forbidden(__('You can only delete items from your own prescriptions'));
        }

        $this->deleteAction->execute($prescriptionItem);

        return ApiResponse::noContent(__('Prescription item deleted successfully'));
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
