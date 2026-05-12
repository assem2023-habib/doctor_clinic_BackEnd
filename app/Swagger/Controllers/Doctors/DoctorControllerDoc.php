<?php

namespace App\Swagger\Controllers\Doctors;

use OpenApi\Attributes as OA;

class DoctorControllerDoc
{
    #[OA\Get(
        path: '/api/v1/doctors',
        summary: 'List all doctors',
        tags: ['Doctors'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Items per page',
                schema: new OA\Schema(type: 'integer', default: 20)
            ),
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
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/doctors/{doctor}',
        summary: 'Get a single doctor',
        tags: ['Doctors'],
        parameters: [
            new OA\Parameter(
                name: 'doctor',
                in: 'path',
                required: true,
                description: 'Doctor ID (UUID)',
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
            new OA\Response(
                response: 404,
                description: 'Doctor not found'
            ),
        ]
    )]
    public function show() {}
}
