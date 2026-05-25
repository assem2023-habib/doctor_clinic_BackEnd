<?php

namespace App\Domains\Supervisions\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\MedicalRecords\Models\MedicalRecordTransfer;
use App\Domains\Patients\Models\Patient;
use App\Models\User;

class RemovePatientFromDoctorAction
{
    public function execute(Doctor $doctor, Patient $patient, ?User $transferredBy = null, string $role = 'system'): void
    {
        $record = MedicalRecord::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->whereHas('doctor', fn($q) => $q->where('specialization_id', $doctor->specialization_id))
            ->first();

        if ($record && $transferredBy) {
            MedicalRecordTransfer::create([
                'medical_record_id' => $record->id,
                'from_doctor_id' => $doctor->id,
                'to_doctor_id' => null,
                'patient_id' => $patient->id,
                'transferred_by' => $transferredBy->id,
                'initiated_by_role' => $role,
            ]);
        }

        $doctor->patients()->detach($patient->id);
    }
}
