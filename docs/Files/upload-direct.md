# Direct File Upload

> Upload a file directly (no chunking). Max 20MB, MIME type validated against allowed list.

## Route Information

- **Method:** `POST`
- **Path:** `/v1/files`
- **Middleware:** `auth:api`, `active`

## Request

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `file` | file | required, max:20480kb, mimes from config | The file to upload |
| `medical_record_id` | string | required, exists:medical_records,id | UUID of the medical record |
| `file_category` | string | required, enum: `document`, `lab_result`, `xray`, `prescription`, `report`, `other` | File category |
| `checksum` | string | nullable, regex:/^[a-f0-9]{64}$/i | SHA256 checksum |

### Example

```
POST /v1/files
Authorization: Bearer {token}
Content-Type: multipart/form-data

file=@report.pdf
medical_record_id=019eca5c-1234-5678-9abc-def012345678
file_category=report
checksum=abc123...
```

## Action: `StoreFileAction`

```php
$path = $this->storageService->disk($data->disk)->store(
    $data->file->getRealPath(),
    "files/{$data->medicalRecordId}/{$uuid}.{$ext}"
);

return File::create([
    'user_id'           => Auth::id(),
    'medical_record_id' => $data->medicalRecordId,
    'disk'              => $data->disk,
    'path'              => $path,
    'original_name'     => $data->originalName,
    'mime_type'         => $data->mimeType,
    'size'              => $data->size,
    'file_category'     => $data->fileCategory,
    'checksum'          => $data->checksum,
    'upload_status'     => FileUploadStatusEnum::Completed,
    'total_chunks'      => 1,
]);
```

## Response

```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": "019eca5c-...",
    "original_name": "report.pdf",
    "mime_type": "application/pdf",
    "size": 1048576,
    "file_category": "report",
    "upload_status": "completed",
    "disk": "local",
    "checksum": "abc123...",
    "medical_record_id": "019eca5c-1234-5678-9abc-def012345678",
    "user_id": "019eca5c-...",
    "downloads_count": 0,
    "created_at": "2026-06-15T10:00:00.000000Z",
    "updated_at": "2026-06-15T10:00:00.000000Z"
  }
}
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 422 | Missing/invalid file, MIME not allowed, size > 20MB, invalid medical_record_id |
| 422 | Invalid file_category value |
