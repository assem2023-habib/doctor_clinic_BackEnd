# Doctors Domain — التوثيق الكامل

إدارة الأطباء — عرض، بحث، تحديث، حذف مع تنظيف متتالي.

---

## قائمة الـ Endpoints

### Doctors

| # | Method | Endpoint | Auth | الوصف |
|---|--------|----------|------|-------|
| 1 | `GET` | `/api/v1/doctors` | ❌ عام | قائمة الأطباء مع البحث والفلترة |
| 2 | `POST` | `/api/v1/doctors` | ✅ admin | إنشاء دكتور جديد ([توثيق](create-doctor.md)) |
| 3 | `GET` | `/api/v1/doctors/{doctor}` | ❌ عام | عرض طبيب — يعيد `supervision_request` و `has_rated` للمريض المصادق |
| 4 | `PUT` | `/api/v1/doctors/{doctor}` | ✅ admin | تحديث كامل |
| 5 | `PATCH` | `/api/v1/doctors/{doctor}` | ✅ admin | تحديث جزئي |
| 6 | `DELETE` | `/api/v1/doctors/{doctor}` | ✅ admin | حذف مع تنظيف |
| 7 | `PUT` | `/api/v1/doctors/{doctor}/activate-account` | ✅ admin | تفعيل حساب الدكتور ([توثيق](activate-account.md)) |
| 8 | `POST` | `/api/v1/doctors/{doctor}/patients/self` | ✅ auth:api | تعيين مريض للدكتور لنفسه |
| 9 | `GET` | `/api/v1/doctors/{doctor}/supervision-requests` | ✅ auth:api | طلبات الإشراف الواردة للدكتور |
| 10 | `GET` | `/api/v1/doctors/{doctor}/appointments` | ✅ auth:api | مواعيد الدكتور مع فلترة بالتاريخ ([توثيق](../Appointments/list-get-appointments.md)) |

### Specializations

| # | Method | Endpoint | Auth | الوصف |
|---|--------|----------|------|-------|
| 1 | `GET` | `/api/v1/specializations` | ✅ auth:api | قائمة التخصصات ([توثيق](specializations.md)) |
| 2 | `GET` | `/api/v1/specializations/{id}` | ✅ auth:api | عرض تخصص |
| 3 | `POST` | `/api/v1/specializations` | ✅ admin | إنشاء تخصص ([توثيق](specializations.md)) |
| 4 | `PUT` | `/api/v1/specializations/{id}` | ✅ admin | تحديث تخصص |
| 5 | `DELETE` | `/api/v1/specializations/{id}` | ✅ admin | حذف تخصص (يمنع إذا لديه أطباء) |

---

## الموديلات

| الموديل | الجدول | الحقول |
|---------|--------|--------|
| `Doctor` | `doctors` | id, user_id, specialization_id, experience_months |
| `DoctorSchedule` | `doctor_schedules` | id, doctor_id, day_of_week, start_time, end_time, is_active |

### علاقات Specialization:
- `doctors()` — HasMany → Doctor
- `image()` — MorphOne → Image (polymorphic, `imageable`)

**Route Binding:** `getRouteKeyName()` returns `'user_id'` — implicit binding (`Doctor $doctor`) resolves by User UUID.

### علاقات Doctor:
- `user()` — BelongsTo → User
- `schedules()` — HasMany → DoctorSchedule
- `appointments()` — HasMany → Appointment
- `patients()` — BelongsToMany → Patient (via doctor_patient)

---

## Response Fields

### `supervision_request`

موجود فقط في `GET /api/v1/doctors/{doctor}` للمستخدمين المصادقين (auth:api).

| الحقل | النوع | الوصف |
|-------|------|-------|
| `has_request` | boolean | هل لدى المستخدم المصادق (Patient) طلب إشراف لهذا الدكتور |
| `status` | string\|null | حالة الطلب: `pending`, `approved`, `rejected`, `cancelled` — أو `null` إن لم يوجد |

**ملاحظة:** الحقل يظهر فقط إذا كان المستخدم المصادق لديه دور `Patient`. لـ Admin أو Doctor أو Receptionist **لا يظهر الحقل مطلقاً** (محذوف بالكامل من الاستجابة).

---

### `has_rated`

موجود فقط في `GET /api/v1/doctors/{doctor}` للمستخدمين المصادقين (auth:api).

| الحقل | النوع | الوصف |
|-------|------|-------|
| `has_rated` | boolean | هل قام المستخدم المصادق (Patient) بتقييم هذا الطبيب |

**ملاحظة:** الحقل يظهر فقط لدور `Patient`. لـ Admin أو Doctor أو Receptionist **لا يظهر الحقل مطلقاً**.

---

---

## Caching

| الـ Endpoint | مخبأ | مدة التخزين | مفتاح الكاش |
|-------------|------|-------------|-------------|
| `GET /api/v1/doctors` | ✅ نعم | 2 يوم (172800 ثانية) | `doctors:index:v{version}:{md5(filters)}` |
| `GET /api/v1/doctors/{doctor}/ratings` | ✅ نعم | 2 يوم | `doctors:ratings:v{version}:{doctor_id}:{md5(filters)}` |

**الإبطال التلقائي (Cache Invalidation):**

| الإجراء | التأثير |
|---------|---------|
| إنشاء طبيب (`POST /api/v1/doctors`) | يرفع `doctors:cache_version` → إبطال كاش القائمة والتقييمات |
| تحديث طبيب (`PUT/PATCH /api/v1/doctors/{doctor}`) | يرفع `doctors:cache_version` |
| حذف طبيب (`DELETE /api/v1/doctors/{doctor}`) | يرفع `doctors:cache_version` |
| إضافة تقييم لطبيب (`POST /api/v1/ratings` مع `type=user`) | يرفع `doctors:cache_version` + `ratings:cache_version` |

**ملاحظة:** `GET /api/v1/doctors/{doctor}` (show) **غير مخبأ** لأنه يحتوي على بيانات خاصة بالمستخدم (`has_rated`, `supervision_request`).

**آلية العمل:**
1. `ClearsCache` trait على `Doctor` و `Rating` models
2. عند `saved`/`deleted` → `Cache::increment('doctors:cache_version')`
3. في الـ Controller يتم قراءة `version` وبناء مفتاح الكاش
4. `Cache::remember()` يحفظ النتيجة لمدة يومين

---

## هيكل المجلدات

```
app/Domains/Doctors/
├── Actions/
│   ├── ActivateDoctorAccountAction.php
│   ├── CreateDoctorAction.php
│   ├── CreateSpecializationAction.php
│   ├── DeleteDoctorAction.php
│   ├── DeleteSpecializationAction.php
│   ├── UpdateDoctorAction.php
│   └── UpdateSpecializationAction.php
├── Controllers/
│   └── SpecializationController.php
├── DTOs/
│   ├── SpecializationData.php
│   └── UpdateDoctorData.php
├── Models/
│   ├── Doctor.php
│   ├── DoctorSchedule.php
│   └── Specialization.php
├── Requests/
│   ├── PatchDoctorRequest.php
│   ├── StoreDoctorRequest.php
│   ├── StoreSpecializationRequest.php
│   ├── UpdateDoctorRequest.php
│   └── UpdateSpecializationRequest.php
├── Resources/
│   ├── DoctorResource.php
│   └── SpecializationResource.php
└── Services/
    └── DoctorDeletionService.php
```
