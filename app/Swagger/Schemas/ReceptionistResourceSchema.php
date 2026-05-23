<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ReceptionistResource',
    description: 'Receptionist resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'User UUID'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Layla'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Hassan'),
        new OA\Property(property: 'username', type: 'string', example: 'laylah'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'receptionist@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+963912345680'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Homs, Syria'),
        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'female'),
        new OA\Property(property: 'birthday_date', type: 'string', format: 'date', nullable: true, example: '1998-11-05'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['receptionist']),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'shift_start', type: 'string', nullable: true, example: '09:00'),
        new OA\Property(property: 'shift_end', type: 'string', nullable: true, example: '17:00'),
    ],
    type: 'object'
)]
class ReceptionistResourceSchema
{
}
