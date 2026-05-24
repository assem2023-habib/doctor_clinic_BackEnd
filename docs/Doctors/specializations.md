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

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `name_ar` | string | required, max:255 | Arabic name |
| `name_en` | string | required, max:255 | English name |
| `description_ar` | string | nullable | Arabic description |
| `description_en` | string | nullable | English description |
| `is_active` | boolean | nullable | Active status (default: true) |

```json
{
  "name_ar": "طب القلب",
  "name_en": "Cardiology",
  "description_ar": "متخصص بأمراض القلب",
  "description_en": "Heart disease specialist",
  "is_active": true
}
```

## Response (SpecializationResource)

```json
{
  "id": "019e1d0f-...",
  "slug": "cardiology",
  "name": { "ar": "طب القلب", "en": "Cardiology" },
  "description": { "ar": "متخصص بأمراض القلب", "en": "Heart disease specialist" },
  "is_active": true,
  "doctors_count": 5,
  "created_at": "2026-05-24T00:00:00.000000Z",
  "updated_at": "2026-05-24T00:00:00.000000Z"
}
```

> `doctors_count` appears only in list/show when loaded via `withCount`.

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden (not admin for write ops) |
| 404 | Specialization not found |
| 409 | Cannot delete — has associated doctors |
| 422 | Validation failed |
