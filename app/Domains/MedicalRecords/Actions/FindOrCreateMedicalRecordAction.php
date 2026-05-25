<?php

namespace App\Domains\MedicalRecords\Actions;

use App\Domains\Doctors\Models\Doctor;
use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Patients\Models\Patient;

class FindOrCreateMedicalRecordAction
{
    public function execute(string $patientId, string $doctorId): MedicalRecord
    {
        $doctor = Doctor::findOrFail($doctorId);

        $existing = MedicalRecord::where('patient_id', $patientId)
            ->whereHas('doctor', fn ($q) => $q->where('specialization_id', $doctor->specialization_id))
            ->first();

        if ($existing) {
            return $existing;
        }

        return MedicalRecord::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'diagnosis' => '',
            'created_at' => now(),
        ]);
    }
}
