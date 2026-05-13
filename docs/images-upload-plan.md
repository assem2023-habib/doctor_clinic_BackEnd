# Image Upload System — Implementation Plan

## 1. Objective

Allow users and admins to upload images (profile pictures, country flags, etc.) and store them locally. The database stores only the **relative path** — the full URL is constructed at runtime using an environment variable.

---

## 2. Environment & Config

### 2.1 Env Variable

Add to `.env` / `.env.example`:

```
IMAGES_STORAGE_URL="${APP_URL}/storage"
```

Or keep using `APP_URL` directly from the existing `config/filesystems.php` public disk URL:

```php
'url' => env('IMAGES_STORAGE_URL', env('APP_URL', 'http://localhost').'/storage'),
```

### 2.2 Filesystem Disk

Use the existing `public` disk (`storage/app/public/`).

Run `php artisan storage:link` (already configured in `config/filesystems.php` `links` array).

---

## 3. Storage Directory Structure

```
storage/app/public/uploads/
├── users/
│   └── {user_uuid}/
│       └── avatar.jpg
├── countries/
│   └── {country_uuid}/
│       └── flag.jpg
└── temp/
    └── ...
```

### Database `url` column stores:

```
uploads/users/{uuid}/avatar.jpg
uploads/countries/{uuid}/flag.jpg
```

### Full URL (constructed at runtime):

```
https://example.com/storage/uploads/users/{uuid}/avatar.jpg
```

---

## 4. What to Create

### 4.1 `app/Domains/Images/` Domain

| Layer | File | Purpose |
|-------|------|---------|
| **Model** | `app/Domains/Images/Models/Image.php` | Move `app/Models/Image.php` here, add `url()` accessor that prepends `IMAGES_STORAGE_URL` |
| **DTO** | `app/Domains/Images/DTOs/UploadImageData.php` | DTO: `imageableType`, `imageableId`, `file` |
| **Action** | `app/Domains/Images/Actions/UploadImageAction.php` | Validate mime/size, store file, create DB record |
| **Action** | `app/Domains/Images/Actions/DeleteImageAction.php` | Delete file + DB record |
| **Request** | `app/Domains/Images/Requests/UploadImageRequest.php` | Validation: `file` required, image, max:2MB, mimes:jpg,png,webp |
| **Resource** | `app/Domains/Images/Resources/ImageResource.php` | Returns `id`, `url` (full), `type`, `imageable_id` |

### 4.2 Controller

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Api/V1/Image/ImageController.php` | `store()` — upload image, `destroy()` — delete image |

### 4.3 Routes

Add to `routes/api.php`:

```php
Route::middleware('auth:api')->prefix('v1/images')->group(function () {
    Route::post('/', [ImageController::class, 'store']);
    Route::delete('/{image}', [ImageController::class, 'destroy']);
});
```

### 4.4 Swagger

| File | Purpose |
|------|---------|
| `app/Swagger/Controllers/Images/ImageControllerDoc.php` | Endpoint annotations |
| `app/Swagger/Schemas/ImageResourceSchema.php` | Schema with `id`, `url`, `type`, `imageable_id` |

### 4.5 Update `ImageTypeEnum`

Add new types as needed:

```php
case Doctor = 'doctor';
case Patient = 'patient';
case Receptionist = 'receptionist';
```

---

## 5. Model `url` Accessor

In the Image model (or a dedicated `Image` domain model), add an accessor:

```php
public function getUrlAttribute(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    return config('filesystems.disks.public.url') . '/' . $value;
}
```

This way:
- **Database**: `uploads/users/xxx/avatar.jpg`
- **API response**: `http://localhost:8000/storage/uploads/users/xxx/avatar.jpg`

---

## 6. Image Validation Rules

| Rule | Value |
|------|-------|
| Max file size | 2MB (for avatars), 5MB (for flags) |
| Allowed MIME types | `image/jpeg`, `image/png`, `image/webp` |
| Dimensions (optional) | User avatar: min 200×200, max 2048×2048 |

---

## 7. Upload Flow

```
Client → POST /api/v1/images (multipart/form-data)
  ├── file: UploadedFile
  ├── type: "user" | "country"  (ImageTypeEnum)
  └── imageable_id: UUID

UploadImageAction::execute()
  1. Validate file (mime, size, dimensions)
  2. Generate filename: {uuid}.{ext}
  3. Store to: storage/app/public/uploads/{type}/{imageable_id}/{filename}
  4. Create DB record: url = "uploads/{type}/{imageable_id}/{filename}"
  5. If image already exists for this (type, imageable_id) → delete old one
  6. Return Image resource

Response: 201 {status, message, data: {id, url, type, imageable_id}}
```

---

## 8. Delete Flow

```
Client → DELETE /api/v1/images/{image} (auth)

DeleteImageAction::execute()
  1. Delete file from disk
  2. Delete DB record

Response: 204
```

---

## 9. Serving Images

Use Laravel's `php artisan storage:link`:

```
public/storage → storage/app/public
```

Image accessible at:

```
http://localhost:8000/storage/uploads/users/{uuid}/avatar.jpg
```

---

## 10. Implementation Order

| Step | Task | Files |
|------|------|-------|
| 1 | Add `IMAGES_STORAGE_URL` to `.env` / `.env.example` | `.env`, `.env.example` |
| 2 | Create `UploadImageData` DTO | `app/Domains/Images/DTOs/UploadImageData.php` |
| 3 | Create `UploadImageRequest` (validation) | `app/Domains/Images/Requests/UploadImageRequest.php` |
| 4 | Create `UploadImageAction` | `app/Domains/Images/Actions/UploadImageAction.php` |
| 5 | Create `DeleteImageAction` | `app/Domains/Images/Actions/DeleteImageAction.php` |
| 6 | Create `ImageResource` | `app/Domains/Images/Resources/ImageResource.php` |
| 7 | Move Image model to `app/Domains/Images/Models/Image.php` | Modify + Delete old `app/Models/Image.php` |
| 8 | Create `ImageController` | `app/Http/Controllers/Api/V1/Image/ImageController.php` |
| 9 | Add routes for POST + DELETE | `routes/api.php` |
| 10 | Create Swagger annotations + schema | `app/Swagger/Controllers/Images/`, `app/Swagger/Schemas/` |
| 11 | Update `AppServiceProvider` morphMap | `app/Providers/AuthServiceProvider.php` |
| 12 | Run `php artisan storage:link` | Terminal |
| 13 | Run `php artisan test` | Verify all tests pass |
| 14 | Commit + Push to GitHub | Git |

---

## 11. Future Considerations

- **S3/CDN**: Swap disk driver from `local` to `s3`; `IMAGES_STORAGE_URL` becomes the CDN URL
- **Thumbnails**: Use `intervention/image` or Glide to auto-generate thumbnails
- **Image optimization**: Compress on upload with `spatie/image-optimizer`
- **Cleanup**: Artisan command to delete orphaned images (no DB record)
- **Multiple images per object**: Change morphOne → morphMany when needed
