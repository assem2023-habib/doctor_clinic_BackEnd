<?php

namespace App\Domains\Receptionists\Resources;

use App\Domains\Shared\Resources\UserResource;

class ReceptionistResource extends UserResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'profile' => [
                'id' => $this->receptionist?->id,
                'shift_start' => $this->receptionist?->shift_start,
                'shift_end' => $this->receptionist?->shift_end,
            ],
        ]);
    }
}
