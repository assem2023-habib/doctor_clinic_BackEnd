<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FileResource',
    description: 'File resource',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'original_name', type: 'string', example: 'report.pdf'),
        new OA\Property(property: 'mime_type', type: 'string', example: 'application/pdf'),
        new OA\Property(property: 'size', type: 'integer', format: 'int64', example: 1048576),
        new OA\Property(property: 'file_category', type: 'string', enum: ['document', 'lab_result', 'xray', 'prescription', 'report', 'other']),
        new OA\Property(property: 'upload_status', type: 'string', enum: ['pending', 'uploading', 'completed', 'failed']),
        new OA\Property(property: 'disk', type: 'string', enum: ['local'], example: 'local'),
        new OA\Property(property: 'checksum', type: 'string', nullable: true, example: 'abc123def456...'),
        new OA\Property(property: 'medical_record_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'downloads_count', type: 'integer', example: 5),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class FileResourceSchema
{
}
