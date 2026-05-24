<?php

namespace App\Domains\Prescriptions\Controllers;

use App\Domains\Prescriptions\Actions\CreateMedicineAction;
use App\Domains\Prescriptions\Actions\DeleteMedicineAction;
use App\Domains\Prescriptions\Actions\UpdateMedicineAction;
use App\Domains\Prescriptions\Models\Medicine;
use App\Domains\Prescriptions\Requests\StoreMedicineRequest;
use App\Domains\Prescriptions\Requests\UpdateMedicineRequest;
use App\Domains\Prescriptions\Resources\MedicineResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicineController
{
    public function __construct(
        private readonly CreateMedicineAction $createAction,
        private readonly UpdateMedicineAction $updateAction,
        private readonly DeleteMedicineAction $deleteAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 20);

        $medicines = Medicine::when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
            $q->where('name->ar', 'like', "%{$v}%")
              ->orWhere('name->en', 'like', "%{$v}%")
              ->orWhere('description->ar', 'like', "%{$v}%")
              ->orWhere('description->en', 'like', "%{$v}%")
              ->orWhere('barcode', 'like', "%{$v}%")
              ->orWhere('manufacturer', 'like', "%{$v}%");
        }))
            ->when($request->manufacturer, fn ($q, $v) => $q->where('manufacturer', $v))
            ->orderBy('name->en')
            ->paginate(min($limit, 100));

        return ApiResponse::success(
            MedicineResource::collection($medicines),
            __('Medicines retrieved successfully'),
            pagination: ApiResponse::pagination($medicines)
        );
    }

    public function show(Medicine $medicine): JsonResponse
    {
        return ApiResponse::success(
            new MedicineResource($medicine),
            __('Medicine retrieved successfully')
        );
    }

    public function store(StoreMedicineRequest $request): JsonResponse
    {
        $medicine = $this->createAction->execute($request->validated());

        return ApiResponse::created(
            new MedicineResource($medicine),
            __('Medicine created successfully')
        );
    }

    public function update(UpdateMedicineRequest $request, Medicine $medicine): JsonResponse
    {
        $medicine = $this->updateAction->execute($medicine, $request->validated());

        return ApiResponse::success(
            new MedicineResource($medicine),
            __('Medicine updated successfully')
        );
    }

    public function destroy(Medicine $medicine): JsonResponse
    {
        $this->deleteAction->execute($medicine);

        return ApiResponse::noContent(__('Medicine deleted successfully'));
    }
}
