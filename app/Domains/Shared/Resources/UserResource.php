<?php

namespace App\Domains\Shared\Resources;

use App\Domains\Images\Resources\ImageResource;
use App\Domains\Locations\Resources\CityResource;
use App\Domains\Locations\Resources\CountryResource;
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
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'is_active' => $this->is_active,
            'city_id' => $this->city_id,
            'city' => new CityResource($this->whenLoaded('city')),
            'country' => new CountryResource($this->whenLoaded('country')),
            'image' => new ImageResource($this->image),
        ];
    }
}
