<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PrescriptionResource',
    description: 'Prescription resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'medical_record_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'prescription_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'archived', 'expired']),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PrescriptionItemResource')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class PrescriptionResourceSchema
{
}
