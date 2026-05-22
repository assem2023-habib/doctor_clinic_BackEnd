<?php

namespace App\Swagger\Controllers\Patients;

use OpenApi\Attributes as OA;

class PatientControllerDoc
{
    #[OA\Get(
        path: '/api/v1/patients',
        summary: 'List all patients',
        tags: ['Patients'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by first name, last name, or email'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Patients retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (staff only)'),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/patients/{patient}',
        summary: 'Get a single patient',
        tags: ['Patients'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'patient', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Patient UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Patient retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (staff only)'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: '/api/v1/patients/{patient}',
        summary: 'Update a patient',
        tags: ['Patients'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'patient', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Patient UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'Patient'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Updated'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'patient@example.com'),
                new OA\Property(property: 'phone', type: 'string', example: '+963912345678'),
                new OA\Property(property: 'address', type: 'string', example: 'Damascus, Syria'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1995-06-15'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Patient updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}

    #[OA\Patch(
        path: '/api/v1/patients/{patient}',
        summary: 'Partially update a patient',
        tags: ['Patients'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'patient', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Patient UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'Patient'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Patient updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updatePartial() {}

    #[OA\Delete(
        path: '/api/v1/patients/{patient}',
        summary: 'Delete a patient',
        tags: ['Patients'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'patient', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Patient UUID'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Patient deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
        ]
    )]
    public function destroy() {}
}
