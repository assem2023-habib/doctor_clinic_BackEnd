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
        description: 'Staff (admin/receptionist) only. Fails with 409 if patient already has a doctor with the same specialization.',
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
                    new OA\Property(property: 'supervision_status', type: 'string', enum: ['active', 'suspended'], default: 'active', description: 'Supervision status'),
                    new OA\Property(property: 'supervision_start', type: 'string', format: 'date', nullable: true, description: 'Supervision start date'),
                    new OA\Property(property: 'supervision_end', type: 'string', format: 'date', nullable: true, description: 'Supervision end date'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Patient assigned to doctor successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (staff only)'),
            new OA\Response(response: 409, description: 'Patient already has a doctor with this specialization'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function assign() {}

    #[OA\Post(
        path: '/api/v1/doctors/{doctor}/patients/bulk',
        summary: 'Bulk assign patients to a doctor',
        description: 'Staff (admin/receptionist) only. Returns assigned, skipped (due to specialization conflict), and error lists.',
        tags: ['Supervisions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'patient_ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid'), description: 'Array of patient UUIDs'),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 1000, nullable: true, description: 'Assignment notes'),
                    new OA\Property(property: 'supervision_status', type: 'string', enum: ['active', 'suspended'], default: 'active', description: 'Supervision status'),
                    new OA\Property(property: 'supervision_start', type: 'string', format: 'date', nullable: true, description: 'Supervision start date'),
                    new OA\Property(property: 'supervision_end', type: 'string', format: 'date', nullable: true, description: 'Supervision end date'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk assign completed'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (staff only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkAssign() {}

    #[OA\Get(
        path: '/api/v1/patients/{patient}/available-doctors',
        summary: 'List available doctors for a patient (not yet assigned)',
        tags: ['Supervisions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'patient', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Patient UUID'),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by name or email'),
            new OA\Parameter(name: 'specialization', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filter by specialization'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Available doctors retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function availableDoctors() {}

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
