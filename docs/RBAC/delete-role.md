# Delete Role

`DELETE /api/v1/roles/{role}`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` (admin) |
| Accept | `application/json` |

## Response `204` (No Content)

## ملاحظات
- الأدوار الـ system (`super-admin`, `admin`, `doctor`, `patient`) لا يمكن حذفها — سترجع `403 Forbidden`
