<?php

namespace App\Domains\Images\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'type' => $this->imageable_type,
            'imageable_id' => $this->imageable_id,
            'created_at' => $this->created_at,
        ];
    }
}
