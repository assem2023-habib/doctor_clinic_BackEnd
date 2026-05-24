<?php

namespace App\Domains\Prescriptions\Actions;

use App\Domains\Prescriptions\Models\Medicine;

class DeleteMedicineAction
{
    public function execute(Medicine $medicine): void
    {
        $medicine->delete();
    }
}
