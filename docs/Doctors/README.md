# Doctors Domain — التوثيق الكامل

إدارة الأطباء — عرض، بحث، تحديث، حذف مع تنظيف متتالي.

---

## قائمة الـ Endpoints

| # | Method | Endpoint | Auth | الوصف |
|---|--------|----------|------|-------|
| 1 | `GET` | `/api/v1/doctors` | ❌ عام | قائمة الأطباء مع البحث والفلترة |
| 2 | `POST` | `/api/v1/doctors` | ✅ admin | إنشاء دكتور جديد ([توثيق](create-doctor.md)) |
| 3 | `GET` | `/api/v1/doctors/{doctor}` | ❌ عام | عرض طبيب |
| 4 | `PUT` | `/api/v1/doctors/{doctor}` | ✅ admin | تحديث كامل |
| 5 | `PATCH` | `/api/v1/doctors/{doctor}` | ✅ admin | تحديث جزئي |
| 6 | `DELETE` | `/api/v1/doctors/{doctor}` | ✅ admin | حذف مع تنظيف |
| 7 | `PUT` | `/api/v1/doctors/{doctor}/activate-account` | ✅ admin | تفعيل حساب الدكتور ([توثيق](activate-account.md)) |

---

## الموديلات

| الموديل | الجدول | الحقول |
|---------|--------|--------|
| `Doctor` | `doctors` | id, user_id, specialization, experience_months |
| `DoctorSchedule` | `doctor_schedules` | id, doctor_id, day_of_week, start_time, end_time, is_active |

### علاقات Doctor:
- `user()` — BelongsTo → User
- `schedules()` — HasMany → DoctorSchedule
- `appointments()` — HasMany → Appointment
- `patients()` — BelongsToMany → Patient (via doctor_patient)

---

## هيكل المجلدات

```
app/Domains/Doctors/
├── Actions/
│   ├── ActivateDoctorAccountAction.php
│   ├── CreateDoctorAction.php
│   ├── UpdateDoctorAction.php
│   └── DeleteDoctorAction.php
├── DTOs/
│   └── UpdateDoctorData.php
├── Models/
│   ├── Doctor.php
│   └── DoctorSchedule.php
├── Requests/
│   ├── StoreDoctorRequest.php
│   ├── UpdateDoctorRequest.php
│   └── PatchDoctorRequest.php
├── Resources/
│   └── DoctorResource.php
└── Services/
    └── DoctorDeletionService.php
```
