<?php

namespace App\Swagger\Controllers\Files;

use OpenApi\Attributes as OA;

class FileControllerDoc
{
    #[OA\Post(
        path: '/api/v1/files',
        summary: 'Direct file upload',
        tags: ['Files'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                        new OA\Property(property: 'medical_record_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'file_category', type: 'string', enum: ['document', 'lab_result', 'xray', 'prescription', 'report', 'other']),
                        new OA\Property(property: 'checksum', type: 'string', nullable: true, description: 'SHA256 hex (64 chars)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'File uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/FileResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Post(
        path: '/api/v1/files/init',
        summary: 'Initialize chunked upload',
        tags: ['Files'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'medical_record_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'file_category', type: 'string', enum: ['document', 'lab_result', 'xray', 'prescription', 'report', 'other']),
                    new OA\Property(property: 'original_name', type: 'string'),
                    new OA\Property(property: 'mime_type', type: 'string'),
                    new OA\Property(property: 'file_size', type: 'integer', description: 'Total file size in bytes (max 20971520)'),
                    new OA\Property(property: 'checksum', type: 'string', nullable: true, description: 'SHA256 hex (64 chars)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Chunked upload initialized',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/FileResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function init() {}

    #[OA\Post(
        path: '/api/v1/files/{file}/chunk',
        summary: 'Upload a single chunk',
        tags: ['Files'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'chunk', type: 'string', format: 'binary'),
                        new OA\Property(property: 'chunk_index', type: 'integer', description: 'Zero-based chunk index'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Chunk uploaded',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'total_chunks', type: 'integer'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function uploadChunk() {}

    #[OA\Post(
        path: '/api/v1/files/{file}/complete',
        summary: 'Assemble chunks into final file',
        tags: ['Files'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'checksum', type: 'string', nullable: true, description: 'SHA256 hex (64 chars)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'File assembled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/FileResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error / checksum mismatch'),
        ]
    )]
    public function completeUpload() {}

    #[OA\Get(
        path: '/api/v1/files',
        summary: 'List files',
        tags: ['Files'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'mine', in: 'query', required: false, schema: new OA\Schema(type: 'integer', enum: [0, 1]), description: 'Filter by own files'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Files list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/FileResource')),
                    ]
                )
            ),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/files/{file}',
        summary: 'Show file details',
        tags: ['Files'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/FileResource'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden - no access'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show() {}

    #[OA\Delete(
        path: '/api/v1/files/{file}',
        summary: 'Delete a file (soft delete)',
        tags: ['Files'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'File deleted successfully'),
            new OA\Response(response: 403, description: 'Forbidden - not the owner'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy() {}

    #[OA\Post(
        path: '/api/v1/files/{file}/download-link',
        summary: 'Generate signed download URL',
        tags: ['Files'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Download link generated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'url', type: 'string', format: 'uri', description: 'Temporary signed URL'),
                            new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden - no access'),
        ]
    )]
    public function requestDownloadLink() {}

    #[OA\Get(
        path: '/files/{file}/download',
        summary: 'Download file via signed URL',
        tags: ['Files'],
        parameters: [
            new OA\Parameter(name: 'file', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'expires', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'signature', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'user', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File binary (supports Range requests)'),
            new OA\Response(response: 206, description: 'Partial content (Range request)'),
            new OA\Response(response: 403, description: 'Invalid or expired signature'),
            new OA\Response(response: 404, description: 'File not found or deleted'),
        ]
    )]
    public function download() {}
}
