<?php

namespace App\Swagger\Controllers\Appointments;

use OpenApi\Attributes as OA;

class AppointmentControllerDoc
{
    #[OA\Get(
        path: '/api/v1/doctors/{doctor}/available-slots',
        summary: 'Get available slots for a doctor',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Available slots retrieved successfully'),
        ]
    )]
    public function availableSlots() {}

    #[OA\Get(
        path: '/api/v1/appointments',
        summary: 'List appointments (role-scoped)',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointments retrieved successfully'),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/appointments/{id}',
        summary: 'Get a single appointment',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment retrieved successfully'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: '/api/v1/appointments',
        summary: 'Request a new appointment',
        tags: ['Appointments'],
        responses: [
            new OA\Response(response: 201, description: 'Appointment requested successfully'),
            new OA\Response(response: 403, description: 'Only patients can request appointments'),
        ]
    )]
    public function store() {}

    #[OA\Post(
        path: '/api/v1/appointments/{id}/set-time',
        summary: 'Set appointment time',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment time set successfully'),
            new OA\Response(response: 400, description: 'Appointment is not in requested status'),
        ]
    )]
    public function setTime() {}

    #[OA\Post(
        path: '/api/v1/appointments/{id}/respond',
        summary: 'Patient accepts or rejects appointment time',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment accepted/rejected'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function respond() {}

    #[OA\Post(
        path: '/api/v1/appointments/{id}/start',
        summary: 'Start an accepted appointment',
        description: 'Transition appointment from accepted to in_progress. Staff/doctor only.',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment started successfully'),
            new OA\Response(response: 400, description: 'Appointment is not in accepted status'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function start() {}

    #[OA\Post(
        path: '/api/v1/appointments/{id}/complete',
        summary: 'Complete an in-progress appointment',
        description: 'Transition appointment from in_progress to completed. Staff/doctor only.',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment completed successfully'),
            new OA\Response(response: 400, description: 'Appointment is not in in_progress status'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function complete() {}

    #[OA\Post(
        path: '/api/v1/appointments/{id}/cancel',
        summary: 'Cancel an appointment',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Appointment cancelled successfully'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function cancel() {}

    #[OA\Post(
        path: '/api/v1/appointments/{id}/suggest-alternative',
        summary: 'Suggest an alternative time',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Alternative suggested successfully'),
            new OA\Response(response: 400, description: 'Can only suggest alternatives for requested appointments'),
        ]
    )]
    public function suggestAlternative() {}
}
