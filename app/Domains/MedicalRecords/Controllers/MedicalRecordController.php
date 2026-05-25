<?php

namespace App\Domains\MedicalRecords\Controllers;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\MedicalRecords\Actions\TransferMedicalRecordAction;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\MedicalRecords\Requests\TransferMedicalRecordRequest;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class MedicalRecordController
{
    public function __construct(
        private readonly TransferMedicalRecordAction $transferAction,
    ) {}

    public function transfer(TransferMedicalRecordRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $user = $request->user();

        $toDoctor = Doctor::findOrFail($request->doctor_id);

        $transferred = $this->transferAction->execute(
            medicalRecord: $medicalRecord,
            toDoctor: $toDoctor,
            transferredBy: $user,
            role: $user->hasRole('admin') ? 'admin' : 'receptionist',
            reason: $request->reason,
        );

        return ApiResponse::success(
            $transferred,
            __('Medical record transferred successfully')
        );
    }
}
