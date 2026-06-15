# Firebase Realtime Database (RTDB)

## Overview

يستخدم Firebase Realtime Database لمزامنة المواعيد المحجوزة (booked appointments) بشكل مباشر بين Laravel Backend و Flutter Frontend دون الحاجة لاستطلاع REST API المتكرر.

- **الهدف:** عرض مواعيد الدكتور للـ Frontend بشكل مباشر دون تأخير
- **الكتابة:** فقط من Laravel عبر Admin SDK (لا يكتب الـ Frontend مباشرة)
- **القراءة:** الـ Frontend بعد تسجيل الدخول عبر Firebase Custom Token

---

## 1. الإعدادات (Configuration)

### `.env`

```env
FCM_ENABLED=true
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FIREBASE_RTDB_URL=https://clinic-managment-9c6fe-default-rtdb.europe-west1.firebasedatabase.app
```

### ملف الاعتماديات (Service Account)

**المسار:** `storage/app/firebase/service-account.json`

يتم تحميله من Firebase Console → Project Settings → Service Accounts → Generate New Private Key.

### مصدر الإعدادات

| Config Key | Env Variable | Default | الملف |
|---|---|---|---|
| `notification.channels.firebase.credentials` | `FIREBASE_CREDENTIALS` | `storage_path('app/firebase/service-account.json')` | `config/notification.php:13` |
| `notification.channels.firebase.rtdb_url` | `FIREBASE_RTDB_URL` | — | `config/notification.php:14` |

---

## 2. تدفق Firebase Authentication

```
                          Laravel Backend                          Firebase
                               │                                      │
  ┌─────────────────────────────┼──────────────────────────────────────┼──┐
  │  Flutter Frontend           │                                      │  │
  │                             │                                      │  │
  │  1. POST /api/v1/auth/login │                                      │  │
  │     {email, password}       │                                      │  │
  │     ← Bearer token         │                                      │  │
  │                             │                                      │  │
  │  2. POST /api/v1/auth/     │                                      │  │
  │     firebase-token          │                                      │  │
  │     Authorization: Bearer   │                                      │  │
  │     ← firebase_token + uid  │                                      │  │
  │                             │                                      │  │
  │  3. signInWithCustomToken(  │                                      │  │
  │     firebase_token)         │ ──────────────────────────────────── │  │
  │                             │                                      │  │
  │  4. اقرأ RTDB مباشرة       │                                      │  │
  │     ref("doctors/{id}/     │ ──────────────────────────────────── │  │
  │       booked-appointments") │                                      │  │
  └─────────────────────────────┼──────────────────────────────────────┼──┘
                               │                                      │
```

### الفرق بين خدمتي Firebase

| الخدمة | الاستخدام | الـ Class |
|--------|-----------|-----------|
| `FirebaseService` | Auth (Custom Tokens) + Messaging (FCM) | `app/Domains/Notifications/Services/FirebaseService.php` |
| `FirebaseRtdbService` | RTDB (قراءة/كتابة/حذف) | `app/Domains/Notifications/Services/FirebaseRtdbService.php` |

كل خدمة تبني `Factory` خاص بها من نفس ملف `service-account.json`.

### الـ Endpoint

```
POST /api/v1/auth/firebase-token
Authorization: Bearer <laravel_access_token>
```

**Response:**
```json
{
    "status": 200,
    "message": "Firebase token generated successfully.",
    "data": {
        "firebase_token": "eyJhbGciOiJSUzI1NiIs...",
        "uid": "user_019e1d0f-..."
    }
}
```

**Custom Token Claims:**

