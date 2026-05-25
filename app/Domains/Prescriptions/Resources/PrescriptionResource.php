<?php

namespace App\Domains\Prescriptions\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->medical_record_id,
            'prescription_date' => $this->prescription_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => PrescriptionItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
