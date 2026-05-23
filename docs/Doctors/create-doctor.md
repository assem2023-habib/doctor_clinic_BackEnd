# POST /api/v1/doctors

إنشاء دكتور جديد من قبل الأدمن (is_active=true فوراً).

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/doctors` |
| **Auth** | ✅ `auth:api` + `active` + `admin` |
| **Middleware** | `auth:api`, `active`, `admin`, `image.content` |

---

## 2. Request Body (multipart/form-data)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `first_name` | `string` | ✅ | |
| `last_name` | `string` | ✅ | |
| `username` | `string` | ✅ | Unique |
| `email` | `string` | ✅ | Unique |
| `phone` | `string` | ❌ | |
| `address` | `string` | ❌ | |
| `gender` | `string` | ✅ | `male` or `female` |
| `birthday_date` | `date` | ❌ | YYYY-MM-DD |
| `specialization` | `string` | ✅ | أحد تخصصات SpecializationEnum |
| `experience_months` | `integer` | ✅ | 0–1200 |
| `password` | `string` | ✅ | |
| `file` | `file` | ❌ | صورة (jpg, jpeg, png, webp) |

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Doctor/DoctorController.php`

```php
public function store(StoreDoctorRequest $request): JsonResponse
{
    $user = $this->createDoctorAction->execute($request);

    return ApiResponse::success(
        new DoctorResource($user),
        __('Doctor created successfully'),
        status: 201
    );
}
```

---

## 4. الـ Action: `CreateDoctorAction`

**الملف:** `app/Domains/Doctors/Actions/CreateDoctorAction.php`

**التدفق:**
1. إنشاء User بـ `is_active=true`
2. Assign role `doctor`
3. إنشاء Doctor record
4. رفع صورة إن وجدت
5. إرجاع `User` مع الـ relations

---

## 5. الـ Request: `StoreDoctorRequest`

**الملف:** `app/Domains/Doctors/Requests/StoreDoctorRequest.php`

قواعد التحقق:
- `first_name`, `last_name` → required, string, max:255
- `username`, `email` → required, unique
- `gender` → required, enum (male/female)
- `specialization` → required, enum (specializations)
- `experience_months` → required, integer, 0–1200
- `password` → required, Password defaults
- `file` → image, max size حسب ImageTypeEnum::User

---

## 6. Response

### ✅ Success — `201 Created`

```json
{
    "status": 201,
    "message": "Doctor created successfully",
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
        "is_active": true,
        "image": null,
        "specialization": "cardiology",
        "experience_months": 60,
        "schedules": []
    }
}
```

### ❌ Validation Error — `422 Unprocessable Entity`

```json
{
    "message": "The email has already been taken.",
    "errors": {
        "email": [
            "The email has already been taken."
        ]
    }
}
```

### ❌ Unauthenticated — `401`

```json
{
    "message": "Unauthenticated."
}
```

### ❌ Forbidden — `403`

```json
{
    "message": "This action is unauthorized."
}
```
