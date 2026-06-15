# Request Download Link

> Generate a temporary signed URL for file download. The user must have access via the Chain of Responsibility. The URL expires at end of day (configurable via `config/files.download_ttl_minutes`).

## Route Information

- **Method:** `POST`
- **Path:** `/v1/files/{file}/download-link`
- **Middleware:** `auth:api`, `active`

## Action: `RequestDownloadLinkAction`

```php
$this->accessService->canAccess($user, $file, throw: true);

$url = URL::temporarySignedRoute(
    'files.download',
    now()->endOfDay(),
    ['file' => $file->id, 'user' => $user->id]
);

return [
    'url'          => $url,
    'expires_at'   => now()->endOfDay()->toDateTimeString(),
];
```

## Response

```json
{
  "success": true,
  "message": "Download link generated successfully",
  "data": {
    "url": "http://localhost/files/019eca5c-.../download?expires=...&signature=...",
    "expires_at": "2026-06-15T23:59:59.000000Z"
  }
}
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | No access to the file |
| 404 | File not found |
