# POST /api/v1/patients

إنشاء مريض جديد من قبل الأدمن (is_active=true فوراً).

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/patients` |
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
| `password` | `string` | ✅ | |
| `file` | `file` | ❌ | صورة (jpg, jpeg, png, webp) |

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Patient/PatientController.php`

```php
public function store(StorePatientRequest $request): JsonResponse
{
    $user = $this->createPatientAction->execute($request);

    return ApiResponse::success(
        new PatientResource($user),
        __('Patient created successfully'),
        status: 201
    );
}
```

---

## 4. الـ Action: `CreatePatientAction`

**الملف:** `app/Domains/Patients/Actions/CreatePatientAction.php`

**التدفق:**
1. إنشاء User بـ `is_active=true`
2. Assign role `patient`
3. إنشاء Patient record (user_id)
4. رفع صورة إن وجدت
5. إرجاع `User` مع الـ relations

---

## 5. Response

### ✅ Success — `201 Created`

```json
{
    "status": 201,
    "message": "Patient created successfully",
    "data": {
        "id": "019e1d0f-...",
        "first_name": "Ahmed",
        "last_name": "Ali",
        "username": "ahmedali",
        "email": "patient@example.com",
        "phone": null,
        "address": null,
        "gender": "male",
        "birthday_date": "1995-06-15",
        "is_active": true,
        "image": null
    }
}
```

### ❌ Validation Error — `422 Unprocessable Entity`
### ❌ Unauthenticated — `401`
### ❌ Forbidden — `403`
