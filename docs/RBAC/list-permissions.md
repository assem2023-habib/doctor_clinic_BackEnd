# List Permissions

`GET /api/v1/permissions`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` |
| Accept | `application/json` |

## Parameters (Query)
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | int | 50 | عدد النتائج (max 200) |
| `group` | string | — | فلترة حسب المجموعة (Appointments, Patients, RBAC, Locations) |
| `search` | string | — | بحث في name و slug |

## Response `200`
```json
{
    "success": true,
    "message": "Permissions retrieved successfully",
    "data": [
        {
            "id": "uuid",
            "name": "View Appointments",
            "slug": "appointments.view",
            "description": null,
            "group": "Appointments",
            "guard_name": "api",
            "created_at": "...",
            "updated_at": "..."
        }
    ],
    "pagination": { "current_page": 1, "per_page": 50, "total": 28, ... }
}
```
