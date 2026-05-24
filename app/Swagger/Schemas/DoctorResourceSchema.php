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
    ],
    type: 'object'
)]
class DoctorResourceSchema
{
}
