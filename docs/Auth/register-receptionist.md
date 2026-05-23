# POST /api/v1/auth/register/receptionist

تسجيل حساب موظف استقبال جديد (يحتاج تفعيل من الأدمن). **لا يتم تسجيل الدخول تلقائياً.**

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

**الملف:** `app/Domains/Auth/DTOs/RegisterReceptionistData.php`

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
├── 1. User::create(['is_active' => false, ...])
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

> **ملاحظة:** الحساب يُنشأ بـ `is_active = false` ولا يتم تسجيل الدخول تلقائياً.

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
    $this->registerReceptionistAction->execute($dto);

    return ApiResponse::created(null, __('auth.pending_activation'));
}
```

---

## 6. Response

### ✅ Success — `201 Created`

```json
{
    "status": 201,
    "message": "Account created successfully. Please wait for admin approval.",
    "data": null
}
```

### ❌ Validation Error — `422 Unprocessable Entity`

```json
{
    "status": 422,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

---

## 7. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `201` | `Account created successfully...` | نجاح |
| `422` | `Validation failed` | حقل مطلوب ناقص أو قيمة غير صحيحة |
| `429` | `Too Many Attempts.` | تجاوز rate limit |

---

## 8. تفعيل الحساب

بعد الإنشاء، يمكن للأدمن تفعيل الحساب عبر:
- **`PUT /api/v1/receptionists/{receptionist}/activate-account`** (محمي بالأدمن)

انظر [activate-account.md](../Receptionists/activate-account.md).
