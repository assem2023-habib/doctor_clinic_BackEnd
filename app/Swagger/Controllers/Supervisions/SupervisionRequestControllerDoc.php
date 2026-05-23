<?php

namespace App\Swagger\Controllers\Supervisions;

use OpenApi\Attributes as OA;

class SupervisionRequestControllerDoc
{
    #[OA\Post(
        path: '/api/v1/patients/{patient}/supervision-requests',
        summary: 'Create a supervision request (patient only)',
        tags: ['Supervision Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'patient', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Patient UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'doctor_id', type: 'string', format: 'uuid', description: 'Doctor UUID'),
                new OA\Property(property: 'notes', type: 'string', maxLength: 1000, nullable: true, description: 'Request notes'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'Supervision request created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 409, description: 'Patient already has active supervision with this specialization, or pending request to this doctor'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Get(
        path: '/api/v1/patients/{patient}/supervision-requests',
        summary: 'List supervision requests for a patient',
        tags: ['Supervision Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'patient', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Patient UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supervision requests retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function indexPatient() {}

    #[OA\Get(
        path: '/api/v1/doctors/{doctor}/supervision-requests',
        summary: 'List supervision requests for a doctor',
        tags: ['Supervision Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor UUID'),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', default: 'pending'), description: 'Filter by status'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supervision requests retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function indexDoctor() {}

    #[OA\Post(
        path: '/api/v1/supervision-requests/{supervision_request}/cancel',
        summary: 'Cancel a supervision request (patient only)',
        tags: ['Supervision Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supervision_request', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Supervision Request UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supervision request cancelled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (only the patient can cancel)'),
            new OA\Response(response: 422, description: 'Request is not pending'),
        ]
    )]
    public function cancel() {}

    #[OA\Post(
        path: '/api/v1/supervision-requests/{supervision_request}/approve',
        summary: 'Approve a supervision request (doctor only)',
        tags: ['Supervision Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supervision_request', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Supervision Request UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supervision request approved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (only the doctor can approve)'),
            new OA\Response(response: 409, description: 'Patient already has a doctor with this specialization'),
            new OA\Response(response: 422, description: 'Request is not pending'),
        ]
    )]
    public function approve() {}

    #[OA\Post(
        path: '/api/v1/supervision-requests/{supervision_request}/reject',
        summary: 'Reject a supervision request (doctor only)',
        tags: ['Supervision Requests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supervision_request', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Supervision Request UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supervision request rejected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (only the doctor can reject)'),
            new OA\Response(response: 422, description: 'Request is not pending'),
        ]
    )]
    public function reject() {}
}
