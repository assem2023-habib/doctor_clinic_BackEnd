# Sync Role Permissions

`POST /api/v1/roles/{role}/permissions`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` (admin) |
| Accept | `application/json` |
| Content-Type | `application/json` |

## Body
```json
{
    "permissions": ["appointments.view", "appointments.create", "patients.view"]
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `permissions` | array | ✅ | array of permission slugs |

## Response `200`
```json
{
    "success": true,
    "message": "Permissions synced successfully",
    "data": { "id": "uuid", "name": "Doctor", "permissions": [...], ... }
}
```

## ملاحظات
- هذه العملية **تستبدل** كل الصلاحيات الحالية بالجديدة (sync = حذف القديم + إضافة الجديد)
