<?php

namespace App\Domains\Shared\Resources;

use App\Domains\Images\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'role' => $this->role?->value,
            'is_active' => $this->is_active,
            'image' => new ImageResource($this->image),
        ];
    }
}
