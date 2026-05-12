<?php

namespace App\Domains\Doctors\Resources;

use App\Domains\Shared\Resources\UserResource;

class DoctorResource extends UserResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'doctor_id' => $this->doctor?->id,
        ]);
    }
}
