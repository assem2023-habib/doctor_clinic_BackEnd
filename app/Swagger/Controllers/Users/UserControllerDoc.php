<?php

namespace App\Swagger\Controllers\Users;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/users',
    summary: 'List all users (admin)',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(name: 'role', in: 'query', description: 'Filter by role slug', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'search', in: 'query', description: 'Search by name/email/username', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'gender', in: 'query', description: 'Filter by gender', schema: new OA\Schema(type: 'string', enum: ['male', 'female'])),
        new OA\Parameter(name: 'is_active', in: 'query', description: 'Filter by active status', schema: new OA\Schema(type: 'boolean')),
        new OA\Parameter(name: 'date_from', in: 'query', description: 'Filter by created_at from', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'date_to', in: 'query', description: 'Filter by created_at to', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        new OA\Parameter(name: 'sort', in: 'query', description: 'Sort field', schema: new OA\Schema(type: 'string', default: 'created_at')),
        new OA\Parameter(name: 'order', in: 'query', description: 'Sort order', schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Users retrieved successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Get(
    path: '/api/v1/users/{user}',
    summary: 'Get a single user (admin)',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'User retrieved successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Put(
    path: '/api/v1/users/{user}',
    summary: 'Update a user (admin)',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'first_name', type: 'string', example: 'John'),
            new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
            new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
            new OA\Property(property: 'phone', type: 'string', example: '+963912345678'),
            new OA\Property(property: 'address', type: 'string', example: 'Damascus, Syria'),
            new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
            new OA\Property(property: 'birthday_date', type: 'string', format: 'date', example: '1995-06-15'),
            new OA\Property(property: 'city_id', type: 'string', format: 'uuid', example: '019e1d0f-...'),
        ])
    ),
    responses: [
        new OA\Response(response: 200, description: 'User updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Put(
    path: '/api/v1/users/{user}/toggle-active',
    summary: 'Toggle user active status (admin)',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'User activated/deactivated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Delete(
    path: '/api/v1/users/{user}',
    summary: 'Delete a user (admin)',
    security: [['bearerAuth' => []]],
    tags: ['Users'],
    parameters: [
        new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'User deleted successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden - cannot delete admin users'),
    ]
)]
class UserControllerDoc
{
}