| Claim | Type | مثال |
|-------|------|------|
| `uid` | `string` | `user_019e1d0f-...` |
| `user_id` | `string` | `019e1d0f-...` |
| `role` | `string` | `admin`, `patient`, `doctor,receptionist` |

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php:170-193`

> ⚠️ إذا لم يتم إعداد Firebase (`service-account.json` مفقود)، يُرجع الـ endpoint خطأ 500 مع رسالة `"Firebase not configured"`.

---

## 3. هيكل قاعدة البيانات (Database Tree)

```
/doctors/
  └── {doctorId}:                                                ← UUID (doctors.id)
       ├── doctor_name: "Jane Smith"                             ← string | null
       └── booked-appointments/
            └── {date}:                                          ← Y-m-d (مثال: "2026-06-01")
                 └── {appointmentId}:                            ← UUID (appointments.id)
                      ├── id:               "uuid"               ← string (PK)
                      ├── appointment_date: "2026-06-01"        ← string (Y-m-d)
                      ├── start_time:       "10:00"             ← string (H:i)
                      ├── end_time:         "11:00"             ← string (H:i)
                      ├── status:           "accepted"          ← string (enum)
                      ├── reason:           "Checkup"           ← string | null
                      └── notes:            "..."               ← string | null
```

### ملاحظات هيكلية

- **`doctor_name`** يُخزّن **مرة واحدة فقط** على مستوى الدكتور (`/doctors/{doctorId}/doctor_name`)، وليس داخل كل موعد
- **`booked-appointments`** مقسّم حسب **التاريخ** (`{date}`) لتسهيل جلب مواعيد يوم معين
- كل موعد له مسار فريد: `/doctors/{doctorId}/booked-appointments/{date}/{appointmentId}`
- المواعيد **تُحذف** من RTDB فور انتهائها أو إلغائها (لا تبقى كسجلات منتهية)
- **لا تُخزّن بيانات المريض الشخصية (name, phone, patient_id)** في RTDB حفاظاً على الخصوصية — Flutter يجلب بيانات المريض عبر API عند الحاجة

---

## 4. تفاصيل الحقول (Field Reference)

| الحقل | Type PHP | RTDB Type | Nullable | الحد الأقصى | المصدر |
|-------|----------|-----------|----------|-------------|--------|
| `id` | `string` (UUID) | `string` | ❌ | 36 char | `$appointment->id` |
| `appointment_date` | `string` (Y-m-d) | `string` | ✅ | 10 char | `$appointment->appointment_date->format('Y-m-d')` |
| `start_time` | `string` (H:i) | `string` | ✅ | 5 char | `$appointment->start_time->format('H:i')` |
| `end_time` | `string` (H:i) | `string` | ✅ | 5 char | `$appointment->end_time->format('H:i')` |
| `status` | `string` (enum) | `string` | ❌ | 15 char | `$appointment->status->value` |
| `reason` | `string` | `string` | ✅ | — | `$appointment->reason` |
| `notes` | `string` | `string` | ✅ | — | `$appointment->notes` |

### قيم `status` المسموح بكتابتها في RTDB

القيم التالية فقط تعتبر "Booked" ويتم مزامنتها:

| Case PHP | Value String | Booked? |
|----------|-------------|---------|
| `AppointmentStatusEnum::Set` | `"set"` | ✅ |
| `AppointmentStatusEnum::Accepted` | `"accepted"` | ✅ |
| `AppointmentStatusEnum::InProgress` | `"in_progress"` | ✅ |
| `AppointmentStatusEnum::Confirmed` | `"confirmed"` | ✅ |

القيم الأخرى (`pending`, `requested`, `rejected`, `cancelled`, `completed`) تؤدي إلى **حذف** الموعد من RTDB.

### مصدر بناء الـ Object

```php
// الملف: app/Domains/Appointments/Services/AppointmentRtdbService.php:138-148
private function buildAppointmentData(Appointment $appointment): array
{
    return [
        'id' => $appointment->id,
        'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
        'start_time' => $appointment->start_time?->format('H:i'),
        'end_time' => $appointment->end_time?->format('H:i'),
        'status' => $appointment->status->value,
        'reason' => $appointment->reason,
        'notes' => $appointment->notes,
    ];
}
```

---

## 5. قواعد المزامنة (Sync Rules)

| الإجراء | الحالة بعد الإجراء | Booked? | إجراء RTDB | شرح |
|---------|-------------------|---------|------------|------|
| `setTime()` | `Set` | ✅ Booked | **Write** | إنشاء الموعد في RTDB لأول مرة |
| `respond(accept)` | `Accepted` | ✅ Booked | **Write** | تحديث status في RTDB |
| AutoConfirm | `Accepted` | ✅ Booked | **Write** | تأكيد تلقائي بعد المهلة |
| `respond(reject)` | `Rejected` | ❌ Not Booked | **Remove** | حذف الموعد من RTDB |
| `start()` | `InProgress` | ✅ Booked | **Write** | تحديث status إلى in_progress |
| `cancel()` | `Cancelled` | ❌ Not Booked | **Remove** | حذف الموعد من RTDB |
| `complete()` | `Completed` | ❌ Not Booked | **Remove** | حذف الموعد من RTDB |
| Cleanup | متغير | حسب الحالة | **Remove** | التنظيف التلقائي بعد 24 ساعة |

### آلية العمل

```php
// AppointmentRtdbService.php:54-61
public function syncIfBooked(Appointment $appointment): void
{
    if (in_array($appointment->status, self::BOOKED_STATUSES, true)) {
        $this->syncAppointment($appointment);  // كتابة
    } else {
        $this->removeAppointment($appointment); // حذف
    }
}
```

---

## 6. خدمات RTDB (Services Architecture)

```
FirebaseService                    ← Auth (Custom Tokens) + Messaging (FCM)
│
FirebaseRtdbService               ← Low-level RTDB wrapper
│  ├── setValue(path, value)       ← كتابة قيمة في مسار
│  ├── removeValue(path)           ← حذف قيمة في مسار
│  └── getValue(path)              ← قراءة قيمة (للاستخدام الداخلي)
│
AppointmentRtdbService             ← Domain-specific (Appointments)
   ├── syncAppointment($appt)       ← بناء الـ object + كتابة في RTDB
   ├── removeAppointment($appt)     ← حذف موعد من RTDB
   ├── syncIfBooked($appt)          ← تقرير: كتابة أم حذف حسب status
   ├── removeExpiredAppointments()  ← حذف المواعيد المنتهية
   └── syncDoctorAppointments()     ← إعادة مزامنة كل مواعيد دكتور
