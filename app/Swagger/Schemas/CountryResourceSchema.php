<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CountryResource',
    description: 'Country resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'object', properties: [
            new OA\Property(property: 'ar', type: 'string'),
            new OA\Property(property: 'en', type: 'string'),
        ]),
        new OA\Property(property: 'code', type: 'string', example: 'SA'),
        new OA\Property(property: 'flag', type: 'string', nullable: true),
        new OA\Property(property: 'cities', type: 'array', items: new OA\Items(ref: '#/components/schemas/CityResource')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class CountryResourceSchema
{
}
