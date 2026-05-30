# POST /api/v1/auth/register/doctor

تسجيل حساب دكتور جديد (يحتاج تفعيل من الأدمن). **لا يتم تسجيل الدخول تلقائياً.**

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/register/doctor` |
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
| `city_id` | `string` (UUID) | اختياري | exists:cities,id |
| `specialization_id` | `string` (UUID) | **مطلوب** | FK → specializations table |
| `experience_months` | `integer` | **مطلوب** | min:0, max:1200 |
| `password` | `string` | **مطلوب** | Laravel Password defaults |
| `file` | `file` (image) | اختياري | max:2MB, mimes:jpg,jpeg,png,webp |

### مثال (JSON):

```json
{
    "first_name": "سارة",
    "last_name": "العلي",
    "username": "dr_sara",
    "email": "sara@clinic.com",
    "phone": "0555987654",
    "address": "جدة، المملكة العربية السعودية",
    "gender": "female",
    "birthday_date": "1985-06-20",
    "specialization_id": "0196f0a0-...",
    "experience_months": 120,
    "password": "SecureDocPass456!"
}
```

---

## 3. DTO: `RegisterDoctorData`

**الملف:** `app/Domains/Auth/DTOs/RegisterDoctorData.php`

```php
class RegisterDoctorData
{
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly string $username;
    public readonly string $email;
    public readonly ?string $phone;
    public readonly ?string $address;
    public readonly GenderEnum $gender;
    public readonly ?string $birthdayDate;
    public readonly string $specializationId;
    public readonly int $experienceMonths;
    public readonly string $password;
    public readonly ?UploadedFile $file;
}
```

### `fromRequest(RegisterDoctorRequest $request): self`

| مفتاح الـ Request | خاصية الـ DTO | تحويل |
|-------------------|---------------|-------|
| `specialization_id` | `$specializationId` | `(string) $request->specialization_id` |
| `experience_months` | `$experienceMonths` | `(int) $request->experience_months` |

باقي الحقول مثل `RegisterPatientData`.

---

## 4. Action: `RegisterDoctorAction::execute()`

**الملف:** `app/Domains\Auth\Actions\RegisterDoctorAction.php`

### التدفق الداخلي:

```
RegisterDoctorAction::execute(RegisterDoctorData $data)
│
├── 1. User::create(['is_active' => false, ...])
│
├── 2. $user->assignRole('doctor')
│
├── 3. $user->doctor()->create([
│     ├── specialization_id => $data->specializationId,
│     └── experience_months => $data->experienceMonths
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
public function registerDoctor(RegisterDoctorRequest $request): JsonResponse
{
    $dto = RegisterDoctorData::fromRequest($request);
    $this->registerDoctorAction->execute($dto);

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
        "specialization_id": ["The selected specialization id is invalid."],
        "experience_months": ["The experience months must be between 0 and 1200."]
    }
}
```

---

## 7. Sequence Diagram

```
Client → Controller → RegisterDoctorAction
                         ├── User::create(['is_active' => false])
                         ├── assignRole('doctor')
                         ├── $user->doctor()->create({specialization_id, experience_months})
                         ├── UploadImage (optional)
                         └── return $user
                      → 201 JSON (data: null, message: pending_activation)
```

> لا يتم إنشاء token. الحساب يحتاج تفعيل من الأدمن أولاً.

---

## 8. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `201` | `Account created successfully...` | نجاح |
| `422` | `Validation failed` | حقل مطلوب ناقص أو قيمة غير صحيحة |
| `429` | `Too Many Attempts.` | تجاوز rate limit |

---

## 9. تفعيل الحساب

بعد الإنشاء، يمكن للأدمن تفعيل الحساب عبر:
- **`PUT /api/v1/doctors/{doctor}/activate-account`** (محمي بالأدمن)

انظر [activate-account.md](../Doctors/activate-account.md).
