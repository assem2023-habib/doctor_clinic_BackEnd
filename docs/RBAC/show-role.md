# Show Role

`GET /api/v1/roles/{role}`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` |
| Accept | `application/json` |

## Response `200`
```json
{
    "success": true,
    "message": "Role retrieved successfully",
    "data": {
        "id": "uuid",
        "name": "Doctor",
        "slug": "doctor",
        "description": null,
        "guard_name": "api",
        "is_system": true,
        "permissions": [
            {
                "id": "uuid",
                "name": "View Appointments",
                "slug": "appointments.view",
                "group": "Appointments",
                "guard_name": "api",
                "created_at": "...",
                "updated_at": "..."
            }
        ],
        "users_count": 3,
        "created_at": "...",
        "updated_at": "..."
    }
}
```
