<?php

namespace App\Domains\Ratings\DTOs;

use App\Domains\Ratings\Requests\StoreRatingRequest;
use App\Domains\Ratings\Requests\UpdateRatingRequest;

class RatingData
{
    private function __construct(
        public readonly string $type,
        public readonly ?string $rateableId,
        public readonly ?string $rateableType,
        public readonly int $rating,
        public readonly ?string $comment,
        public readonly ?string $raterId,
    ) {}

    public static function fromStoreRequest(StoreRatingRequest $request): self
    {
        return new self(
            type: $request->type,
            rateableId: $request->rateable_id,
            rateableType: $request->rateable_type,
            rating: (int) $request->rating,
            comment: $request->comment,
            raterId: $request->user()->id,
        );
    }

    public static function fromUpdateRequest(UpdateRatingRequest $request): self
    {
        return new self(
            type: '',
            rateableId: null,
            rateableType: null,
            rating: (int) $request->rating,
            comment: $request->comment,
            raterId: null,
        );
    }

    public function toArray(): array
    {
        return [
            'rater_id' => $this->raterId,
            'type' => $this->type,
            'rateable_id' => $this->rateableId,
            'rateable_type' => $this->rateableType,
            'rating' => $this->rating,
            'comment' => $this->comment,
        ];
    }

    public function toUpdateArray(): array
    {
        return [
            'rating' => $this->rating,
            'comment' => $this->comment,
        ];
    }
}
