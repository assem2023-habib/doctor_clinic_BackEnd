<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PrescriptionItemResource',
    description: 'Prescription item resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'prescription_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'medicine_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'dosage', type: 'string'),
        new OA\Property(property: 'frequency', type: 'string'),
        new OA\Property(property: 'duration', type: 'string'),
        new OA\Property(property: 'instructions', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class PrescriptionItemResourceSchema
{
}
