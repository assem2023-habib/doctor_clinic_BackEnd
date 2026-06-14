<?php

namespace App\Domains\Supervisions\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SupervisionRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient?->user_id,
            'doctor_id' => $this->doctor?->user_id,
            'status' => $this->status?->value,
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
