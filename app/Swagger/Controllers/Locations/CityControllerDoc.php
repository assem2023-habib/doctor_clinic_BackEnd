<?php

namespace App\Swagger\Controllers\Locations;

use OpenApi\Attributes as OA;

class CityControllerDoc
{
    #[OA\Get(
        path: '/api/v1/cities',
        summary: 'List all cities',
        tags: ['Cities'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by name (Arabic or English)'),
            new OA\Parameter(name: 'country_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Filter by country'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cities retrieved successfully'),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/cities/{city}',
        summary: 'Get a single city',
        tags: ['Cities'],
        parameters: [
            new OA\Parameter(name: 'city', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'City retrieved successfully'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: '/api/v1/cities',
        summary: 'Create a new city',
        tags: ['Cities'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name_ar', type: 'string', example: 'الرياض'),
                    new OA\Property(property: 'name_en', type: 'string', example: 'Riyadh'),
                    new OA\Property(property: 'country_id', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'City created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Put(
        path: '/api/v1/cities/{city}',
        summary: 'Update a city',
        tags: ['Cities'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'city', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name_ar', type: 'string'),
                    new OA\Property(property: 'name_en', type: 'string'),
                    new OA\Property(property: 'country_id', type: 'string', format: 'uuid'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'City updated successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: '/api/v1/cities/{city}',
        summary: 'Delete a city',
        tags: ['Cities'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'city', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'City deleted successfully'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy() {}
}
