<?php

namespace App\Domains\Ratings\Actions;

use App\Domains\Ratings\DTOs\RatingData;
use App\Domains\Ratings\Models\Rating;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CreateRatingAction
{
    public function execute(RatingData $data): Rating
    {
        $exists = Rating::where('rater_id', $data->raterId)
            ->where('type', $data->type)
            ->where('rateable_id', $data->rateableId)
            ->where('rateable_type', $data->rateableType)
            ->exists();

        if ($exists) {
            throw new ConflictHttpException(__('You have already rated this entity'));
        }

        return Rating::create($data->toArray());
    }
}
