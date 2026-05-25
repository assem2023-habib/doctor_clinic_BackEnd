<?php

namespace App\Domains\Prescriptions\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'prescription_id' => $this->prescription_id,
            'medicine_id' => $this->medicine_id,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
            'instructions' => $this->instructions,
            'medicine' => new MedicineResource($this->whenLoaded('medicine')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
