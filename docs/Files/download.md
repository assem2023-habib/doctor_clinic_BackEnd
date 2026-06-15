# Download File (Signed)

> Download a file using a temporary signed URL. Supports resumable downloads via HTTP `Range` headers (required by iOS, network-resilient clients).

## Route Information

- **Method:** `GET`
- **Path:** `/files/{file}/download`
- **Middleware:** `signed` (Laravel `ValidateSignature`)

The URL must contain valid `expires` and `signature` query parameters. This route is **not** behind `auth:api` — authentication is embedded in the signed URL (`user` param).

## Controller Logic

```php
// Validate access (signed URL guarantees authenticity)
$user = User::findOrFail($request->user);

// Serve via BinaryFileResponse with Range support
$fullPath = $this->storageService->disk($file->disk)->retrieve($file->path);

$response = new BinaryFileResponse($fullPath, 200, [
    'Content-Type'        => $file->mime_type,
    'Content-Length'      => $file->size,
    'Accept-Ranges'       => 'bytes',
    'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"',
]);

// Log the download
FileDownload::create([
    'id'            => (string) Str::uuid7(),
    'file_id'       => $file->id,
    'user_id'       => $user->id,
    'ip_address'    => $request->ip(),
    'user_agent'    => $request->userAgent(),
    'downloaded_at' => now(),
]);

return $response;
```

## Client Example

```http
GET /files/019eca5c-.../download?expires=1755273599&user=019eca5c-...&signature=...
```

Resumable download (e.g. iOS NSURLSession, curl):

```http
GET /files/019eca5c-.../download?expires=1755273599&user=019eca5c-...&signature=...
Range: bytes=500-999
```

### Response (full)

```
HTTP/1.1 200 OK
Content-Type: application/pdf
Content-Length: 1048576
Accept-Ranges: bytes
Content-Disposition: attachment; filename="report.pdf"

<binary data>
```

### Response (partial / Range)

```
HTTP/1.1 206 Partial Content
Content-Type: application/pdf
Content-Range: bytes 500-999/1048576
Content-Length: 500
Accept-Ranges: bytes
Content-Disposition: attachment; filename="report.pdf"

<500 bytes of binary data>
```

## FileDownload Record

Each download is logged in the `file_downloads` table:

| Column | Description |
|--------|-------------|
| `id` | UUID v7 |
| `file_id` | FK to files table |
| `user_id` | The user who downloaded |
| `ip_address` | Client IP |
| `user_agent` | Client user agent |
| `downloaded_at` | Timestamp |

## Errors

| Status | Condition |
|--------|-----------|
| 403 | Invalid or expired signature |
| 404 | File not found or deleted |
| 404 | File missing from storage disk |
