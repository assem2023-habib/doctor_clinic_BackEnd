# Mark Notifications as Read

> تعليم إشعار واحد أو عدة إشعارات أو كل الإشعارات كمقروءة.

---

## Route Information

| Method | Path | Middleware |
|--------|------|------------|
| `POST` | `/api/v1/notifications/{notification}/read` | `auth:api`, `active` |
| `POST` | `/api/v1/notifications/read` | `auth:api`, `active` |
| `POST` | `/api/v1/notifications/read-all` | `auth:api`, `active` |

---

## 1. قراءة إشعار واحد — `markAsRead()`

### Route Parameter

| Parameter | Type | Description |
|-----------|------|-------------|
| `notification` | UUID | معرف الإشعار |

### Example Request

```
POST /api/v1/notifications/019e5596-4346-73e6-aaf2-005432d2cfd1/read
Authorization: Bearer <token>
```

### آلية العمل

```
1. Route Model Binding ← تحميل Notification
2. $user->notifications()->find($id) ← التحقق من الملكية
3. if not found → 404
4. MarkNotificationReadAction::execute($user, $notification)
   └── DB::table('notification_user')->where(user_id, notification_id)->update(read_at = now())
5. return 200
```

### الـ Action

**الملف:** `app/Domains/Notifications/Actions/MarkNotificationReadAction.php`

```php
class MarkNotificationReadAction
{
    public function execute(User $user, Notification $notification): void
    {
        $user->notifications()->updateExistingPivot($notification->id, [
            'read_at' => now(),
        ]);
    }
}
```

### Response

#### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Notification marked as read",
    "data": null
}
```

### الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `401` | `Unauthenticated` | التوكن غير صالح |
| `404` | `Notification not found` | الإشعار غير موجود أو لا يملكه المستخدم |

---

## 2. قراءة إشعارات متعددة — `markMultipleAsRead()`

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `ids` | `array` | ✅ | مصفوفة من UUIDs للإشعارات (min: 1) |
| `ids.*` | `uuid` | ✅ | معرف الإشعار |

### Example Request

```
POST /api/v1/notifications/read
Authorization: Bearer <token>
Content-Type: application/json

{
    "ids": [
        "019e5596-4346-73e6-aaf2-005432d2cfd1",
        "019e5596-4346-73e6-aaf2-005432d2cfd2",
        "019e5596-4346-73e6-aaf2-005432d2cfd3"
    ]
}
```

### آلية العمل

```
1. MarkNotificationsReadRequest ← validation
   └── ids: required, array, min:1, each: uuid, exists:notifications
2. MarkMultipleNotificationsReadAction::execute($user, $ids)
   └── DB::table('notification_user')
       ->where(user_id)
       ->whereIn(notification_id, $ids)
       ->whereNull(read_at)
       ->update(read_at = now())
3. return 200
```

### الـ Request

**الملف:** `app/Domains/Notifications/Requests/MarkNotificationsReadRequest.php`

```php
class MarkNotificationsReadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'uuid', 'exists:notifications,id'],
        ];
    }
}
```

### الـ Action

**الملف:** `app/Domains/Notifications/Actions/MarkMultipleNotificationsReadAction.php`

```php
class MarkMultipleNotificationsReadAction
{
    public function execute(User $user, array $notificationIds): void
    {
        DB::table('notification_user')
            ->where('user_id', $user->id)
            ->whereIn('notification_id', $notificationIds)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
```

### Response

#### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Notifications marked as read",
    "data": null
}
```

#### ❌ Validation Error — `422`

```json
{
    "status": 422,
    "message": "Validation failed",
    "errors": {
        "ids": ["The ids field is required."],
        "ids.0": ["The selected ids.0 is invalid."]
    }
}
```

### الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `401` | `Unauthenticated` | التوكن غير صالح |
| `422` | `Validation failed` | IDs غير صالحة أو مفقودة |

---

## 3. قراءة كل الإشعارات — `markAllAsRead()`

### Example Request

```
POST /api/v1/notifications/read-all
Authorization: Bearer <token>
```

### آلية العمل

```
1. MarkAllNotificationsReadAction::execute($user)
   └── DB::table('notification_user')
       ->where(user_id)
       ->whereNull(read_at)
       ->update(read_at = now())
2. return 200
```

### الـ Action

**الملف:** `app/Domains/Notifications/Actions/MarkAllNotificationsReadAction.php`

```php
class MarkAllNotificationsReadAction
{
    public function execute(User $user): void
    {
        DB::table('notification_user')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
```

### Response

#### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "All notifications marked as read",
    "data": null
}
```

### الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `401` | `Unauthenticated` | التوكن غير صالح |
