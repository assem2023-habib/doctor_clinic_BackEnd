<?php

namespace App\Domains\Doctors\Resources;

use App\Domains\Images\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecializationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'doctors_count' => $this->whenCounted('doctors'),
            'image' => $this->whenLoaded('image', fn() => new ImageResource($this->image)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
