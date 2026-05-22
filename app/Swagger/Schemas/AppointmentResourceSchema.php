<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AppointmentResource',
    description: 'Appointment resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['requested', 'set', 'accepted', 'rejected', 'in_progress', 'completed', 'cancelled']),
        new OA\Property(property: 'reason', type: 'string', nullable: true),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(property: 'appointment_date', type: 'string', format: 'date', nullable: true, example: '2026-06-01'),
        new OA\Property(property: 'start_time', type: 'string', nullable: true, example: '10:00'),
        new OA\Property(property: 'end_time', type: 'string', nullable: true, example: '11:00'),
        new OA\Property(property: 'created_by', type: 'string', example: '019e1d0f-...: Admin User'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'patient',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], nullable: true),
                new OA\Property(property: 'birthday_date', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'image', ref: '#/components/schemas/ImageResource', nullable: true),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'doctor',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'last_name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'specialization', type: 'string', nullable: true),
                new OA\Property(property: 'experience_months', type: 'integer', nullable: true),
                new OA\Property(property: 'image', ref: '#/components/schemas/ImageResource', nullable: true),
            ],
            type: 'object'
        ),
    ],
    type: 'object'
)]
class AppointmentResourceSchema
{
}
