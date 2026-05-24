# GET /api/v1/auth/me

جلب ملف المستخدم الحالي. يعيد البيانات حسب الـ Role (Patient, Doctor, Receptionist, Admin).

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **URL** | `/api/v1/auth/me` |
| **Auth** | ✅ `Bearer <access_token>` |
| **Middleware** | `auth:api` |
| **Content-Type** | — |

---

## 2. Request

**Headers:**

```
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

لا يحتاج Body.

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function me(Request $request): JsonResponse
{
    $user = $request->user()->load('roles');

    $resource = match (true) {
        $user->hasRole('patient')      => new PatientResource($user),
        $user->hasRole('doctor')       => new DoctorResource($user),
        $user->hasRole('receptionist') => new ReceptionistResource($user),
        default                        => new UserResource($user),
    };

    return ApiResponse::success($resource, __('auth.profile_retrieved'));
}
```

---

## 4. الـ Resource حسب الـ Role

### Patient — `PatientResource`

```json
{
    "status": 200,
    "message": "User profile retrieved successfully.",
    "data": {
        "id": "0196f0a0-...",
        "first_name": "أحمد",
        "last_name": "محمد",
        "username": "ahmed_m",
        "email": "ahmed@example.com",
        "phone": "0555123456",
        "address": "الرياض، المملكة العربية السعودية",
        "gender": "male",
        "birthday_date": "1990-01-15",
        "roles": [
            {
                "id": "...",
                "name": "Patient",
                "slug": "patient",
                "description": null,
                "guard_name": "api",
                "is_system": true,
                "created_at": "...",
                "updated_at": "..."
            }
        ],
        "is_active": true,
        "patient": {
            "id": "0196f0a0-..."
        },
        "image": null
    }
}
```

### Doctor — `DoctorResource`

```json
{
    "status": 200,
    "message": "User profile retrieved successfully.",
    "data": {
        "id": "0196f0a0-...",
        "first_name": "سارة",
        "last_name": "العلي",
        "username": "dr_sara",
        "email": "sara@clinic.com",
        "phone": "0555987654",
        "address": "جدة، المملكة العربية السعودية",
        "gender": "female",
        "birthday_date": "1985-06-20",
        "roles": [
            {
                "id": "...",
                "name": "Doctor",
                "slug": "doctor",
                "description": null,
                "guard_name": "api",
                "is_system": true,
                "created_at": "...",
                "updated_at": "..."
            }
        ],
        "is_active": true,
        "doctor": {
            "id": "0196f0a0-...",
            "specialization": {
                "id": "0196f0a0-...",
                "slug": "cardiology",
                "name": {
                    "ar": "طب القلب",
                    "en": "Cardiology"
                },
                "description": null
            },
            "experience_months": 120
        },
        "image": null
    }
}
```

### Receptionist — `ReceptionistResource`

```json
{
    "status": 200,
    "message": "User profile retrieved successfully.",
    "data": {
        "id": "0196f0a0-...",
        "first_name": "خالد",
        "last_name": "عمر",
        "username": "khaled_r",
        "email": "khaled@clinic.com",
        "phone": "0555777888",
        "address": "الدمام، المملكة العربية السعودية",
        "gender": "male",
        "birthday_date": "1995-03-10",
        "roles": [
            {
                "id": "...",
                "name": "Receptionist",
                "slug": "receptionist",
                "description": null,
                "guard_name": "api",
                "is_system": true,
                "created_at": "...",
                "updated_at": "..."
            }
        ],
        "is_active": true,
        "receptionist": {
            "id": "0196f0a0-...",
            "shift_start": "08:00",
            "shift_end": "16:00"
        },
        "image": null
    }
}
```

### Admin — `UserResource`

```json
{
    "status": 200,
    "message": "User profile retrieved successfully.",
    "data": {
        "id": "0196f0a0-...",
        "first_name": "Admin",
        "last_name": "User",
        "username": "admin",
        "email": "admin@clinic.com",
        "phone": null,
        "address": null,
        "gender": "male",
        "birthday_date": null,
        "roles": [
            {
                "id": "...",
                "name": "Admin",
                "slug": "admin",
                "description": null,
                "guard_name": "api",
                "is_system": true,
                "created_at": "...",
                "updated_at": "..."
            }
        ],
        "is_active": true,
        "image": null
    }
}
```

---

## 5. الحقول العامة في كل الـ Resources

| الحقل | النوع | الوصف |
|-------|-------|-------|
| `id` | `string` (UUID) | معرف المستخدم |
| `first_name` | `string` | الاسم الأول |
| `last_name` | `string` | اسم العائلة |
| `username` | `string` | اسم المستخدم |
| `email` | `string` | البريد الإلكتروني |
| `phone` | `string?` | رقم الهاتف |
| `address` | `string?` | العنوان |
| `gender` | `string` | الجنس (`male`/`female`) |
| `birthday_date` | `string?` | تاريخ الميلاد |
| `roles` | `array` | قائمة الأدوار (`patient`/`doctor`/`receptionist`/`admin`) |
| `is_active` | `boolean` | هل الحساب مفعّل |
| `image` | `array?` | صورة المستخدم (إن وجدت) |

---

## 6. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `User profile retrieved successfully.` | نجاح |
| `401` | `Unauthenticated.` | التوكن غير صالح أو منتهي |
