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
    $user = $request->user();

    $resource = match ($user->role) {
        RoleEnum::Patient      => new PatientResource($user),
        RoleEnum::Doctor       => new DoctorResource($user),
        RoleEnum::Receptionist => new ReceptionistResource($user),
        default                => new UserResource($user),
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
        "role": "patient",
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
        "role": "doctor",
        "doctor": {
            "id": "0196f0a0-...",
            "specialization": "cardiology",
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
        "role": "receptionist",
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
        "role": "admin",
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
| `role` | `string` | الدور (`patient`/`doctor`/`receptionist`/`admin`) |
| `is_active` | `boolean` | هل الحساب مفعّل |
| `image` | `array?` | صورة المستخدم (إن وجدت) |

---

## 6. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `User profile retrieved successfully.` | نجاح |
| `401` | `Unauthenticated.` | التوكن غير صالح أو منتهي |
