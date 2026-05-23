# Notifications Domain — التوثيق الكامل

> نظام الإشعارات متعدد القنوات (Log, Database, Firebase FCM, WebSocket, Socket.IO). يدعم إرسال الإشعارات عبر `NotificationManager` وإدارتها (قراءة/حذف) عبر API للمستخدم المصادق.

---

## قائمة الـ Endpoints

| # | Method | Endpoint | Auth | الوصف |
|---|--------|----------|------|-------|
| 1 | `GET` | `/api/v1/notifications` | ✅ Bearer | [قائمة إشعاراتي + unread_count](#list-get) |
| 2 | `GET` | `/api/v1/notifications/{notification}` | ✅ Bearer | [عرض إشعار واحد](#list-get) |
| 3 | `POST` | `/api/v1/notifications/{notification}/read` | ✅ Bearer | [قراءة إشعار واحد](#read-notifications) |
| 4 | `POST` | `/api/v1/notifications/read` | ✅ Bearer | [قراءة إشعارات متعددة](#read-notifications) |
| 5 | `POST` | `/api/v1/notifications/read-all` | ✅ Bearer | [قراءة كل الإشعارات](#read-notifications) |
| 6 | `DELETE` | `/api/v1/notifications/{notification}` | ✅ Bearer | [حذف إشعار واحد](#delete-notifications) |
| 7 | `DELETE` | `/api/v1/notifications` | ✅ Bearer | [حذف إشعارات متعددة](#delete-notifications) |
| 8 | `DELETE` | `/api/v1/notifications/all` | ✅ Bearer | [حذف كل الإشعارات](#delete-notifications) |

جميع الـ endpoints تتطلب `auth:api` و `active` (المستخدم نشط).

---

## آلية العمل

```
NotificationManager::send('event.name', $data)
        ↓
توزيع على القنوات المفعّلة حسب config('notification.events')
        ↓
┌──────────────┬──────────────┬──────────────┬──────────────┐
│  LogChannel  │DatabaseChannel│FirebaseChannel│WebSocket/etc│
└──────────────┴──────────────┴──────────────┘──────────────┘
        ↓
DatabaseChannel ← إنشاء سجل في notifications + ربطه بالمستخدمين في notification_user
        ↓
المستخدم يستلم الإشعار عبر API ← قراءة / حذف عبر endpoints الإدارة
```

## الأنماط المعمارية

| النمط | أين يستخدم |
|-------|-----------|
| **Action Pattern** | 3 Actions: `MarkNotificationReadAction`, `MarkMultipleNotificationsReadAction`, `MarkAllNotificationsReadAction` |
| **DTO Pattern** | `NotificationData` — topic, title, body, userIds, type |
| **Channel Pattern** | `NotificationChannelInterface` مع 5 تطبيقات (Log, Database, Firebase, WebSocket, SocketIO) |
| **Manager Pattern** | `NotificationManager` — يقوم بتوزيع الحدث على القنوات المفعّلة |
| **Resource Pattern** | `NotificationResource` — تنسيق الاستجابة مع pivot `read_at` |
| **Request Pattern** | `MarkNotificationsReadRequest`, `DeleteNotificationsRequest` للتحقق من صحة IDs |

---

## هيكل قاعدة البيانات

```
notifications
├── id (UUID v7)
├── topic (string) ← e.g. appointment.requested
├── title (string)
├── body (json) ← payload حسب نوع الحدث
└── timestamps

notification_user (pivot)
├── notification_id (FK → notifications)
├── user_id (FK → users)
├── read_at (timestamp, nullable) ← null = غير مقروء
├── created_at
└── updated_at
```

### العلاقات

```php
// Notification Model
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class)->withPivot('read_at')->withTimestamps();
}

// User Model
public function notifications()
{
    return $this->belongsToMany(Notification::class)
        ->withPivot('read_at')
        ->withTimestamps();
}
```

---

## هيكل المجلدات

```
app/Domains/Notifications/
├── Actions/
│   ├── MarkNotificationReadAction.php
│   ├── MarkMultipleNotificationsReadAction.php
│   └── MarkAllNotificationsReadAction.php
├── Channels/
│   ├── DatabaseChannel.php
│   ├── FirebaseChannel.php
│   ├── LogChannel.php
│   ├── SocketIOChannel.php
│   └── WebSocketChannel.php
├── Contracts/
│   └── NotificationChannelInterface.php
├── Controllers/
│   └── NotificationController.php
├── DTOs/
│   └── NotificationData.php
├── Models/
│   └── Notification.php
├── Providers/
│   └── NotificationServiceProvider.php
├── Requests/
│   ├── MarkNotificationsReadRequest.php
│   └── DeleteNotificationsRequest.php
├── Resources/
│   └── NotificationResource.php
└── Services/
    ├── FirebaseService.php
    ├── FirebaseRtdbService.php
    └── NotificationManager.php
```

---

## أحداث الإشعارات (Events)

يتم تعريف الأحداث في `config/notification.php`:

| الحدث | القنوات المفعلة |
|-------|----------------|
| `appointment.requested` | log, database, firebase, websocket, socketio |
| `appointment.time_set` | log, database, firebase, websocket, socketio |
| `appointment.accepted` | log, database, firebase, websocket, socketio |
| `appointment.rejected` | log, database, firebase, websocket, socketio |
| `appointment.cancelled` | log, database, firebase, websocket, socketio |
| `appointment.in_progress` | log, database, firebase, websocket, socketio |
| `appointment.completed` | log, database, firebase, websocket, socketio |
| `appointment.alternative_suggested` | log, database, firebase, websocket, socketio |
| `login.new_device` | log, database, firebase, websocket, socketio |
| `login.suspicious_activity` | log, database, firebase, websocket, socketio |

---

## الفروقات: إدارة الإشعارات vs إرسال الإشعارات

| الخاصية | إدارة الإشعارات (هذا الـ Domain) | إرسال الإشعارات |
|----------|----------------------------------|----------------|
| **الهدف** | قراءة/حذف الإشعارات من قبل المستخدم | توليد وإرسال الإشعارات عند حدث |
| **المسار** | `Notifications/Controllers/`, `Notifications/Actions/` | `Notifications/Services/`, `Notifications/Channels/` |
| **الـ API** | 8 endpoints للمستخدم المصادق | `NotificationManager::send()` يُستدعى من الـ Actions الأخرى |
| **الاستخدام** | `GET /v1/notifications` ← قائمتي | `$this->notificationManager->send('appointment.requested', new NotificationData(...))` |
