<?php

namespace App\Swagger\Controllers;

use OpenApi\Attributes as OA;

class DeviceTokenControllerDoc
{
    #[OA\Post(
        path: '/api/v1/device-tokens',
        summary: 'Update device FCM token',
        description: 'Adds a new FCM token to the authenticated user\'s fcm_tokens array. Duplicates are ignored.',
        tags: ['Device Tokens'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'token', type: 'string', maxLength: 500, description: 'FCM device token'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Device token updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update() {}
}
