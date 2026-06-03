<?php

namespace App\Swagger\Controllers\Ratings;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/app-ratings',
    summary: 'List application ratings',
    description: 'Returns paginated application-level ratings (service, center, appointment_system). Can be filtered by type (single or multiple) and rating value.',
    tags: ['App Ratings'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'type', in: 'query', description: 'Filter by type(s). Single (?type=service) or array (?type[]=service&type[]=center)', schema: new OA\Schema(oneOf: [
            new OA\Schema(type: 'string'),
            new OA\Schema(type: 'array', items: new OA\Items(type: 'string')),
        ])),
        new OA\Parameter(name: 'rating', in: 'query', description: 'Filter by rating value (1-5)', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 5)),
        new OA\Parameter(name: 'limit', in: 'query', description: 'Items per page (max 100, default 20)', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'App ratings retrieved successfully', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'status', type: 'integer', example: 200),
            new OA\Property(property: 'message', type: 'string', example: 'App ratings retrieved successfully'),
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AppRatingResource')),
            new OA\Property(property: 'meta', type: 'object'),
        ])),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
class AppRatingControllerDoc {}

#[OA\Schema(
    schema: 'AppRatingResource',
    description: 'Simplified application rating resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '019e1d0f-...'),
        new OA\Property(property: 'type', type: 'string', example: 'service'),
        new OA\Property(property: 'rater', type: 'object', properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Ahmed Ali'),
        ]),
        new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Good service overall'),
        new OA\Property(property: 'rating', type: 'integer', example: 5),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class AppRatingResourceSchema {}