```

### `FirebaseRtdbService`

**الملف:** `app/Domains/Notifications/Services/FirebaseRtdbService.php`

```php
class FirebaseRtdbService
{
    private ?Database $database = null;

    public function isAvailable(): bool;       // هل Firebase مهيأ؟
    public function getDatabase(): ?Database;   // الـ instance (للاستخدام المباشر)
    public function setValue(string $path, mixed $value): void;     // كتابة
    public function removeValue(string $path): void;                // حذف
    public function getValue(string $path): mixed;                  // قراءة
}
```

- يستخدم Kreait Firebase PHP SDK (`kreait/firebase-php`)
- يُسجّل كـ **singleton** في Laravel Service Container
- يقرأ الإعدادات من `config('notification.channels.firebase.*')`
- يعود بصمت (silent fail) إذا لم يكن Firebase مهيئاً (يسجل warning فقط)

### `AppointmentRtdbService`

**الملف:** `app/Domains/Appointments/Services/AppointmentRtdbService.php`

```php
class AppointmentRtdbService
{
    private const BOOKED_STATUSES = [
        AppointmentStatusEnum::Set,
        AppointmentStatusEnum::Accepted,
        AppointmentStatusEnum::InProgress,
        AppointmentStatusEnum::Confirmed,
    ];

