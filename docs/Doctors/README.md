# Doctors Domain — التوثيق الكامل

إدارة الأطباء — عرض، بحث، تحديث، حذف مع تنظيف متتالي.

---

## قائمة الـ Endpoints

### Doctors

| # | Method | Endpoint | Auth | الوصف |
|---|--------|----------|------|-------|
| 1 | `GET` | `/api/v1/doctors` | ❌ عام | قائمة الأطباء مع البحث والفلترة — يعيد `supervision_request` للمريض المصادق |
| 2 | `POST` | `/api/v1/doctors` | ✅ admin | إنشاء دكتور جديد ([توثيق](create-doctor.md)) |
| 3 | `GET` | `/api/v1/doctors/{doctor}` | ❌ عام | عرض طبيب — يعيد `supervision_request` للمريض المصادق |
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

موجود فقط في `GET /api/v1/doctors` و `GET /api/v1/doctors/{doctor}` للمستخدمين المصادقين (auth:api).

| الحقل | النوع | الوصف |
|-------|------|-------|
| `has_request` | boolean | هل لدى المستخدم المصادق (Patient) طلب إشراف لهذا الدكتور |
| `status` | string\|null | حالة الطلب: `pending`, `approved`, `rejected`, `cancelled` — أو `null` إن لم يوجد |

**ملاحظة:** الحقل يظهر فقط إذا كان المستخدم المصادق لديه دور `Patient`. لـ Admin أو Doctor أو Receptionist **لا يظهر الحقل مطلقاً** (محذوف بالكامل من الاستجابة).

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
