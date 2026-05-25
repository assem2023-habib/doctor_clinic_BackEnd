<?php

namespace App\Swagger\Controllers\Prescriptions;

use OpenApi\Attributes as OA;

class PrescriptionControllerDoc
{
    #[OA\Get(
        path: '/api/v1/medical-records/{medical_record}/prescriptions',
        summary: 'List prescriptions for a medical record',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'medical_record', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Medical record UUID'),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Prescriptions retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: '/api/v1/medical-records/{medical_record}/prescriptions',
        summary: 'Create a prescription with optional inline items',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'medical_record', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Medical record UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'prescription_date', type: 'string', format: 'date', nullable: true, description: 'Optional, defaults to today'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'archived', 'expired'], description: 'Optional, defaults to active'),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, description: 'Optional notes, max 5000'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        nullable: true,
                        description: 'Optional items to create inline (max 50)',
                        items: new OA\Items(
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
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Prescription created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Get(
        path: '/api/v1/prescriptions/{prescription}',
        summary: 'Get a single prescription',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'prescription', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Prescription UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Prescription retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Prescription not found'),
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: '/api/v1/prescriptions/{prescription}',
        summary: 'Update a prescription',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'prescription', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Prescription UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'prescription_date', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'archived', 'expired']),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Prescription updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Prescription not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: '/api/v1/prescriptions/{prescription}',
        summary: 'Delete a prescription',
        description: 'Cannot delete if archived/expired or older than 2 days. Cascades to delete all items.',
        tags: ['Prescriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'prescription', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Prescription UUID'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'No content - deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Prescription not found'),
            new OA\Response(response: 422, description: 'Cannot delete an archived or expired prescription, or a prescription older than 2 days'),
        ]
    )]
    public function destroy() {}
}
