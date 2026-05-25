<?php

namespace App\Domains\Prescriptions\Actions;

use App\Domains\MedicalRecords\Models\MedicalRecord;
use App\Domains\Prescriptions\Enums\PrescriptionStatusEnum;
use App\Domains\Prescriptions\Models\Prescription;

class CreatePrescriptionAction
{
    public function execute(MedicalRecord $medicalRecord, array $data): Prescription
    {
        $prescription = $medicalRecord->prescriptions()->create([
            'prescription_date' => $data['prescription_date'] ?? now(),
            'status' => $data['status'] ?? PrescriptionStatusEnum::Active,
            'notes' => $data['notes'] ?? null,
        ]);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $prescription->items()->create([
                    'medicine_id' => $item['medicine_id'],
                    'dosage' => $item['dosage'],
                    'frequency' => $item['frequency'],
                    'duration' => $item['duration'],
                    'instructions' => $item['instructions'] ?? null,
                ]);
            }
        }

        return $prescription->load('items');
    }
}
