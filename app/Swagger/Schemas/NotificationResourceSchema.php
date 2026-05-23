<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'NotificationResource',
    description: 'Notification resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'Notification UUID'),
        new OA\Property(property: 'topic', type: 'string', example: 'appointment.requested'),
        new OA\Property(property: 'title', type: 'string', example: 'New Appointment Request'),
        new OA\Property(property: 'body', type: 'object', nullable: true, description: 'Notification payload data'),
        new OA\Property(property: 'is_read', type: 'boolean', example: false),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class NotificationResourceSchema
{
}
