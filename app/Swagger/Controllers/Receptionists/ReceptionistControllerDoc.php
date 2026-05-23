<?php

namespace App\Swagger\Controllers\Receptionists;

use OpenApi\Attributes as OA;

class ReceptionistControllerDoc
{
    #[OA\Put(
        path: '/api/v1/receptionists/{receptionist}/activate-account',
        summary: 'Activate a receptionist account',
        description: 'Admin-only. Sets is_active=true for the receptionist user.',
        tags: ['Receptionists'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'receptionist', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Receptionist UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Account activated successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'Account activated successfully.'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden (admin only)'),
        ]
    )]
    public function activateAccount() {}
}
