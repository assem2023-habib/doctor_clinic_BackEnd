<?php

namespace App\Domains\Prescriptions\Actions;

use App\Domains\Prescriptions\Models\PrescriptionItem;

class DeletePrescriptionItemAction
{
    public function execute(PrescriptionItem $prescriptionItem): void
    {
        $prescriptionItem->delete();
    }
}
