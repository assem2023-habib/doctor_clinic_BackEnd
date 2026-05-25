<?php

namespace App\Swagger\Controllers\Prescriptions;

use OpenApi\Attributes as OA;

class PrescriptionItemControllerDoc
{
    #[OA\Get(
        path: '/api/v1/prescriptions/{prescription}/items',
        summary: 'List items for a prescription',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'prescription', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Prescription UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Prescription items retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: '/api/v1/prescriptions/{prescription}/items',
        summary: 'Create a prescription item',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'prescription', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Prescription UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'medicine_id', type: 'string', format: 'uuid', description: 'Medicine UUID'),
                    new OA\Property(property: 'dosage', type: 'string', description: 'e.g. 500mg'),
                    new OA\Property(property: 'frequency', type: 'string', description: 'e.g. 3 times daily'),
                    new OA\Property(property: 'duration', type: 'string', description: 'e.g. 7 days'),
                    new OA\Property(property: 'instructions', type: 'string', nullable: true, description: 'Optional, max 5000'),
                ],
                required: ['medicine_id', 'dosage', 'frequency', 'duration']
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Prescription item created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Get(
        path: '/api/v1/prescription-items/{prescription_item}',
        summary: 'Get a single prescription item',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'prescription_item', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Prescription item UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Prescription item retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Prescription item not found'),
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: '/api/v1/prescription-items/{prescription_item}',
        summary: 'Update a prescription item',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'prescription_item', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Prescription item UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'medicine_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'dosage', type: 'string'),
                    new OA\Property(property: 'frequency', type: 'string'),
                    new OA\Property(property: 'duration', type: 'string'),
                    new OA\Property(property: 'instructions', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Prescription item updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Prescription item not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: '/api/v1/prescription-items/{prescription_item}',
        summary: 'Delete a prescription item',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'prescription_item', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Prescription item UUID'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'No content - deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Prescription item not found'),
        ]
    )]
    public function destroy() {}
}
