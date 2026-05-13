<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LogApiBearerAndRequestDetails
{
    /** @var list<string> */
    private const REDACT_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'old_password',
        'client_secret',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        if (! config('api_auth_debug.enabled')) {
            return $next($request);
        }

        $bearer = $request->bearerToken();
        $authHeader = $request->header('Authorization');

        Log::info('[API Debug] Incoming HTTP request', $this->incomingPayload($request, $authHeader, $bearer));

        return $next($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function incomingPayload(Request $request, ?string $authHeader, ?string $bearer): array
    {
        return [
            'direction' => 'incoming',
            'timestamp' => now()->toIso8601String(),
            'method' => $request->method(),
            'full_url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'ips' => $request->ips(),
            'user_agent' => $request->userAgent(),
            'authorization_header_raw' => $authHeader,
            'bearer_token' => $bearer,
            'bearer_token_length' => $bearer !== null ? strlen($bearer) : null,
            'bearer_dot_count' => $bearer !== null ? substr_count($bearer, '.') : null,
            'query' => $request->query->all(),
            'headers' => $request->headers->all(),
            'content_type' => $request->header('Content-Type'),
            'raw_body' => $this->rawBody($request),
            'parsed_body' => $this->parsedBodyForLog($request),
        ];
    }

    private function rawBody(Request $request): ?string
    {
        $raw = $request->getContent();
        if ($raw === '' || $raw === false) {
            return null;
        }

        $contentType = strtolower((string) $request->header('Content-Type', ''));

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($raw, true);

            return is_array($decoded)
                ? (string) json_encode($this->redactSensitive($decoded), JSON_UNESCAPED_UNICODE)
                : $raw;
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($raw, $parsed);

            return http_build_query($this->redactSensitive($parsed));
        }

        return $raw;
    }

    /**
     * @return array<string, mixed>
     */
    private function parsedBodyForLog(Request $request): array
    {
        $data = $request->all();

        foreach ($request->allFiles() as $key => $file) {
            if ($file instanceof UploadedFile) {
                $data[$key] = [
                    '_upload_original_name' => $file->getClientOriginalName(),
                    '_upload_size' => $file->getSize(),
                    '_upload_mime' => $file->getClientMimeType(),
                ];
            }
        }

        return $this->redactSensitive($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function redactSensitive(array $data): array
    {
        foreach (self::REDACT_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '***REDACTED***';
            }
        }

        return $data;
    }
}
