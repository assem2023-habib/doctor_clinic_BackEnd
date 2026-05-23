<?php

namespace App\Domains\Supervisions\Resources;

use App\Domains\Images\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SupervisionPatientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'gender' => $this->gender?->value,
            'birthday_date' => $this->birthday_date?->format('Y-m-d'),
            'roles' => $this->whenLoaded('roles')?->pluck('slug'),
            'is_active' => $this->is_active,
            'image' => new ImageResource($this->image),
            'supervision' => [
                'assigned_by' => $this->pivot?->assigned_by,
                'notes' => $this->pivot?->notes,
                'assigned_at' => $this->pivot?->created_at?->toIso8601String(),
                'supervision_status' => $this->pivot?->supervision_status,
                'supervision_start' => $this->pivot?->supervision_start?->toIso8601String(),
                'supervision_end' => $this->pivot?->supervision_end?->toIso8601String(),
            ],
        ];
    }
}
