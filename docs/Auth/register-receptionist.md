# POST /api/v1/auth/register/receptionist

تسجيل موظف استقبال جديد + تسجيل دخول تلقائي.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/register/receptionist` |
| **Auth** | ❌ لا يحتاج |
| **Middleware** | `throttle:register` (3 محاولات في الدقيقة), `image.content` |
| **Content-Type** | `multipart/form-data` |

---

## 2. Request Body

| الحقل | النوع | الحالة | التحقق |
|-------|-------|--------|--------|
| `first_name` | `string` | **مطلوب** | max:255 |
| `last_name` | `string` | **مطلوب** | max:255 |
| `username` | `string` | **مطلوب** | max:255, unique:users |
| `email` | `string` (email) | **مطلوب** | max:255, unique:users |
| `phone` | `string` | اختياري | max:20 |
| `address` | `string` | اختياري | max:1000 |
| `gender` | `string` (enum) | **مطلوب** | `male`, `female` |
| `birthday_date` | `string` (date) | اختياري | صيغة YYYY-MM-DD |
| `password` | `string` | **مطلوب** | Laravel Password defaults |
| `shift_start` | `string` (time) | اختياري | صيغة `H:i` (مثال: 08:00) |
| `shift_end` | `string` (time) | اختياري | صيغة `H:i` (مثال: 16:00) |
| `file` | `file` (image) | اختياري | max:2MB, mimes:jpg,jpeg,png,webp |

### مثال (JSON):

```json
{
    "first_name": "خالد",
    "last_name": "عمر",
    "username": "khaled_r",
    "email": "khaled@clinic.com",
    "phone": "0555777888",
    "address": "الدمام، المملكة العربية السعودية",
    "gender": "male",
    "birthday_date": "1995-03-10",
    "password": "SecureRecPass789!",
    "shift_start": "08:00",
    "shift_end": "16:00"
}
```

---

## 3. DTO: `RegisterReceptionistData`

**الملف:** `app/Domains\Auth/DTOs/RegisterReceptionistData.php`

```php
class RegisterReceptionistData
{
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly string $username;
    public readonly string $email;
    public readonly ?string $phone;
    public readonly ?string $address;
    public readonly GenderEnum $gender;
    public readonly ?string $birthdayDate;
    public readonly string $password;
    public readonly ?string $shiftStart;
    public readonly ?string $shiftEnd;
    public readonly ?UploadedFile $file;
}
```

### `fromRequest(RegisterReceptionistRequest $request): self`

| مفتاح الـ Request | خاصية الـ DTO |
|-------------------|---------------|
| `first_name` | `$firstName` |
| `last_name` | `$lastName` |
| `username` | `$username` |
| `email` | `$email` |
| `phone` | `$phone` |
| `address` | `$address` |
| `gender` | `GenderEnum::from(...)` |
| `birthday_date` | `$birthdayDate` |
| `password` | `$password` |
| `shift_start` | `$shiftStart` |
| `shift_end` | `$shiftEnd` |
| `file` | `$request->file('file')` |

---

## 4. Action: `RegisterReceptionistAction::execute()`

**الملف:** `app/Domains\Auth\Actions\RegisterReceptionistAction.php`

### التدفق الداخلي:

```
RegisterReceptionistAction::execute(RegisterReceptionistData $data)
│
├── 1. User::create([...])
│
├── 2. $user->assignRole('receptionist')
│
├── 3. $user->receptionist()->create([
│     ├── shift_start => $data->shiftStart,
│     └── shift_end => $data->shiftEnd
│     ])
│
├── 4. if ($data->file)
│     └── UploadImageAction::execute()
│
└── 5. return $user
```

> **الفرق الجوهري:** ينشئ سجل `Receptionist` مع `shift_start` و `shift_end`.

### Dependencies:

| الخدمة | الغرض |
|--------|-------|
| `UploadImageAction` | رفع الصورة إن وجدت |

---

## 5. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function registerReceptionist(RegisterReceptionistRequest $request): JsonResponse
{
    $dto = RegisterReceptionistData::fromRequest($request);
    $user = $this->registerReceptionistAction->execute($dto);
    $tokenData = $this->loginAction->execute(LoginData::fromCredentials(
        $dto->email,
        $dto->password
    ));

    return ApiResponse::created(
        new AuthResource((object) compact('user', 'tokenData')),
        __('auth.register_success')
    );
}
```

---

## 6. Response

### ✅ Success — `201 Created`

```json
{
    "status": 201,
    "message": "Registered successfully.",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1Qi...",
        "refresh_token": "def50200...",
        "expires_in": 31536000,
        "token_type": "Bearer",
        "user": {
            "id": "0196f0a0-zzzz-7abc-def0-zzzzzzzzzzzz",
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
                "id": "0196f0a0-wwww-7abc-def0-wwwwwwwwwwww",
                "shift_start": "08:00",
                "shift_end": "16:00"
            },
            "image": null
        }
    }
}
```

---

## 7. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `201` | `Registered successfully.` | نجاح |
| `422` | `Validation failed` | حقل مطلوب ناقص أو قيمة غير صحيحة |
| `429` | `Too Many Attempts.` | تجاوز rate limit |
