<?php

namespace App\Domains\Prescriptions\Actions;

use App\Domains\Prescriptions\Models\Prescription;

class UpdatePrescriptionAction
{
    public function execute(Prescription $prescription, array $data): Prescription
    {
        $prescription->update($data);

        return $prescription;
    }
}
