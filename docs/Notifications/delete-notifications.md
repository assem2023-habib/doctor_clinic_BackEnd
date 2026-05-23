# Delete Notifications

> حذف إشعار واحد أو عدة إشعارات أو كل الإشعارات. الحذف يتم فقط من pivot `notification_user` (فصل المستخدم عن الإشعار) دون حذف سجل الإشعار نفسه.

---

## Route Information

| Method | Path | Middleware |
|--------|------|------------|
| `DELETE` | `/api/v1/notifications/{notification}` | `auth:api`, `active` |
| `DELETE` | `/api/v1/notifications` | `auth:api`, `active` |
| `DELETE` | `/api/v1/notifications/all` | `auth:api`, `active` |

---

## 1. حذف إشعار واحد — `destroy()`

### Route Parameter

| Parameter | Type | Description |
|-----------|------|-------------|
| `notification` | UUID | معرف الإشعار |

### Example Request

```
DELETE /api/v1/notifications/019e5596-4346-73e6-aaf2-005432d2cfd1
Authorization: Bearer <token>
```

### آلية العمل

```
1. Route Model Binding ← تحميل Notification
2. التحقق من ملكية المستخدم ← exists in pivot
3. if not owned → 404
4. $user->notifications()->detach($id) ← حذف من pivot فقط
5. return 200
```

### الكود (جزء من الـ Controller)

```php
public function destroy(Request $request, Notification $notification): JsonResponse
{
    $user = $request->user();

    $exists = $user->notifications()
        ->where('notification_id', $notification->id)
        ->exists();

    if (!$exists) {
        return ApiResponse::notFound(__('Notification not found'));
    }

    $user->notifications()->detach($notification->id);

    return ApiResponse::success(null, __('Notification deleted'));
}
```

### Response

#### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Notification deleted",
    "data": null
}
```

### الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `401` | `Unauthenticated` | التوكن غير صالح |
| `404` | `Notification not found` | الإشعار غير موجود أو لا يملكه المستخدم |

---

## 2. حذف إشعارات متعددة — `destroyMultiple()`

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `ids` | `array` | ✅ | مصفوفة من UUIDs للإشعارات (min: 1) |
| `ids.*` | `uuid` | ✅ | معرف الإشعار |

### Example Request

```
DELETE /api/v1/notifications
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
1. DeleteNotificationsRequest ← validation
   └── ids: required, array, min:1, each: uuid, exists:notifications
2. $user->notifications()->detach($ids) ← فصل كل الإشعارات المذكورة
3. return 200
```

### الـ Request

**الملف:** `app/Domains/Notifications/Requests/DeleteNotificationsRequest.php`

```php
class DeleteNotificationsRequest extends FormRequest
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

### الكود (جزء من الـ Controller)

```php
public function destroyMultiple(DeleteNotificationsRequest $request): JsonResponse
{
    $user = $request->user();
    $user->notifications()->detach($request->input('ids'));

    return ApiResponse::success(null, __('Notifications deleted'));
}
```

### Response

#### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Notifications deleted",
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

## 3. حذف كل الإشعارات — `destroyAll()`

### Example Request

```
DELETE /api/v1/notifications/all
Authorization: Bearer <token>
```

### آلية العمل

```
1. $user->notifications()->detach() ← فصل كل الإشعارات (بدون IDs = كل الـ pivot)
2. return 200
```

### الكود (جزء من الـ Controller)

```php
public function destroyAll(Request $request): JsonResponse
{
    $user = $request->user();
    $user->notifications()->detach();

    return ApiResponse::success(null, __('All notifications deleted'));
}
```

### Response

#### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "All notifications deleted",
    "data": null
}
```

### الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `401` | `Unauthenticated` | التوكن غير صالح |

---

## ملاحظات مهمة

| المعلومة | التفصيل |
|----------|---------|
| **نوع الحذف** | `detach()` — يحذف فقط من `notification_user` وليس من `notifications` |
| **الأمان** | حتى لو أرسل المستخدم IDs لا يملكها، `detach()` يتجاهلها ببساطة |
| **عدم الرجوع** | بعد الحذف، لا يمكن استرجاع الإشعار (يحتاج إعادة إرسال من النظام) |
| **تأثير على المستخدمين الآخرين** | لا يوجد — كل مستخدم لديه pivot مستقل |
