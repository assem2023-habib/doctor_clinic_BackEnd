<?php

namespace App\Swagger\Controllers\Receptionists;

use OpenApi\Attributes as OA;

class ReceptionistControllerDoc
{
    #[OA\Get(
        path: '/api/v1/receptionists',
        summary: 'List all receptionists',
        tags: ['Receptionists'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by first name, last name, or email'),
            new OA\Parameter(name: 'gender', in: 'query', schema: new OA\Schema(type: 'string', enum: ['male', 'female']), description: 'Filter by gender'),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Birthday date range start'),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Birthday date range end'),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filter by active status'),
            new OA\Parameter(name: 'shift_start_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'time', example: '08:00'), description: 'Shift start time from'),
            new OA\Parameter(name: 'shift_start_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'time', example: '12:00'), description: 'Shift start time to'),
            new OA\Parameter(name: 'shift_end_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'time', example: '16:00'), description: 'Shift end time from'),
            new OA\Parameter(name: 'shift_end_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'time', example: '20:00'), description: 'Shift end time to'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Receptionists retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Receptionists retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ReceptionistResource')
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(
                                    property: 'pagination',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer'),
                                        new OA\Property(property: 'last_page', type: 'integer'),
                                        new OA\Property(property: 'per_page', type: 'integer'),
                                        new OA\Property(property: 'total', type: 'integer'),
                                        new OA\Property(property: 'from', type: 'integer'),
                                        new OA\Property(property: 'to', type: 'integer'),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/receptionists/{receptionist}',
        summary: 'Get a single receptionist',
        tags: ['Receptionists'],
        parameters: [
            new OA\Parameter(
                name: 'receptionist',
                in: 'path',
                required: true,
                description: 'Receptionist user UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Receptionist retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Receptionist retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/ReceptionistResource'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Receptionist not found'
            ),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: '/api/v1/receptionists',
        summary: 'Create a new receptionist',
        description: 'Admin-only. Creates a receptionist with is_active=true immediately.',
        tags: ['Receptionists'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'first_name', type: 'string', example: 'Layla'),
                        new OA\Property(property: 'last_name', type: 'string', example: 'Hassan'),
                        new OA\Property(property: 'username', type: 'string', example: 'laylah'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'receptionist@example.com'),
                        new OA\Property(property: 'phone', type: 'string', example: '+963912345680'),
                        new OA\Property(property: 'address', type: 'string', example: 'Homs, Syria'),
                        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'female'),
                        new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1998-11-05'),
                        new OA\Property(property: 'shift_start', type: 'string', format: 'time', example: '09:00'),
                        new OA\Property(property: 'shift_end', type: 'string', format: 'time', example: '17:00'),
                        new OA\Property(property: 'password', type: 'string', format: 'password'),
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Profile image'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Receptionist created successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 201),
                new OA\Property(property: 'message', type: 'string', example: 'Receptionist created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ReceptionistResource'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Put(
        path: '/api/v1/receptionists/{receptionist}',
        summary: 'Update a receptionist',
        tags: ['Receptionists'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'receptionist', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Receptionist user UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'Layla'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Hassan Updated'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'receptionist@example.com'),
                new OA\Property(property: 'phone', type: 'string', example: '+963912345680'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'female'),
                new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1998-11-05'),
                new OA\Property(property: 'shift_start', type: 'string', format: 'time', example: '09:00'),
                new OA\Property(property: 'shift_end', type: 'string', format: 'time', example: '17:00'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Receptionist updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}

    #[OA\Patch(
        path: '/api/v1/receptionists/{receptionist}',
        summary: 'Partially update a receptionist',
        tags: ['Receptionists'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'receptionist', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Receptionist user UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'Layla'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Receptionist updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updatePartial() {}

    #[OA\Delete(
        path: '/api/v1/receptionists/{receptionist}',
        summary: 'Delete a receptionist',
        tags: ['Receptionists'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'receptionist', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Receptionist user UUID'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Receptionist deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 409, description: 'Receptionist has active appointments'),
        ]
    )]
    public function destroy() {}

    #[OA\Put(
        path: '/api/v1/receptionists/{receptionist}/activate-account',
        summary: 'Activate a receptionist account',
        description: 'Admin-only. Sets is_active=true for the receptionist user.',
        tags: ['Receptionists'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'receptionist', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Receptionist user UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Account activated successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'Account activated successfully.'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ReceptionistResource'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
        ]
    )]
    public function activateAccount() {}
}
