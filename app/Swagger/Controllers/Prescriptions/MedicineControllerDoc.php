<?php

namespace App\Swagger\Controllers\Prescriptions;

use OpenApi\Attributes as OA;

class MedicineControllerDoc
{
    #[OA\Get(
        path: '/api/v1/medicines',
        summary: 'List all medicines',
        tags: ['Medicines'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by name, description, barcode, or manufacturer'),
            new OA\Parameter(name: 'manufacturer', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filter by manufacturer'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Medicines retrieved successfully', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MedicineResource')),
                    new OA\Property(property: 'meta', type: 'object'),
                ]
            )),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/medicines/{medicine}',
        summary: 'Get a single medicine',
        tags: ['Medicines'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'medicine', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Medicine retrieved successfully'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: '/api/v1/medicines',
        summary: 'Create a new medicine',
        tags: ['Medicines'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name_ar', type: 'string', example: 'باراسيتامول'),
                new OA\Property(property: 'name_en', type: 'string', example: 'Paracetamol'),
                new OA\Property(property: 'description_ar', type: 'string', nullable: true, example: 'مسكن ألم وخافض حرارة'),
                new OA\Property(property: 'description_en', type: 'string', nullable: true, example: 'Pain reliever and fever reducer'),
                new OA\Property(property: 'barcode', type: 'string', nullable: true, example: '6281001001001'),
                new OA\Property(property: 'manufacturer', type: 'string', nullable: true, example: 'GlaxoSmithKline'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Medicine created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (requires medicines.create permission)'),
            new OA\Response(response: 422, description: 'Validation error or daily limit exceeded (patients: max 15/day)'),
        ]
    )]
    public function store() {}

    #[OA\Put(
        path: '/api/v1/medicines/{medicine}',
        summary: 'Update a medicine',
        tags: ['Medicines'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'medicine', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name_ar', type: 'string'),
                new OA\Property(property: 'name_en', type: 'string'),
                new OA\Property(property: 'description_ar', type: 'string', nullable: true),
                new OA\Property(property: 'description_en', type: 'string', nullable: true),
                new OA\Property(property: 'barcode', type: 'string', nullable: true),
                new OA\Property(property: 'manufacturer', type: 'string', nullable: true),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Medicine updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (staff:doctor only)'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: '/api/v1/medicines/{medicine}',
        summary: 'Delete a medicine',
        tags: ['Medicines'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'medicine', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Medicine deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (staff:doctor only)'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy() {}
}
