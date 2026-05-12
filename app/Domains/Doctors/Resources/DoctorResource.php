<?php

namespace App\Domains\Doctors\Resources;

use App\Domains\Shared\Resources\UserResource;

class DoctorResource extends UserResource
{
    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
