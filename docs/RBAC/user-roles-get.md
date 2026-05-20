# Get User Roles

`GET /api/v1/users/{user}/roles`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` |
| Accept | `application/json` |

## Response `200`
```json
{
    "success": true,
    "message": "User roles retrieved successfully",
    "data": [
        {
            "id": "uuid",
            "name": "Doctor",
            "slug": "doctor",
            "description": null,
            "guard_name": "api",
            "is_system": true,
            "permissions": [...],
            "users_count": 3,
            "created_at": "...",
            "updated_at": "..."
        }
    ]
}
```
