<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CityResource',
    description: 'City resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'object', properties: [
            new OA\Property(property: 'ar', type: 'string'),
            new OA\Property(property: 'en', type: 'string'),
        ]),
        new OA\Property(property: 'country_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class CityResourceSchema
{
}
