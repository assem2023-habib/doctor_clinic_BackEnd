# Chunked File Upload

> Upload large files (up to 20MB) in chunks. Three-step process: init → upload chunks → complete.

## Init Chunked Upload

### Route Information

- **Method:** `POST`
- **Path:** `/v1/files/init`
- **Middleware:** `auth:api`, `active`

### Request

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `medical_record_id` | string | required, exists:medical_records,id | UUID of the medical record |
| `file_category` | string | required, enum values | File category |
| `original_name` | string | required, string | Original filename |
| `mime_type` | string | required, string | MIME type |
| `file_size` | integer | required, integer, min:1, max:20971520 (20MB) | Total file size in bytes |
| `checksum` | string | nullable, regex:/^[a-f0-9]{64}$/i | SHA256 checksum |

### Response

```json
{
  "success": true,
  "message": "Chunked upload initialized",
  "data": {
    "id": "019eca5c-...",
    "upload_status": "uploading",
    "total_chunks": 0,
    "file_category": "document",
    "medical_record_id": "019eca5c-...",
    "original_name": "large.pdf",
    "mime_type": "application/pdf",
    "size": 5242880,
    "checksum": null
  }
}
```

---

## Upload Chunk

### Route Information

- **Method:** `POST`
- **Path:** `/v1/files/{file}/chunk`
- **Middleware:** `auth:api`, `active`

### Request

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `chunk` | file | required, file | The chunk binary |
| `chunk_index` | integer | required, integer, min:0 | Zero-based chunk index |

### Action: `UploadChunkAction`

```php
$this->chunkStorage->storeChunk($file->id, $data->chunkIndex, $data->chunk);
$file->increment('total_chunks');
```

### Response

```json
{
  "success": true,
  "message": "Chunk uploaded successfully",
  "data": {
    "id": "019eca5c-...",
    "total_chunks": 3
  }
}
```

---

## Complete Upload (Assemble Chunks)

### Route Information

- **Method:** `POST`
- **Path:** `/v1/files/{file}/complete`
- **Middleware:** `auth:api`, `active`

### Request

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `checksum` | string | nullable, regex:/^[a-f0-9]{64}$/i | SHA256 checksum to verify |

### Action: `AssembleChunksAction`

```php
// Assemble chunks into final file
$tempPath = $this->chunkStorage->assembleChunks($file->id, $file->total_chunks);

// Verify checksum if provided
if ($data->checksum && hash_file('sha256', $tempPath) !== $data->checksum) {
    throw new ApiServiceException(400, 'Checksum mismatch');
}

// Store final file
$finalPath = $this->storageService->disk($file->disk)->store(
    $tempPath,
    "files/{$file->medical_record_id}/{$file->id}.{$ext}"
);

// Clean up chunks
$this->chunkStorage->cleanup($file->id);

// Update file record
$file->update([
    'path'          => $finalPath,
    'upload_status' => FileUploadStatusEnum::Completed,
    'checksum'      => $data->checksum ?? $file->checksum,
]);
```

### Response

```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": "019eca5c-...",
    "upload_status": "completed",
    "total_chunks": 4,
    "path": "files/019eca5c-.../019eca5c-....pdf",
    "size": 5242880
  }
}
```

---

## Sequence Diagram

```
Client              FileController          ChunkStorage    FileStorage      DB
  │                        │                    │               │            │
  │── POST /files/init ───>│                    │               │            │
  │                        │── create record ──────────────────────────────>│
  │<── 201 {id, pending} ──│                    │               │            │
  │                        │                    │               │            │
  │── POST /files/{id}/chunk ──>│                │               │            │
  │   (× N times)          │── storeChunk ─────>│               │            │
  │                        │── increment ──────────────────────────────────>│
  │<── 200 {total_chunks} ─│                    │               │            │
  │                        │                    │               │            │
  │── POST /files/{id}/complete ──>│            │               │            │
  │                        │── assemble ───────>│               │            │
  │                        │── verify checksum  │               │            │
  │                        │── store ──────────────────────────>│            │
  │                        │── cleanup ────────>│               │            │
  │                        │── update status ──────────────────────────────>│
  │<── 200 {completed} ────│                    │               │            │
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Not the upload owner |
| 422 | Invalid parameters, missing fields |
| 422 | Checksum mismatch during completion |
| 422 | Total assembled size > 20MB (exceeds max_file_size) |