    public function syncAppointment(Appointment $appointment): void;     // كتابة/تحديث
    public function removeAppointment(Appointment $appointment): void;    // حذف
    public function syncIfBooked(Appointment $appointment): void;         // اختيار حسب الحالة
    public function removeExpiredAppointments(): int;                     // حذف المنتهية
    public function syncDoctorAppointments(Doctor $doctor): int;          // إعادة مزامنة كل المواعيد لدكتور
}
```

**المسارات:**

| Method | RTDB Path |
|--------|-----------|
| `syncAppointment()` | `/doctors/{doctorId}/booked-appointments/{date}/{appointmentId}` |
| `removeAppointment()` | `/doctors/{doctorId}/booked-appointments/{date}/{appointmentId}` |
| doctor_name | `/doctors/{doctorId}/doctor_name` |

---

## 7. قواعد الأمان (Firebase Security Rules)

اذهب إلى **Firebase Console → Realtime Database → Rules** والصق:

```json
{
  "rules": {
    ".read": false,
    ".write": false,
    "doctors": {
      "$doctorId": {
        "doctor_name": {
          ".read": "auth !== null",
          ".write": false
        },
        "booked-appointments": {
          "$date": {
            ".read": "auth !== null",
            ".write": false
          }
        }
      }
    }
  }
}
```

### شرح القواعد

| القاعدة | الشرح |
|---------|-------|
| `".read": false` (root) | منع الوصول المباشر لجذر قاعدة البيانات |
| `".write": false` (root) | منع الكتابة من الـ Frontend تماماً |
| `"doctors/$doctorId/doctor_name": { ".read": "auth !== null" }` | أي مستخدم سجل دخول في Firebase يمكنه قراءة اسم الدكتور |
| `"doctors/$doctorId/booked-appointments/$date": { ".read": "auth !== null" }` | أي مستخدم سجل دخول يمكنه قراءة مواعيد أي دكتور |
| `".write": false` (على كل المسارات) | الـ Frontend لا يستطيع الكتابة — فقط Laravel Admin SDK يكتب |

> **الأمان:** الاعتماد على Admin SDK (`FirebaseRtdbService`) الذي يستخدم Service Account للكتابة ويمر عبر Firebase Servers مباشرة (ليس عبر REST API العام)، لذلك يتجاوز قواعد الأمان هذه.

---

## 8. أمر التنظيف (Cleanup Command)

```
php artisan appointments:cleanup-rtdb
```

### المهام

1. **حذف المواعيد المنتهية من RTDB** — المواعيد التي انتهى وقتها (تاريخ + وقت ≤ الآن) ولم يتم تحديث حالتها
2. **تصفية المواعيد القديمة (بعد 24 ساعة):**
   - `Set` / `Accepted` → **Cancelled** + حذف من RTDB
   - `InProgress` → **Completed** + حذف من RTDB
3. **تسجيل** `AppointmentStatusLog` مع `changed_by: "system: auto-cleanup"`

### الجدولة

يعمل كل **5 دقائق** عبر الـ Scheduler في `routes/console.php`:

```php
Schedule::command('appointments:cleanup-rtdb')->everyFiveMinutes();
```

### المصدر

**الملف:** `app/Console/Commands/CleanupExpiredRtdbAppointments.php`

---

## 9. دمج Flutter (Frontend Integration)

### تسجيل الدخول وربط Firebase

```dart
import 'package:firebase_auth/firebase_auth.dart';
import 'package:firebase_database/firebase_database.dart';

Future<void> connectToFirebase(String laravelToken) async {
  // 1. اجلب Firebase Custom Token من Laravel
  final response = await http.post(
    Uri.parse('http:/10.0.2.2:8000/api/v1/auth/firebase-token'),
    headers: {'Authorization': 'Bearer $laravelToken'},
  );
  final firebaseToken = json.decode(response.body)['data']['firebase_token'];

  // 2. سجل دخول في Firebase
  await FirebaseAuth.instance.signInWithCustomToken(firebaseToken);
}

