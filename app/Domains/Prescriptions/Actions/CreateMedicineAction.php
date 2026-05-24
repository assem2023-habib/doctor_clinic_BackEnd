<?php

namespace App\Domains\Prescriptions\Actions;

use App\Domains\Prescriptions\Models\Medicine;

class CreateMedicineAction
{
    public function execute(array $data): Medicine
    {
        return Medicine::create([
            'name' => [
                'ar' => $data['name_ar'],
                'en' => $data['name_en'],
            ],
            'description' => ($data['description_ar'] ?? false) || ($data['description_en'] ?? false)
                ? [
                    'ar' => $data['description_ar'] ?? '',
                    'en' => $data['description_en'] ?? '',
                ]
                : null,
            'barcode' => $data['barcode'] ?? null,
            'manufacturer' => $data['manufacturer'] ?? null,
        ]);
    }
}
