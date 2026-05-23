# List & Get Notifications

> عرض قائمة إشعارات المستخدم مع عدد غير المقروء، وعرض إشعار واحد (مع تعليمه كمقروء تلقائياً).

---

## Route Information

| Method | Path | Middleware |
|--------|------|------------|
| `GET` | `/api/v1/notifications` | `auth:api`, `active` |
| `GET` | `/api/v1/notifications/{notification}` | `auth:api`, `active` |

---

## 1. قائمة الإشعارات — `index()`

### Query Parameters

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `limit` | integer | 20 | 1–100 | عدد العناصر في الصفحة |
| `page` | integer | 1 | — | رقم الصفحة |

### Example Request

```
GET /api/v1/notifications?limit=10&page=1
Authorization: Bearer <token>
```

### الـ Controller

**الملف:** `app/Domains/Notifications/Controllers/NotificationController.php`

```php
public function index(Request $request): JsonResponse
{
    $user = $request->user();
    $limit = (int) $request->integer('limit', 20);

    $notifications = $user->notifications()
        ->orderBy('created_at', 'desc')
        ->paginate(min($limit, 100));

    $unreadCount = $user->notifications()
        ->wherePivotNull('read_at')
        ->count();

    return ApiResponse::success(
        data: [
            'notifications' => NotificationResource::collection($notifications),
            'unread_count' => $unreadCount,
        ],
        message: __('Notifications retrieved successfully'),
        pagination: ApiResponse::pagination($notifications),
    );
}
```

### Response

#### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Notifications retrieved successfully",
    "data": {
        "notifications": [
            {
                "id": "019e5596-4346-73e6-aaf2-005432d2cfd1",
                "topic": "appointment.requested",
                "title": "New Appointment Request",
                "body": {
                    "appointment_id": "019e5596-4346-73e6-aaf2-005432d2cfd2",
                    "doctor_id": "019e5596-4346-73e6-aaf2-005432d2cfd3",
                    "patient_id": "019e5596-4346-73e6-aaf2-005432d2cfd4",
                    "reason": "Checkup"
                },
                "is_read": false,
                "read_at": null,
                "created_at": "2026-05-23T10:00:00.000000Z",
                "updated_at": "2026-05-23T10:00:00.000000Z"
            }
        ],
        "unread_count": 5
    },
    "meta": {
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "limit": 10,
            "total": 42,
            "hasNextPage": true,
            "hasPreviousPage": false
        }
    }
}
```

### الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `401` | `Unauthenticated` | التوكن غير صالح أو منتهي |

---

## 2. عرض إشعار واحد — `show()`

### Route Parameter

| Parameter | Type | Description |
|-----------|------|-------------|
| `notification` | UUID | معرف الإشعار |

### Example Request

```
GET /api/v1/notifications/019e5596-4346-73e6-aaf2-005432d2cfd1
Authorization: Bearer <token>
```

### آلية العمل

```
1. Route Model Binding ← تحميل Notification
2. $user->notifications()->find($id) ← التحقق من ملكية المستخدم
3. if not found → 404
4. if read_at == null → MarkNotificationReadAction ← تعليم كمقروء
5. NotificationResource → 200
```

### الـ Controller

```php
public function show(Request $request, Notification $notification): JsonResponse
{
    $user = $request->user();

    $notification = $user->notifications()->find($notification->id);

    if (!$notification) {
        return ApiResponse::notFound(__('Notification not found'));
    }

    if (is_null($notification->pivot?->read_at)) {
        $this->markReadAction->execute($user, $notification);
        $notification->pivot->read_at = now();
    }

    return ApiResponse::success(
        new NotificationResource($notification),
        __('Notification retrieved successfully')
    );
}
```

### Response

#### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Notification retrieved successfully",
    "data": {
        "id": "019e5596-4346-73e6-aaf2-005432d2cfd1",
        "topic": "appointment.requested",
        "title": "New Appointment Request",
        "body": {
            "appointment_id": "019e5596-4346-73e6-aaf2-005432d2cfd2",
            "doctor_id": "019e5596-4346-73e6-aaf2-005432d2cfd3",
            "patient_id": "019e5596-4346-73e6-aaf2-005432d2cfd4",
            "reason": "Checkup"
        },
        "is_read": true,
        "read_at": "2026-05-23T10:05:00.000000Z",
        "created_at": "2026-05-23T10:00:00.000000Z",
        "updated_at": "2026-05-23T10:00:00.000000Z"
    }
}
```

> **ملاحظة:** عند عرض الإشعار، يتم تعليمه `is_read: true` تلقائياً.

### الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `401` | `Unauthenticated` | التوكن غير صالح |
| `404` | `Notification not found` | الإشعار غير موجود أو لا يملكه المستخدم |
