<?php

namespace App\Domains\Ratings\Actions;

use App\Domains\Ratings\Models\Rating;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteRatingAction
{
    public function execute(Rating $rating, string $userId, bool $isAdmin): void
    {
        if ($rating->rater_id !== $userId && !$isAdmin) {
            throw new AccessDeniedHttpException(__('You can only delete your own ratings'));
        }

        $rating->delete();
    }
}
