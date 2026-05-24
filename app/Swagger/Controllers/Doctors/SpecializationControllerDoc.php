<?php

namespace App\Swagger\Controllers\Doctors;

use OpenApi\Attributes as OA;

class SpecializationControllerDoc
{
    #[OA\Get(
        path: '/api/v1/specializations',
        summary: 'List all specializations',
        tags: ['Specializations'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by name (Arabic or English)'),
            new OA\Parameter(name: 'slug', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filter by slug'),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filter by active status'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Specializations retrieved successfully', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SpecializationResource')),
                    new OA\Property(property: 'meta', type: 'object'),
                ]
            )),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/specializations/{specialization}',
        summary: 'Get a single specialization',
        tags: ['Specializations'],
        parameters: [
            new OA\Parameter(name: 'specialization', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Specialization retrieved successfully'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: '/api/v1/specializations',
        summary: 'Create a new specialization',
        tags: ['Specializations'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name_ar', type: 'string', example: 'طب القلب'),
                new OA\Property(property: 'name_en', type: 'string', example: 'Cardiology'),
                new OA\Property(property: 'description_ar', type: 'string', nullable: true, example: 'متخصص بأمراض القلب'),
                new OA\Property(property: 'description_en', type: 'string', nullable: true, example: 'Heart disease specialist'),
                new OA\Property(property: 'is_active', type: 'boolean', nullable: true, example: true),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Specialization created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Put(
        path: '/api/v1/specializations/{specialization}',
        summary: 'Update a specialization',
        tags: ['Specializations'],
        parameters: [
            new OA\Parameter(name: 'specialization', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name_ar', type: 'string'),
                new OA\Property(property: 'name_en', type: 'string'),
                new OA\Property(property: 'description_ar', type: 'string', nullable: true),
                new OA\Property(property: 'description_en', type: 'string', nullable: true),
                new OA\Property(property: 'is_active', type: 'boolean', nullable: true),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Specialization updated successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: '/api/v1/specializations/{specialization}',
        summary: 'Delete a specialization',
        tags: ['Specializations'],
        parameters: [
            new OA\Parameter(name: 'specialization', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Specialization deleted successfully'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 409, description: 'Cannot delete specialization with associated doctors'),
        ]
    )]
    public function destroy() {}
}
