<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PatientResource',
    description: 'Patient resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'User UUID'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Ahmed'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Ali'),
        new OA\Property(property: 'username', type: 'string', example: 'ahmedali'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'patient@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+963912345678'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Damascus, Syria'),
        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
        new OA\Property(property: 'birthday_date', type: 'string', format: 'date', nullable: true, example: '1995-06-15'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['patient']),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'image', ref: '#/components/schemas/ImageResource', nullable: true),
    ],
    type: 'object'
)]
class PatientResourceSchema
{
}
