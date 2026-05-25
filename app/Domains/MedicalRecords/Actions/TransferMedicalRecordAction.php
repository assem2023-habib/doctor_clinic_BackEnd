<?php

namespace App\Domains\MedicalRecords\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\MedicalRecords\Models\MedicalRecordTransfer;
use App\Models\User;

class TransferMedicalRecordAction
{
    public function execute(MedicalRecord $medicalRecord, Doctor $toDoctor, User $transferredBy, string $role, ?string $reason = null): MedicalRecord
    {
        $fromDoctorId = $medicalRecord->doctor_id;

        $medicalRecord->update([
            'doctor_id' => $toDoctor->id,
        ]);

        MedicalRecordTransfer::create([
            'medical_record_id' => $medicalRecord->id,
            'from_doctor_id' => $fromDoctorId,
            'to_doctor_id' => $toDoctor->id,
            'patient_id' => $medicalRecord->patient_id,
            'transferred_by' => $transferredBy->id,
            'initiated_by_role' => $role,
            'reason' => $reason,
        ]);

        return $medicalRecord->fresh();
    }
}