Stream<DatabaseEvent> watchDoctorAppointments(String doctorId) {
  final ref = FirebaseDatabase.instance
      .ref('doctors/$doctorId/booked-appointments');

  return ref.onValue; // يستمع للتغييرات المباشرة
}
```

### مثال كامل للاستماع لمواعيد دكتور

```dart
class DoctorAppointmentsWidget extends StatefulWidget {
  final String doctorId;
  const DoctorAppointmentsWidget({required this.doctorId});

  @override
  State<DoctorAppointmentsWidget> createState() => _DoctorAppointmentsWidgetState();
}

class _DoctorAppointmentsWidgetState extends State<DoctorAppointmentsWidget> {
  late final StreamSubscription _subscription;
  Map<String, dynamic>? _appointments;

  @override
  void initState() {
    super.initState();
    final ref = FirebaseDatabase.instance
        .ref('doctors/${widget.doctorId}/booked-appointments');
    _subscription = ref.onValue.listen((event) {
      setState(() {
        _appointments = Map<String, dynamic>.from(event.snapshot.value ?? {});
      });
    });
  }

  @override
  void dispose() {
    _subscription.cancel();
    super.dispose();
  }
}
```

### هيكل البيانات المستلمة في Flutter

عند استدعاء `ref('doctors/{doctorId}/booked-appointments/{date}/{appointmentId}').once()`:

```dart
{
  "id": "019e1d0f-...",
  "appointment_date": "2026-06-01",
  "start_time": "10:00",
  "end_time": "11:00",
  "status": "accepted",
  "reason": "Checkup",
  "notes": "..."
}
```

---

## 10. تشخيص الأخطاء (Troubleshooting)

| المشكلة | السبب | الحل |
|---------|-------|------|
| `Firebase not configured` (500) | ملف `service-account.json` مفقود أو المسار خطأ | تأكد من وجود الملف في `storage/app/firebase/service-account.json` |
| RTDB URL not configured | `FIREBASE_RTDB_URL` غير موجود في `.env` | أضف الرابط الصحيح من Firebase Console |
| Client authentication failed (Login) | Password Grant Client غير موجود في قاعدة البيانات | شغّل `php artisan passport:client --password --name "Password Grant Client" --provider users` |
| Firebase token generation fails (500) | Credentials تالفة أو صلاحيات Service Account ناقصة | تأكد من تفعيل Authentication + Realtime Database في Firebase Console |
| Reading empty data in Flutter | لم يتم تسجيل الدخول في Firebase أو الـ Security rules تمنع القراءة | تأكد من استدعاء `signInWithCustomToken()` قبل قراءة RTDB |
| بيانات قديمة تظهر في RTDB | لم يتم حذف المواعيد المنتهية | شغّل يدوياً `php artisan appointments:cleanup-rtdb` أو انتظر 5 دقائق للجدولة التلقائية |

---

## 11. ملفات المصدر (Source Files Reference)

| الملف | الوصف |
|-------|-------|
| `app/Domains/Notifications/Services/FirebaseRtdbService.php` | Low-level wrapper حول Kreait Firebase RTDB (setValue, removeValue, getValue) |
| `app/Domains/Appointments/Services/AppointmentRtdbService.php` | Domain service لبناء بيانات المواعيد ومزامنتها مع RTDB |
| `app/Domains/Notifications/Services/FirebaseService.php` | Auth (Custom Tokens) + Messaging (FCM) |
| `app/Http/Controllers/Api/V1/Auth/AuthController.php` | Endpoint `POST /auth/firebase-token` |
| `app/Console/Commands/CleanupExpiredRtdbAppointments.php` | Artisan command للتنظيف التلقائي |
| `config/notification.php` | إعدادات Firebase (credentials, rtdb_url) |
| `storage/app/firebase/service-account.json` | Service Account JSON Key (غير مضمن في Git) |
