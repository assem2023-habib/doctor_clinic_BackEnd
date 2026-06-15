<?php

namespace App\Domains\Ratings\Actions;

use App\Domains\Ratings\DTOs\RatingData;
use App\Domains\Ratings\Models\Rating;
use App\Domains\Shared\Exceptions\ApiServiceException;
use App\Enums\HttpStatusEnum;

class CreateRatingAction
{
    private const ERROR_CODE = 'RATING_ALREADY_EXISTS';

    private const MESSAGES = [
        'user' => 'You have already rated this doctor',
        'service' => 'You have already rated this service',
        'center' => 'You have already rated this center',
        'appointment_system' => 'You have already rated the appointment system',
    ];

    public function execute(RatingData $data): Rating
    {
        $exists = Rating::where('rater_id', $data->raterId)
            ->where('type', $data->type)
            ->where('rateable_id', $data->rateableId)
            ->where('rateable_type', $data->rateableType)
            ->exists();

        if ($exists) {
            throw new ApiServiceException(
                errorCode: self::ERROR_CODE,
                message: __(self::MESSAGES[$data->type] ?? 'You have already rated this entity'),
                status: HttpStatusEnum::Conflict,
            );
        }

        return Rating::create($data->toArray());
    }
}
