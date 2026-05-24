# Show & Delete Image

> Serve the raw image binary or delete an image record + file.

## Route Information

| Method | Path | Middleware | Description |
|--------|------|------------|-------------|
| GET | `/v1/images/{image}` | `auth:api` | Serve image file binary |
| DELETE | `/v1/images/{image}` | `auth:api` | Delete image |

## Show Image

Serves the raw image content with the correct MIME type. Access control:

- **Admin** — can view any image
- **Other users** — can only view user images they own; non-user images (`specialization`, `country`) are publicly viewable by any authenticated user (checked via `$image->isOwnedBy($user)`)

### Ownership Check

```php
// Image.php
public function isOwnedBy(\App\Models\User $user): bool
{
    if ($this->imageable_type === 'user') {
        return $this->imageable_id === $user->id;
    }
    return true; // non-user images are public
}
```

### Response

Returns raw binary with `Content-Type` header matching the file's MIME type.

```
HTTP/1.1 200 OK
Content-Type: image/jpeg
Content-Length: 54321

<binary image data>
```

---

## Delete Image

### Action: `DeleteImageAction`

```php
Storage::disk('local')->delete($image->getRawOriginal('url'));
$image->delete();
```

Deletes both the file from disk and the database record.

### Response

```json
{
  "success": true,
  "message": "Image deleted successfully",
  "data": null
}
```

Status: **204 No Content**

## Sequence Diagram

```
Client     ImageController    Storage/Model
  │              │                │
  │── GET /images/{id} ──>│       │
  │              │── auth check   │
  │              │── ownership    │
  │              │── get file ───>│
  │              │<── binary ─────│
  │<── 200 binary │                │
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden (not admin and not owner) |
| 404 | Image not found or file missing from disk |
