# DELETE /api/v1/doctors/{doctor}

حذف طبيب مع تنظيف جميع البيانات المرتبطة.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `DELETE` |
| **URL** | `/api/v1/doctors/{doctor}` |
| **Auth** | ✅ `Bearer` + `admin` |
| **Middleware** | `auth:api`, `admin` |
| **Content-Type** | — |

---

## 2. Action: `DeleteDoctorAction::execute()`

**الملف:** `app/Domains/Doctors/Actions/DeleteDoctorAction.php`

```php
public function execute(Doctor $doctor, User $admin): void
{
    $this->doctorDeletionService->deleteDoctor($doctor, $admin);
}
```

### `DoctorDeletionService::deleteDoctor()`

**الملف:** `app/Domains/Doctors/Services/DoctorDeletionService.php`

**التدفق الكامل:**

```
deleteDoctor(Doctor $doctor, User $actingUser):
│
├── 1. التحقق من المواعيد النشطة
│     └── if (confirmed/completed appointments > 0)
│           └── abort(409, "Doctor has active appointments. Cannot delete.")
│
├── 2. DB::transaction:
│     ├── المواعيد confirmed/completed/cancelled:
│     │     ├── MedicalRecord → doctor_id = null
│     │     ├── Prescription → doctor_id = null
│     │     └── Appointment → doctor_id = null
│     ├── المواعيد pending: حذف كامل
│     ├── remaining appointments → doctor_id = null
│     ├── created_by → تحديث إلى admin
│     ├── حذف الصورة (Storage + DB)
│     ├── حذف جداول المواعيد (schedules)
│     └── حذف المستخدم (user)
```

> **ملاحظة:** الحذف يتم في transaction واحد لضمان atomicity.

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Doctor/DoctorController.php`

```php
public function destroy(Doctor $doctor): JsonResponse
{
    $this->deleteDoctorAction->execute($doctor, request()->user());

    return ApiResponse::noContent(__('Doctor deleted successfully'));
}
```

---

## 4. Response

### ✅ Success — `204 No Content`

```
(empty body)
```

### ❌ Conflict — `409 Conflict`

```json
{
    "status": 409,
    "message": "Doctor has active appointments. Cannot delete."
}
```

### ❌ Forbidden — `403`

```json
{ "status": 403, "message": "Forbidden" }
```

---

## 5. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `204` | — | نجاح |
| `409` | `Doctor has active appointments...` | يوجد مواعيد confirmed/completed |
| `403` | `Forbidden` | ليس admin |
| `401` | `Unauthenticated.` | التوكن غير صالح |
