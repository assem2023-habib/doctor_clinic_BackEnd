# List, Show & Delete Files

## List Files

### Route Information

- **Method:** `GET`
- **Path:** `/v1/files`
- **Middleware:** `auth:api`, `active`

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `mine` | bool (query) | When `1`, returns only files owned by the authenticated user |

Access control:

- **Patient** — sees only own files
- **Doctor** — sees files for medical records where they are the treating doctor or supervising doctor (via access chain)
- **Admin** — sees all files
- **`mine=1`** — scopes to `user_id = Auth::id()` regardless of role

### Response

```json
{
  "success": true,
  "message": "Files retrieved successfully",
  "data": [
    {
      "id": "019eca5c-...",
      "original_name": "lab_result.pdf",
      "mime_type": "application/pdf",
      "size": 204800,
      "file_category": "lab_result",
      "upload_status": "completed",
      "disk": "local",
      "checksum": "abc123...",
      "medical_record_id": "019eca5c-...",
      "user_id": "019eca5c-...",
      "downloads_count": 3,
      "created_at": "2026-06-15T10:00:00.000000Z",
      "updated_at": "2026-06-15T10:00:00.000000Z"
    }
  ]
}
```

---

## Show File

### Route Information

- **Method:** `GET`
- **Path:** `/v1/files/{file}`
- **Middleware:** `auth:api`, `active`

Access is checked via `FileAccessService` (Chain of Responsibility). Returns 403 if the user has no right to view the file.

### Response

Same shape as list item above.

---

## Delete File

### Route Information

- **Method:** `DELETE`
- **Path:** `/v1/files/{file}`
- **Middleware:** `auth:api`, `active`

### Action: `DeleteFileAction`

```php
if ($file->path) {
    $this->storageService->disk($file->disk)->delete($file->path);
}
$file->delete(); // soft delete
```

Only the upload owner can delete the file.

### Response

```json
{
  "success": true,
  "message": "File deleted successfully",
  "data": null
}
```

Status: **204 No Content**

---

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden — not the owner (delete), or no access (show) |
| 404 | File not found |
