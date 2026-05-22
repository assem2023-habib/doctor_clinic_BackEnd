<?php

namespace App\Swagger\Controllers\Appointments;

use OpenApi\Attributes as OA;

class AppointmentControllerDoc
{
    #[OA\Get(
        path: '/api/v1/doctors/{doctor}/available-slots',
        summary: 'Get available slots for a doctor',
        description: 'Public endpoint. No authentication required.',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor UUID'),
            new OA\Parameter(name: 'date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date'), description: 'Date (Y-m-d)'),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20), description: 'Items per page'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Available slots retrieved successfully'),
        ]
    )]
    public function availableSlots() {}

    #[OA\Get(
        path: '/api/v1/appointments',
        summary: 'List appointments (role-scoped)',
        description: 'Patients see own appointments, doctors see own, admins/receptionists see all.',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filter by status'),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Filter by date (Y-m-d)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointments retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/appointments/{appointment}',
        summary: 'Get a single appointment',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Appointment UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment retrieved successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AppointmentResource'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: '/api/v1/appointments',
        summary: 'Request a new appointment',
        description: 'Patient only.',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'doctor_id', type: 'string', format: 'uuid', description: 'Doctor UUID'),
                    new OA\Property(property: 'preferred_date', type: 'string', format: 'date', nullable: true, description: 'Preferred date (Y-m-d)'),
                    new OA\Property(property: 'reason', type: 'string', nullable: true, maxLength: 2000, description: 'Reason for appointment'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Appointment requested successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Only patients can request appointments'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Post(
        path: '/api/v1/appointments/{appointment}/set-time',
        summary: 'Set appointment date and time',
        description: 'Staff or doctor only. Triggers auto-confirm job.',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Appointment UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'appointment_date', type: 'string', format: 'date', description: 'Appointment date (Y-m-d)'),
                    new OA\Property(property: 'start_time', type: 'string', format: 'time', example: '10:00', description: 'Start time (H:i)'),
                    new OA\Property(property: 'end_time', type: 'string', format: 'time', example: '11:00', description: 'End time (H:i)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Appointment time set successfully'),
            new OA\Response(response: 400, description: 'Appointment is not in requested status'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function setTime() {}

    #[OA\Post(
        path: '/api/v1/appointments/{appointment}/respond',
        summary: 'Patient accepts or rejects appointment time',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Appointment UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'response', type: 'string', enum: ['accepted', 'rejected'], description: 'Patient response'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Appointment accepted/rejected'),
            new OA\Response(response: 400, description: 'Appointment is not in set status'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (not the appointment patient)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function respond() {}

    #[OA\Post(
        path: '/api/v1/appointments/{appointment}/start',
        summary: 'Start an accepted appointment',
        description: 'Transition from accepted to in_progress. Staff/doctor only.',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Appointment UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment started successfully'),
            new OA\Response(response: 400, description: 'Appointment is not in accepted status'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function start() {}

    #[OA\Post(
        path: '/api/v1/appointments/{appointment}/complete',
        summary: 'Complete an in-progress appointment',
        description: 'Transition from in_progress to completed. Staff/doctor only.',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Appointment UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment completed successfully'),
            new OA\Response(response: 400, description: 'Appointment is not in in_progress status'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function complete() {}

    #[OA\Post(
        path: '/api/v1/appointments/{appointment}/cancel',
        summary: 'Cancel an appointment',
        description: 'Staff/doctor only. Cancels from any status except completed.',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Appointment UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment cancelled successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function cancel() {}

    #[OA\Post(
        path: '/api/v1/appointments/{appointment}/suggest-alternative',
        summary: 'Suggest an alternative time',
        description: 'Staff/doctor only. Appends message to notes. Status stays requested.',
        tags: ['Appointments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Appointment UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', maxLength: 2000, description: 'Alternative suggestion message'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Alternative suggested successfully'),
            new OA\Response(response: 400, description: 'Can only suggest alternatives for requested appointments'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function suggestAlternative() {}
}
