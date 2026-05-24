<?php

namespace App\Domains\Ratings\Resources;

use App\Domains\Shared\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'rater' => new UserResource($this->whenLoaded('rater')),
            'rateable_id' => $this->rateable_id,
            'rateable_type' => $this->rateable_type,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
