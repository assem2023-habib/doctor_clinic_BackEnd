# Show Permission

`GET /api/v1/permissions/{permission}`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` |
| Accept | `application/json` |

## Response `200`
```json
{
    "success": true,
    "message": "Permission retrieved successfully",
    "data": {
        "id": "uuid",
        "name": "View Appointments",
        "slug": "appointments.view",
        "description": null,
        "group": "Appointments",
        "guard_name": "api",
        "created_at": "...",
        "updated_at": "..."
    }
}
```
