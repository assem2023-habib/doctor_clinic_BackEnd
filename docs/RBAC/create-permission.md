# Create Permission

`POST /api/v1/permissions`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` (admin) |
| Accept | `application/json` |
| Content-Type | `application/json` |

## Body
```json
{
    "name": "Export Reports",
    "slug": "reports.export",
    "description": "Ability to export system reports",
    "group": "Reports"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | ✅ | اسم الصلاحية |
| `slug` | string | ✅ | المعرف الفريد (unique) |
| `description` | string | ❌ | وصف |
| `group` | string | ❌ | للتجميع والفلترة |

## Response `201`
```json
{
    "success": true,
    "message": "Permission created successfully",
    "data": { ... }
}
```
