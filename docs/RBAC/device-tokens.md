# Update Device Token (FCM)

`POST /api/v1/device-tokens`

## الـ Headers
| Key | Value |
|-----|-------|
| Authorization | `Bearer {{token}}` |
| Accept | `application/json` |
| Content-Type | `application/json` |

## Body
```json
{
    "token": "fcm-device-token-string"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `token` | string | ✅ | FCM device token (max 500 chars) |

## Response `200`
```json
{
    "success": true,
    "message": "Device token updated successfully",
    "data": {
        "device_tokens": ["token1", "token2"]
    }
}
```

## ملاحظات
- التوكين يضاف إلى مصفوفة `device_tokens` في جدول `users`
- إذا كان التوكين موجوداً مسبقاً، لا يتم تكراره (unique per user)
- يستخدم هذا التوكين لإرسال الإشعارات عبر FCM
