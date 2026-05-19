# Upload Image

> Upload an image file associated with a polymorphic parent (e.g. User, Country). Replaces any existing image for the same parent.

## Route Information

- **Method:** `POST`
- **Path:** `/v1/images`
- **Middleware:** `auth:api`, `image.content`

## Request

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `file` | file | required, image, max per type, jpg/jpeg/png/webp | Image file |
| `type` | string | required, enum: `user`, `country` | Image type (polymorphic) |
| `imageable_id` | string | required | UUID of the parent entity |

### Example

```
POST /v1/images
Content-Type: multipart/form-data

file=@avatar.jpg
type=user
imageable_id=0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d
```

## DTO: `UploadImageData`

```php
private function __construct(
    public readonly UploadedFile $file,
    public readonly ImageTypeEnum $type,
    public readonly string $imageableId,
) {}
```

## Action: `UploadImageAction`

```php
// Replace existing image for this parent
$existing = Image::where('imageable_type', $data->type->value)
    ->where('imageable_id', $data->imageableId)->first();
if ($existing) {
    Storage::disk('local')->delete($existing->getRawOriginal('url'));
    $existing->delete();
}

// Store with UUID filename
$filename = Uuid::uuid7()->toString() . '.' . $data->file->extension();
$relativePath = $data->file->storeAs(
    'uploads/' . $data->type->value . '/' . $data->imageableId,
    $filename, 'local'
);

// Optimize with Intervention Image (80% quality)
InterventionImage::decodePath(Storage::disk('local')->path($relativePath))
    ->save(quality: 80);

// Create DB record
return Image::create([
    'url' => $relativePath,
    'imageable_type' => $data->type->value,
    'imageable_id' => $data->imageableId,
]);
```

## Response

```json
{
  "success": true,
  "message": "Image uploaded successfully",
  "data": {
    "id": "0194f1e2-9a0b-1f23-2c6d-3e4f5a6b7c8d",
    "url": "http://localhost/api/v1/images/0194f1e2-9a0b-1f23-2c6d-3e4f5a6b7c8d",
    "type": "App\\Models\\User",
    "imageable_id": "0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d",
    "created_at": "2026-05-19T10:00:00.000000Z"
  }
}
```

## Sequence Diagram

```
Client     image.content MW     ImageController    UploadImageAction    Storage    DB
  │              │                  │                    │                │        │
  │── POST /images ──>│            │                    │                │        │
  │              │── validate ─────│                    │                │        │
  │              │── pass ────────>│                    │                │        │
  │              │                  │── UploadImageData │                │        │
  │              │                  │── execute() ─────>│                │        │
  │              │                  │                    │── delete old  │        │
  │              │                  │                    │── store file ─>│        │
  │              │                  │                    │── optimize ───>│        │
  │              │                  │                    │── create rec ──────────>│
  │              │                  │<── Image ─────────│                │        │
  │              │<── 201 Created ──│                    │                │        │
  │<── 201 OK ───│                  │                    │                │        │
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 422 | Invalid file (type, size, mime), missing fields, or invalid `imageable_id` |
