# Sync User Roles

`POST /api/v1/users/{user}/roles`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` (admin) |
| Accept | `application/json` |
| Content-Type | `application/json` |

## Body
```json
{
    "roles": ["doctor", "receptionist"]
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `roles` | array | ✅ | array of role slugs |

## Response `200`
```json
{
    "success": true,
    "message": "User roles synced successfully",
    "data": [
        {
            "id": "uuid",
            "name": "Doctor",
            "slug": "doctor",
            "permissions": [...],
            ...
        }
    ]
}
```

## ملاحظات
- هذه العملية **تستبدل** كل رتب المستخدم بالجديدة (sync)
