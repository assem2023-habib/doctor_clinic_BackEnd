# PUT /api/v1/doctors/{doctor}/activate-account

تفعيل حساب دكتور. يقوم بتعيين `is_active = true` لحساب المستخدم المرتبط.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `PUT` |
| **URL** | `/api/v1/doctors/{doctor}/activate-account` |
| **Auth** | ✅ Bearer token (admin فقط) |
| **Middleware** | `auth:api`, `active`, `admin` |

---

## 2. الـ Action: `ActivateDoctorAccountAction::execute()`

**الملف:** `app/Domains/Doctors/Actions/ActivateDoctorAccountAction.php`

```php
public function execute(Doctor $doctor): Doctor
{
    $doctor->user->update(['is_active' => true]);
    $doctor->load('user.roles');

    return $doctor;
}
```

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Doctor/DoctorController.php`

```php
public function activateAccount(Doctor $doctor): JsonResponse
{
    $doctor = $this->activateDoctorAccountAction->execute($doctor);

    return ApiResponse::success(
        new DoctorResource($doctor->user),
        __('auth.account_activated')
    );
}
```

---

## 4. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Account activated successfully",
    "data": {
        "id": "019e1d0f-...",
        "first_name": "Khaled",
        "last_name": "Suleiman",
        "email": "doctor@example.com",
        "roles": [
            {
                "id": "...",
                "name": "Doctor",
                "slug": "doctor",
                "guard_name": "api",
                "is_system": true
            }
        ],
        "phone": "+963912345679",
        "gender": "male",
        "birthday_date": "1985-03-20",
        "is_active": true,
        "specialization": "cardiology",
        "experience_months": 60,
        "schedules": []
    }
}
```

### ❌ Unauthenticated — `401 Unauthorized`

```json
{
    "status": 401,
    "message": "Unauthenticated"
}
```

### ❌ Forbidden — `403 Forbidden`

```json
{
    "status": 403,
    "message": "Forbidden"
}
```

---

## 5. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Account activated successfully` | نجاح |
| `401` | `Unauthenticated` | لم يتم تسجيل الدخول |
| `403` | `Forbidden` | ليس لديك صلاحية admin |
| `404` | `Not Found` | الـ Doctor UUID غير موجود |
