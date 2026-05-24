<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SpecializationResource',
    description: 'Specialization resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'slug', type: 'string', example: 'cardiology'),
        new OA\Property(property: 'name', type: 'object', properties: [
            new OA\Property(property: 'ar', type: 'string', example: 'طب القلب'),
            new OA\Property(property: 'en', type: 'string', example: 'Cardiology'),
        ]),
        new OA\Property(property: 'description', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'ar', type: 'string'),
            new OA\Property(property: 'en', type: 'string'),
        ]),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'doctors_count', type: 'integer', example: 5, nullable: true),
        new OA\Property(property: 'image', ref: '#/components/schemas/ImageResource', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class SpecializationResourceSchema
{
}
