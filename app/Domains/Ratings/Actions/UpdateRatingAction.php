<?php

namespace App\Domains\Ratings\Actions;

use App\Domains\Ratings\DTOs\RatingData;
use App\Domains\Ratings\Models\Rating;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UpdateRatingAction
{
    public function execute(Rating $rating, RatingData $data, string $userId): Rating
    {
        if ($rating->rater_id !== $userId) {
            throw new AccessDeniedHttpException(__('You can only update your own ratings'));
        }

        $rating->update($data->toUpdateArray());

        return $rating->fresh();
    }
}
