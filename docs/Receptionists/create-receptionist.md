# POST /api/v1/receptionists

إنشاء موظف استقبال جديد من قبل الأدمن (is_active=true فوراً).

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/receptionists` |
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
| `shift_start` | `string` (H:i) | ❌ | بداية الوردية |
| `shift_end` | `string` (H:i) | ❌ | نهاية الوردية |
| `password` | `string` | ✅ | |
| `file` | `file` | ❌ | صورة (jpg, jpeg, png, webp) |

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Receptionist/ReceptionistController.php`

```php
public function store(StoreReceptionistRequest $request): JsonResponse
{
    $user = $this->createReceptionistAction->execute($request);

    return ApiResponse::success(
        new ReceptionistResource($user),
        __('Receptionist created successfully'),
        status: 201
    );
}
```

---

## 4. الـ Action: `CreateReceptionistAction`

**الملف:** `app/Domains/Receptionists/Actions/CreateReceptionistAction.php`

**التدفق:**
1. إنشاء User بـ `is_active=true`
2. Assign role `receptionist`
3. إنشاء Receptionist record (shift_start, shift_end)
4. رفع صورة إن وجدت
5. إرجاع `User` مع الـ relations

---

## 5. Response

### ✅ Success — `201 Created`

```json
{
    "status": 201,
    "message": "Receptionist created successfully",
    "data": {
        "id": "019e1d0f-...",
        "first_name": "Layla",
        "last_name": "Hassan",
        "username": "laylah",
        "email": "receptionist@example.com",
        "phone": "+963912345680",
        "address": "Homs, Syria",
        "gender": "female",
        "birthday_date": "1998-11-05",
        "is_active": true,
        "shift_start": "09:00",
        "shift_end": "17:00"
    }
}
```

### ❌ Validation Error — `422 Unprocessable Entity`
### ❌ Unauthenticated — `401`
### ❌ Forbidden — `403`
