<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ImageResource',
    description: 'Image resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'url', type: 'string', format: 'uri', description: 'Full URL to the image'),
        new OA\Property(property: 'type', type: 'string', enum: ['user', 'country', 'specialization']),
        new OA\Property(property: 'imageable_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class ImageResourceSchema
{
}
