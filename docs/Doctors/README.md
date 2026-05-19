# Doctors Domain — التوثيق الكامل

إدارة الأطباء — عرض، بحث، تحديث، حذف مع تنظيف متتالي.

---

## قائمة الـ Endpoints

| # | Method | Endpoint | Auth | الوصف |
|---|--------|----------|------|-------|
| 1 | `GET` | `/api/v1/doctors` | ❌ عام | قائمة الأطباء مع البحث |
| 2 | `GET` | `/api/v1/doctors/{doctor}` | ❌ عام | عرض طبيب |
| 3 | `PUT` | `/api/v1/doctors/{doctor}` | ✅ admin | تحديث كامل |
| 4 | `PATCH` | `/api/v1/doctors/{doctor}` | ✅ admin | تحديث جزئي |
| 5 | `DELETE` | `/api/v1/doctors/{doctor}` | ✅ admin | حذف مع تنظيف |

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
│   ├── UpdateDoctorAction.php
│   └── DeleteDoctorAction.php
├── DTOs/
│   └── UpdateDoctorData.php
├── Models/
│   ├── Doctor.php
│   └── DoctorSchedule.php
├── Requests/
│   ├── UpdateDoctorRequest.php
│   └── PatchDoctorRequest.php
├── Resources/
│   └── DoctorResource.php
└── Services/
    └── DoctorDeletionService.php
```
