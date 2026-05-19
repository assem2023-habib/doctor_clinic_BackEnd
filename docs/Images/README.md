# Images Domain

> Manages image uploads, serving, and deletion. Uses Intervention Image for optimization, polymorphic `imageable` relationship, and replaces existing images on re-upload.

## Endpoints

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| GET | `/v1/images/{image}` | `auth:api` | Serve image file binary |
| POST | `/v1/images` | `auth:api`, `image.content` | Upload an image |
| DELETE | `/v1/images/{image}` | `auth:api` | Delete an image |

## Architecture

```
ImageController
 ├── store() → UploadImageAction (UploadImageData DTO)
 ├── show()  → serve raw file with Content-Type header (ownership check)
 └── destroy() → DeleteImageAction (delete file + record)
```

- **Model:** `Image` (UUID v7, polymorphic `imageable` — morphs to User, Country, etc.)
- **Types:** `ImageTypeEnum` — `user` (max 2048KB), `country` (max 2048KB) — configurable via `config/images.max_size`
- **Accessor:** `url` attribute returns full URL (`/api/v1/images/{id}`) instead of storage path
- **Upload Action:** replaces existing image for same `imageable_type + imageable_id`, optimizes to 80% quality via Intervention Image
- **Storage:** local disk, path: `uploads/{type}/{imageable_id}/{uuid}.{ext}`
- **Constraints:** jpg/jpeg/png/webp only, max size per type
- **`image.content` middleware:** validates base64 content before the request reaches the controller
