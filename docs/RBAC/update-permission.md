# Update Permission

`PUT /api/v1/permissions/{permission}`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` (admin) |
| Accept | `application/json` |
| Content-Type | `application/json` |

## Body
```json
{
    "name": "Export Advanced Reports",
    "slug": "reports.export.advanced",
    "description": "Ability to export advanced system reports",
    "group": "Reports"
}
```

## Response `200`
```json
{
    "success": true,
    "message": "Permission updated successfully",
    "data": { ... }
}
```
