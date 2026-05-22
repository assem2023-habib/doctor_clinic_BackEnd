<?php

namespace App\Swagger\Controllers\Supervisions;

use OpenApi\Attributes as OA;

class SupervisionControllerDoc
{
    #[OA\Get(
        path: '/api/v1/doctors/{doctor}/patients',
        summary: 'List patients assigned to a doctor',
        tags: ['Supervisions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor UUID'),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by name or email'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Patients retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function doctorPatients() {}

    #[OA\Get(
        path: '/api/v1/patients/{patient}/doctors',
        summary: 'List doctors assigned to a patient',
        tags: ['Supervisions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'patient', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Patient UUID'),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by name or email'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Doctors retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function patientDoctors() {}

    #[OA\Post(
        path: '/api/v1/doctors/{doctor}/patients',
        summary: 'Assign a patient to a doctor',
        description: 'Staff (admin/receptionist) only.',
        tags: ['Supervisions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'patient_id', type: 'string', format: 'uuid', description: 'Patient UUID'),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 1000, nullable: true, description: 'Assignment notes'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Patient assigned to doctor successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (staff only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function assign() {}

    #[OA\Delete(
        path: '/api/v1/doctors/{doctor}/patients/{patient}',
        summary: 'Remove a patient from a doctor',
        description: 'Staff (admin/receptionist) only.',
        tags: ['Supervisions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor UUID'),
            new OA\Parameter(name: 'patient', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Patient UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Patient removed from doctor successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (staff only)'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function remove() {}
}
