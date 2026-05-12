<?php

namespace App\Domains\Patients\Resources;

use App\Domains\Shared\Resources\UserResource;

class PatientResource extends UserResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'patient_id' => $this->patient?->id,
        ]);
    }
}
