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
            new OA\Parameter(name: 'gender', in: 'query', schema: new OA\Schema(type: 'string', enum: ['male', 'female']), description: 'Filter by gender'),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Filter by birthday from (YYYY-MM-DD)'),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Filter by birthday to (YYYY-MM-DD)'),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filter by active status'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Patients retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (staff/doctor only)'),
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
            new OA\Response(response: 403, description: 'Forbidden (staff/doctor only)'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: '/api/v1/patients',
        summary: 'Create a new patient (admin only)',
        tags: ['Patients'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'Ahmed'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Ali'),
                new OA\Property(property: 'username', type: 'string', example: 'ahmedali'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'patient@example.com'),
                new OA\Property(property: 'phone', type: 'string', example: '+963912345678'),
                new OA\Property(property: 'address', type: 'string', example: 'Damascus, Syria'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1995-06-15'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'Patient created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

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
