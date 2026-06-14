<?php

namespace App\Swagger\Controllers\Ratings;

use OpenApi\Attributes as OA;

class RatingControllerDoc
{
    #[OA\Get(
        path: '/api/v1/ratings',
        summary: 'List all ratings',
        tags: ['Ratings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'type', in: 'query', description: 'Filter by type(s). Single (?type=service) or array (?type[]=service&type[]=center&type[]=appointment_system)', schema: new OA\Schema(oneOf: [
                new OA\Schema(type: 'string'),
                new OA\Schema(type: 'array', items: new OA\Items(type: 'string')),
            ])),
            new OA\Parameter(name: 'rater_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Filter by rater ID'),
            new OA\Parameter(name: 'rateable_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Filter by rateable ID'),
            new OA\Parameter(name: 'rateable_type', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filter by rateable type'),
            new OA\Parameter(name: 'rating', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filter by rating value'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ratings retrieved successfully', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RatingResource')),
                    new OA\Property(property: 'meta', type: 'object'),
                ]
            )),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/ratings/{rating}',
        summary: 'Get a single rating',
        tags: ['Ratings'],
        parameters: [
            new OA\Parameter(name: 'rating', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Rating retrieved successfully'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: '/api/v1/ratings',
        summary: 'Create a new rating',
        tags: ['Ratings'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'type', type: 'string', example: 'user', description: 'user, service, center, appointment_system'),
                new OA\Property(property: 'rateable_id', type: 'string', format: 'uuid', nullable: true, example: '019e1d0f-...', description: 'Required when type is user'),
                new OA\Property(property: 'rateable_type', type: 'string', nullable: true, example: 'App\\Models\\User', description: 'Required when type is user'),
                new OA\Property(property: 'rating', type: 'integer', example: 5, description: '1-5'),
                new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Excellent doctor'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Rating created successfully'),
            new OA\Response(response: 409, description: 'Already rated this entity'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Put(
        path: '/api/v1/ratings/{rating}',
        summary: 'Update a rating',
        tags: ['Ratings'],
        parameters: [
            new OA\Parameter(name: 'rating', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'rating', type: 'integer', example: 4, description: '1-5'),
                new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Updated review'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Rating updated successfully'),
            new OA\Response(response: 403, description: 'Forbidden (not your rating)'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: '/api/v1/ratings/{rating}',
        summary: 'Delete a rating',
        tags: ['Ratings'],
        parameters: [
            new OA\Parameter(name: 'rating', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Rating deleted successfully'),
            new OA\Response(response: 403, description: 'Forbidden (not your rating)'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy() {}
}
