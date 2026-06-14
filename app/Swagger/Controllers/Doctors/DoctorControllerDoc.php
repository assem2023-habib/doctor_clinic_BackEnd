<?php

namespace App\Swagger\Controllers\Doctors;

use OpenApi\Attributes as OA;

class DoctorControllerDoc
{
    #[OA\Get(
        path: '/api/v1/doctors',
        summary: 'List all doctors',
        tags: ['Doctors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by first name, last name, or email'),
            new OA\Parameter(name: 'specialization_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Filter by specialization ID'),
            new OA\Parameter(name: 'experience_from', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 0), description: 'Minimum experience in months'),
            new OA\Parameter(name: 'experience_to', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 0), description: 'Maximum experience in months'),
            new OA\Parameter(name: 'gender', in: 'query', schema: new OA\Schema(type: 'string', enum: ['male', 'female']), description: 'Filter by gender'),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Birthday date range start'),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Birthday date range end'),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filter by active status'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Doctors retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Doctors retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/DoctorResource')
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
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/doctors/{doctor}/ratings',
        summary: 'Get doctor ratings (paginated)',
        tags: ['Doctors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor user UUID'),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by comment or rater name'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Doctor ratings retrieved successfully', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'message', type: 'string', example: 'Doctor ratings retrieved successfully'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RatingResource')),
                    new OA\Property(property: 'meta', type: 'object'),
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Doctor not found'),
        ]
    )]
    public function ratings() {}

    #[OA\Post(
        path: '/api/v1/doctors',
        summary: 'Create a new doctor',
        description: 'Admin-only. Creates a doctor with is_active=true immediately.',
        tags: ['Doctors'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'first_name', type: 'string', example: 'Khaled'),
                        new OA\Property(property: 'last_name', type: 'string', example: 'Suleiman'),
                        new OA\Property(property: 'username', type: 'string', example: 'drkhaled'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'doctor@example.com'),
                        new OA\Property(property: 'phone', type: 'string', example: '+963912345679'),
                        new OA\Property(property: 'address', type: 'string', example: 'Aleppo, Syria'),
                        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                        new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1985-03-20'),
                        new OA\Property(property: 'specialization_id', type: 'string', format: 'uuid', description: 'Specialization UUID'),
                        new OA\Property(property: 'experience_months', type: 'integer', example: 60),
                        new OA\Property(property: 'password', type: 'string', format: 'password'),
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Profile image'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Doctor created successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 201),
                new OA\Property(property: 'message', type: 'string', example: 'Doctor created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/DoctorResource'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Get(
        path: '/api/v1/doctors/{doctor}',
        summary: 'Get a single doctor',
        tags: ['Doctors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'doctor',
                in: 'path',
                required: true,
                description: 'Doctor ID (user UUID)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Doctor retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Doctor retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/DoctorResource'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 404,
                description: 'Doctor not found'
            ),
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: '/api/v1/doctors/{doctor}',
        summary: 'Update a doctor',
        tags: ['Doctors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor user UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'Khaled'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Suleiman Updated'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'doctor@example.com'),
                new OA\Property(property: 'phone', type: 'string', example: '+963912345679'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1985-03-20'),
                new OA\Property(property: 'specialization_id', type: 'string', format: 'uuid', description: 'Specialization UUID'),
                new OA\Property(property: 'experience_months', type: 'integer', example: 60),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Doctor updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}

    #[OA\Patch(
        path: '/api/v1/doctors/{doctor}',
        summary: 'Partially update a doctor',
        tags: ['Doctors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor user UUID'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'Khaled'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Doctor updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updatePartial() {}

    #[OA\Delete(
        path: '/api/v1/doctors/{doctor}',
        summary: 'Delete a doctor',
        tags: ['Doctors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor user UUID'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Doctor deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
            new OA\Response(response: 409, description: 'Doctor has active appointments'),
        ]
    )]
    public function destroy() {}

    #[OA\Put(
        path: '/api/v1/doctors/{doctor}/activate-account',
        summary: 'Activate a doctor account',
        description: 'Admin-only. Sets is_active=true for the doctor user.',
        tags: ['Doctors'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'doctor', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Doctor user UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Account activated successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'Account activated successfully.'),
                new OA\Property(property: 'data', ref: '#/components/schemas/DoctorResource'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
        ]
    )]
    public function activateAccount() {}
}
