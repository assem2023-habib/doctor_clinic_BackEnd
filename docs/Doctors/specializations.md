# Specializations CRUD

> إدارة التخصصات الطبية — عرض، إنشاء، تحديث، حذف.

## Route Information

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| GET | `/v1/specializations` | `auth:api`, `active` | List all specializations |
| GET | `/v1/specializations/{specialization}` | `auth:api`, `active` | Get a single specialization |
| POST | `/v1/specializations` | `auth:api`, `active`, `admin` | Create a specialization |
| PUT | `/v1/specializations/{specialization}` | `auth:api`, `active`, `admin` | Update a specialization |
| DELETE | `/v1/specializations/{specialization}` | `auth:api`, `active`, `admin` | Delete a specialization (fails if has doctors) |

## List Specializations

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `limit` | integer | 20 | 1–100 | Items per page |
| `page` | integer | 1 | — | Page number |
| `search` | string | — | — | Search in `name.ar` or `name.en` (LIKE) |
| `slug` | string | — | — | Filter by slug |
| `is_active` | boolean | — | — | Filter by active status |

## Store/Update

Content-Type: `multipart/form-data`

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `name_ar` | string | required, max:255 | Arabic name |
| `name_en` | string | required, max:255 | English name |
| `description_ar` | string | nullable | Arabic description |
| `description_en` | string | nullable | English description |
| `is_active` | boolean | nullable | Active status (default: true) |
| `file` | file | nullable, image, max:2048KB, jpg/jpeg/png/webp | Specialization image |

## Response (SpecializationResource)

```json
{
  "id": "019e1d0f-...",
  "slug": "cardiology",
  "name": { "ar": "طب القلب", "en": "Cardiology" },
  "description": { "ar": "متخصص بأمراض القلب", "en": "Heart disease specialist" },
  "is_active": true,
  "doctors_count": 5,
  "image": {
    "id": "019e5936-...",
    "url": "http://localhost/api/v1/images/019e5936-...",
    "type": "specialization",
    "imageable_id": "019e1d0f-...",
    "created_at": "2026-05-24T00:00:00.000000Z"
  },
  "created_at": "2026-05-24T00:00:00.000000Z",
  "updated_at": "2026-05-24T00:00:00.000000Z"
}
```

> `doctors_count` appears only when loaded via `withCount`. `image` appears only when the relationship is loaded (always in `show`, not by default in `index`).

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden (not admin for write ops) |
| 404 | Specialization not found |
| 409 | Cannot delete — has associated doctors |
| 413 | File too large (exceeds 2048KB) |
| 422 | Validation failed |
