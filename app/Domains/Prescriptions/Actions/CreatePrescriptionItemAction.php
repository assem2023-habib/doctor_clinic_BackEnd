<?php

namespace App\Domains\Prescriptions\Actions;

use App\Domains\Prescriptions\Models\Prescription;
use App\Domains\Prescriptions\Models\PrescriptionItem;

class CreatePrescriptionItemAction
{
    public function execute(Prescription $prescription, array $data): PrescriptionItem
    {
        return $prescription->items()->create([
            'medicine_id' => $data['medicine_id'],
            'dosage' => $data['dosage'],
            'frequency' => $data['frequency'],
            'duration' => $data['duration'],
            'instructions' => $data['instructions'] ?? null,
        ]);
    }
}
