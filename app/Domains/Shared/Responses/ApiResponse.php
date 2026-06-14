<?php

namespace App\Domains\Shared\Responses;

use App\Enums\HttpStatusEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        HttpStatusEnum $status = HttpStatusEnum::OK,
        ?array $pagination = null,
    ): JsonResponse {
        $response = [
            'status' => $status->value,
            'message' => $message,
            'data' => $data,
        ];

        if ($pagination) {
            $response['meta']['pagination'] = $pagination;
        }

        return response()->json($response, $status->value);
    }

    public static function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'limit' => $paginator->perPage(),
            'total' => $paginator->total(),
            'hasNextPage' => $paginator->hasMorePages(),
            'hasPreviousPage' => $paginator->currentPage() > 1,
        ];
    }

    public static function created(
        mixed $data = null,
        string $message = 'Created successfully',
    ): JsonResponse {
        return self::success($data, $message, HttpStatusEnum::Created);
    }

    public static function noContent(string $message = 'No content'): JsonResponse
    {
        return self::success(null, $message, HttpStatusEnum::NoContent);
    }

    public static function error(
        string $message = 'Error',
        HttpStatusEnum $status = HttpStatusEnum::BadRequest,
        mixed $data = null,
        ?array $errors = null,
        ?string $errorCode = null,
    ): JsonResponse {
        $response = [
            'status' => $status->value,
            'message' => $message,
            'data' => $data,
        ];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status->value);
    }

    public static function notFound(
        string $message = 'Resource not found',
        mixed $data = null,
    ): JsonResponse {
        return self::error($message, HttpStatusEnum::NotFound, $data);
    }

    public static function unauthorized(
        string $message = 'Unauthorized',
        mixed $data = null,
    ): JsonResponse {
        return self::error($message, HttpStatusEnum::Unauthorized, $data);
    }

    public static function forbidden(
        string $message = 'Forbidden',
        mixed $data = null,
    ): JsonResponse {
        return self::error($message, HttpStatusEnum::Forbidden, $data);
    }

    public static function validationError(
        string $message = 'Validation failed',
        ?array $errors = null,
    ): JsonResponse {
        return self::error($message, HttpStatusEnum::UnprocessableEntity, null, $errors);
    }

    public static function serverError(
        string $message = 'Internal server error',
        mixed $data = null,
    ): JsonResponse {
        return self::error($message, HttpStatusEnum::InternalServerError, $data);
    }
}
