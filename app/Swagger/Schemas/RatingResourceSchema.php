<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RatingResource',
    description: 'Rating resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'type', type: 'string', example: 'user'),
        new OA\Property(property: 'rater', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'last_name', type: 'string'),
            new OA\Property(property: 'email', type: 'string'),
        ]),
        new OA\Property(property: 'rateable_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'rateable_type', type: 'string', nullable: true, example: 'App\\Models\\User'),
        new OA\Property(property: 'rating', type: 'integer', example: 5),
        new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Excellent doctor'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class RatingResourceSchema
{
}
