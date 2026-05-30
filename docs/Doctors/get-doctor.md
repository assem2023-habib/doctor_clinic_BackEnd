# GET /api/v1/doctors/{doctor}

عرض طبيب محدد.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **URL** | `/api/v1/doctors/{doctor}` |
| **Auth** | ❌ عام |
| **Middleware** | لا يوجد |

**Route Model Binding:** `{doctor}` هو `Doctor` (UUID).

---

## 2. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Doctor/DoctorController.php`

```php
public function show(Doctor $doctor): JsonResponse
{
    $doctor->load('user', 'schedules');
    $user = $doctor->user;
    $user->setRelation('doctor', $doctor);

    return ApiResponse::success(
        new DoctorResource($user),
        __('Doctor retrieved successfully')
    );
}
```

**التدفق:**
```
1. Doctor::where('id', $id)->first() — Route Model Binding
2. $doctor->load('user', 'schedules')
3. $user->setRelation('doctor', $doctor) — يربط علاقة doctor بالمستخدم
4. new DoctorResource($user)
5. return 200
```

---

## 3. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Doctor retrieved successfully",
    "data": {
        "id": "0196f0a0-...",
        "first_name": "Khaled",
        "last_name": "Suleiman",
        "username": "drkhaled",
        "email": "doctor@example.com",
        "phone": "+963912345679",
        "address": "Aleppo, Syria",
        "gender": "male",
        "birthday_date": "1985-03-20",
        "roles": ["Doctor"],
        "is_active": true,
        "image": null,
        "specialization": {
                "id": "0196f0a0-...",
                "slug": "cardiology",
                "name": {
                    "ar": "طب القلب",
                    "en": "Cardiology"
                },
                "description": null
            },
        "experience_months": 60,
        "schedules": [
            {
                "id": "0196f0a0-...",
                "day_of_week": "sunday",
                "start_time": "09:00",
                "end_time": "17:00",
                "is_active": true
            }
        ]
    }
}
```

### ❌ Not Found — `404`

```json
{
    "status": 404,
    "message": "Resource not found"
}
```

---

## 4. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Doctor retrieved successfully` | نجاح |
| `404` | `Resource not found` | الـ UUID غير موجود |
