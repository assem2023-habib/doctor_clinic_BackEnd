# Create Role

`POST /api/v1/roles`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` (admin) |
| Accept | `application/json` |
| Content-Type | `application/json` |

## Body
```json
{
    "name": "Nurse",
    "slug": "nurse",
    "description": "Nursing staff",
    "permissions": ["appointments.view", "patients.view"]
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | ✅ | اسم الرتبة |
| `slug` | string | ✅ | المعرف الفريد (unique) |
| `description` | string | ❌ | وصف الرتبة |
| `permissions` | array | ❌ | array of permission slugs |

## Response `201`
```json
{
    "success": true,
    "message": "Role created successfully",
    "data": { "id": "uuid", "name": "Nurse", "slug": "nurse", ... }
}
```

## Errors
| Status | Message |
|--------|---------|
| `422` | Validation errors (slug already exists, permission not found) |
