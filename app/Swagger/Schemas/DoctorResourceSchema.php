<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DoctorResource',
    description: 'Doctor resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'User UUID'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Smith'),
        new OA\Property(property: 'username', type: 'string', example: 'janesmith'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'doctor@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+963912345678'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Damascus, Syria'),
        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'female'),
        new OA\Property(property: 'birthday_date', type: 'string', format: 'date', nullable: true, example: '1985-05-15'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['doctor']),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'specialization',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'slug', type: 'string'),
                new OA\Property(property: 'name', type: 'object', properties: [
                    new OA\Property(property: 'ar', type: 'string'),
                    new OA\Property(property: 'en', type: 'string'),
                ]),
                new OA\Property(property: 'description', type: 'object', nullable: true, properties: [
                    new OA\Property(property: 'ar', type: 'string'),
                    new OA\Property(property: 'en', type: 'string'),
                ]),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'schedules',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'day_of_week', type: 'string', enum: ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
                    new OA\Property(property: 'start_time', type: 'string', example: '09:00'),
                    new OA\Property(property: 'end_time', type: 'string', example: '17:00'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        new OA\Property(
            property: 'supervision_request',
            type: 'object',
            nullable: true,
            description: 'Only for Patient role, only in GET /api/v1/doctors/{doctor}. Supervision request info for the authenticated patient (omitted entirely for non-patients)',
            properties: [
                new OA\Property(property: 'has_request', type: 'boolean', description: 'Whether the authenticated patient has a supervision request for this doctor'),
                new OA\Property(property: 'status', type: 'string', nullable: true, enum: ['pending', 'approved', 'rejected', 'cancelled'], description: 'Status of the supervision request (null if no request)'),
            ]
        ),
        new OA\Property(
            property: 'has_rated',
            type: 'boolean',
            description: 'Only for Patient role, only in GET /api/v1/doctors/{doctor}. Whether the authenticated patient has rated this doctor (omitted entirely for non-patients)',
        ),
        new OA\Property(
            property: 'rating',
            type: 'object',
            properties: [
                new OA\Property(property: 'avg', type: 'number', format: 'float', example: 4.5, description: 'Average rating (0 if none)'),
                new OA\Property(property: 'count', type: 'integer', example: 12, description: 'Total ratings count'),
                new OA\Property(
                    property: 'recent',
                    type: 'array',
                    description: 'Last 5 ratings (empty if none)',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'rating', type: 'integer', example: 5, description: '1-5'),
                            new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Excellent doctor, very professional'),
                            new OA\Property(
                                property: 'rater',
                                type: 'object',
                                nullable: true,
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'first_name', type: 'string'),
                                    new OA\Property(property: 'last_name', type: 'string'),
                                ]
                            ),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        ],
                        type: 'object'
                    )
                ),
            ]
        ),
    ],
    type: 'object'
)]
class DoctorResourceSchema
{
}
