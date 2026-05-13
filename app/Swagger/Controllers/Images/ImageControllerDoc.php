<?php

namespace App\Swagger\Controllers\Images;

use OpenApi\Attributes as OA;

class ImageControllerDoc
{
    #[OA\Post(
        path: '/api/v1/images',
        summary: 'Upload an image',
        tags: ['Images'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                        new OA\Property(property: 'type', type: 'string', enum: ['user', 'country']),
                        new OA\Property(property: 'imageable_id', type: 'string', format: 'uuid'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Image uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'integer', example: 201),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ImageResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store() {}

    #[OA\Delete(
        path: '/api/v1/images/{image}',
        summary: 'Delete an image',
        tags: ['Images'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'image', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Image deleted successfully'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy() {}
}
