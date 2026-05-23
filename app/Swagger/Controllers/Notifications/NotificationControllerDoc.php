<?php

namespace App\Swagger\Controllers\Notifications;

use OpenApi\Attributes as OA;

class NotificationControllerDoc
{
    #[OA\Get(
        path: '/api/v1/notifications',
        summary: 'List authenticated user notifications',
        description: 'Returns paginated notifications for the authenticated user along with unread count.',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100), description: 'Items per page (max 100)'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1), description: 'Page number'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notifications retrieved successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'Notifications retrieved successfully'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'notifications', type: 'array', items: new OA\Items(ref: '#/components/schemas/NotificationResource')),
                    new OA\Property(property: 'unread_count', type: 'integer', example: 5),
                ], type: 'object'),
                new OA\Property(property: 'meta', properties: [
                    new OA\Property(property: 'pagination', properties: [
                        new OA\Property(property: 'current_page', type: 'integer'),
                        new OA\Property(property: 'last_page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'hasNextPage', type: 'boolean'),
                        new OA\Property(property: 'hasPreviousPage', type: 'boolean'),
                    ], type: 'object'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: '/api/v1/notifications/{notification}',
        summary: 'Get a single notification',
        description: 'Returns a single notification. Automatically marks it as read.',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Notification UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification retrieved successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'Notification retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/NotificationResource'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Notification not found'),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: '/api/v1/notifications/{notification}/read',
        summary: 'Mark a single notification as read',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Notification UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification marked as read', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'Notification marked as read'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Notification not found'),
        ]
    )]
    public function markAsRead() {}

    #[OA\Post(
        path: '/api/v1/notifications/read',
        summary: 'Mark multiple notifications as read',
        description: 'Accepts an array of notification IDs to mark as read.',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid'), description: 'Array of notification UUIDs', minItems: 1),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Notifications marked as read', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'Notifications marked as read'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function markMultipleAsRead() {}

    #[OA\Post(
        path: '/api/v1/notifications/read-all',
        summary: 'Mark all notifications as read',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'All notifications marked as read', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'All notifications marked as read'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function markAllAsRead() {}

    #[OA\Delete(
        path: '/api/v1/notifications/{notification}',
        summary: 'Delete a single notification',
        description: 'Detaches the notification from the authenticated user.',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Notification UUID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'Notification deleted'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Notification not found'),
        ]
    )]
    public function destroy() {}

    #[OA\Delete(
        path: '/api/v1/notifications',
        summary: 'Delete multiple notifications',
        description: 'Accepts an array of notification IDs to detach from the authenticated user.',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid'), description: 'Array of notification UUIDs', minItems: 1),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Notifications deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'Notifications deleted'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function destroyMultiple() {}

    #[OA\Delete(
        path: '/api/v1/notifications/all',
        summary: 'Delete all notifications',
        description: 'Detaches all notifications from the authenticated user.',
        tags: ['Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'All notifications deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', example: 200),
                new OA\Property(property: 'message', type: 'string', example: 'All notifications deleted'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function destroyAll() {}
}
