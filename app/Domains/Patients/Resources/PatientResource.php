<?php

namespace App\Domains\Patients\Resources;

use App\Domains\Shared\Resources\UserResource;

class PatientResource extends UserResource
{
    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
