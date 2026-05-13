<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Postman (and some clients) may send "Authorization: Bearer <jwt>, Bearer <jwt>".
 * League OAuth2 only strips the first "Bearer " prefix, leaving junk that breaks JWT parsing.
 * Collapse to a single "Bearer <token>" using the last Bearer segment (typically the fresh token).
 */
class NormalizeDuplicateBearerAuthorization
{
    public function handle(Request $request, Closure $next): mixed
    {
        $raw = $request->header('Authorization');
        if (! is_string($raw) || $raw === '') {
            return $next($request);
        }

        $segments = array_values(array_filter(array_map('trim', explode(',', $raw))));
        $bearerSegments = array_values(array_filter(
            $segments,
            static fn (string $s): bool => str_starts_with(strtolower($s), 'bearer ')
        ));

        if (count($bearerSegments) <= 1) {
            return $next($request);
        }

        $request->headers->set('Authorization', (string) end($bearerSegments), true);

        return $next($request);
    }
}
