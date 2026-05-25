<?php

namespace App\Domains\Prescriptions\Actions;

use App\Domains\Prescriptions\Models\PrescriptionItem;

class UpdatePrescriptionItemAction
{
    public function execute(PrescriptionItem $prescriptionItem, array $data): PrescriptionItem
    {
        $prescriptionItem->update($data);

        return $prescriptionItem;
    }
}
