<?php

namespace App\Http\Middleware;

use App\Domains\Shared\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class ValidateApiBodySize
{
    private const int MAX_JSON_BYTES = 102400;
    private const int MAX_MULTIPART_BYTES = 41943040;

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->is('api/*')) {
            return $next($request);
        }

        $contentType = strtolower($request->header('Content-Type', ''));
        $contentLength = $request->header('Content-Length');

        if ($contentLength === null) {
            return $next($request);
        }

        $length = (int) $contentLength;

        if (str_contains($contentType, 'multipart/form-data')) {
            if ($length > self::MAX_MULTIPART_BYTES) {
                return ApiResponse::error(
                    __('Request body too large. Maximum 40MB for file uploads.'),
                    status: \App\Enums\HttpStatusEnum::ContentTooLarge,
                );
            }
        } elseif ($length > self::MAX_JSON_BYTES) {
            return ApiResponse::error(
                __('Request body too large. Maximum 100KB for JSON requests.'),
                status: \App\Enums\HttpStatusEnum::ContentTooLarge,
            );
        }

        return $next($request);
    }
}
