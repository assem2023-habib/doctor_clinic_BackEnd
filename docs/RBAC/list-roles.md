# List Roles

`GET /api/v1/roles`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` |
| Accept | `application/json` |

## Parameters (Query)
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | int | 20 | عدد النتائج (max 100) |
| `search` | string | — | بحث في name و slug |

## Response `200`
```json
{
    "success": true,
    "message": "Roles retrieved successfully",
    "data": [
        {
            "id": "uuid",
            "name": "Super Admin",
            "slug": "super-admin",
            "description": null,
            "guard_name": "api",
            "is_system": true,
            "permissions": [...],
            "users_count": 1,
            "created_at": "2026-05-20T10:00:00.000000Z",
            "updated_at": "2026-05-20T10:00:00.000000Z"
        }
    ],
    "pagination": { "current_page": 1, "per_page": 20, "total": 5, ... }
}
```
