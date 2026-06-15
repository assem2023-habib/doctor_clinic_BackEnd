# Files Domain

> Manages file uploads (direct & chunked), signed resumable downloads, and access control for medical records. Built with SOLID principles using Strategy Pattern (storage) and Chain of Responsibility (access).

## Endpoints

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| POST | `/v1/files` | `auth:api,active` | Direct file upload |
| POST | `/v1/files/init` | `auth:api,active` | Initialize chunked upload |
| POST | `/v1/files/{file}/chunk` | `auth:api,active` | Upload a single chunk |
| POST | `/v1/files/{file}/complete` | `auth:api,active` | Assemble chunks into final file |
| GET | `/v1/files` | `auth:api,active` | List files (filter by `?mine=1`) |
| GET | `/v1/files/{file}` | `auth:api,active` | Show file details |
| DELETE | `/v1/files/{file}` | `auth:api,active` | Soft delete a file |
| POST | `/v1/files/{file}/download-link` | `auth:api,active` | Generate signed download URL |
| GET | `/files/{file}/download` | `signed` | Download file (signed, resumable) |

## Architecture

```
FileController
 ├── store()                → StoreFileAction                (StoreFileData DTO)
 ├── init()                 → InitChunkedUploadAction         (FileData DTO)
 ├── uploadChunk()          → UploadChunkAction
 ├── completeUpload()       → AssembleChunksAction
 ├── index()                → direct query (scope by role)
 ├── show()                 → FileResource
 ├── destroy()              → DeleteFileAction
 ├── requestDownloadLink()  → RequestDownloadLinkAction
 └── download()             → serve file (signed, Range support)
```

- **Model:** `File` (UUID v7, SoftDeletes, belongs to User + MedicalRecord)
- **FileDownload:** tracks each download (user, ip, user_agent, timestamp)
- **Enums:** `FileCategoryEnum` (document, lab_result, xray, prescription, report, other), `StorageDiskEnum` (local), `FileUploadStatusEnum` (pending, uploading, completed, failed)
- **Storage Strategy:** `FileStorageInterface` → `LocalFileStorage` (local disk); `FileStorageService` resolves driver by disk name
- **Access Chain:** `OwnerHandler → TreatingDoctorHandler → SupervisorDoctorHandler → AdminHandler`
- **Chunk Storage:** `ChunkStorageService` stores/assembles/cleans up chunks on local disk under `chunks/{uploadId}/`
- **Config:** `config/files.php` — max_file_size=20480KB, chunk_size=5120KB, allowed_mime_types, download_ttl_minutes
- **Constraints:** max 20MB total, allowed MIME types list, SHA256 checksum optional, storage path relative only
- **Download:** signed URL via `URL::temporarySignedRoute`, served with `BinaryFileResponse` & `Accept-Ranges: bytes`
