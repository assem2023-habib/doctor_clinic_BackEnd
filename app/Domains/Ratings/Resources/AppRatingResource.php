<?php

namespace App\Domains\Ratings\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppRatingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'rater' => [
                'name' => $this->rater->first_name . ' ' . $this->rater->last_name,
            ],
            'comment' => $this->comment,
            'rating' => $this->rating,
            'created_at' => $this->created_at,
        ];
    }
}
