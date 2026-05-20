# Update Role

`PUT /api/v1/roles/{role}`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` (admin) |
| Accept | `application/json` |
| Content-Type | `application/json` |

## Body
```json
{
    "name": "Senior Nurse",
    "slug": "senior-nurse",
    "description": "Senior nursing staff",
    "permissions": ["appointments.view", "appointments.edit", "patients.view"]
}
```

## Response `200`
```json
{
    "success": true,
    "message": "Role updated successfully",
    "data": { ... }
}
```

## ملاحظات
- الأدوار الـ system (`is_system = true`) لا يمكن تعديلها — سترجع `403 Forbidden`
- إذا لم ترسل `permissions`، لن تتغير صلاحيات الرتبة
- إذا أرسلت `permissions: []`، سيتم إزالة كل الصلاحيات
