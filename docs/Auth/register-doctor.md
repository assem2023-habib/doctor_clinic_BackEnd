# POST /api/v1/auth/register/doctor

تسجيل دكتور جديد + تسجيل دخول تلقائي.

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
| `specialization` | `string` (enum) | **مطلوب** | أحد تخصصات `SpecializationEnum` (27 تخصصاً) |
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
    "specialization": "cardiology",
    "experience_months": 120,
    "password": "SecureDocPass456!"
}
```

### قيم `specialization` المتاحة:

`cardiology`, `dermatology`, `neurology`, `pediatrics`, `orthopedics`, `ophthalmology`, `ent`, `psychiatry`, `radiology`, `surgery`, `internal_medicine`, `obstetrics_gynecology`, `emergency_medicine`, `anesthesiology`, `pathology`, `urology`, `gastroenterology`, `endocrinology`, `pulmonology`, `rheumatology`, `nephrology`, `hematology`, `oncology`, `infectious_disease`, `genetics`, `immunology`, `sports_medicine`

---

## 3. DTO: `RegisterDoctorData`

**الملف:** `app/Domains\Auth/DTOs/RegisterDoctorData.php`

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
    public readonly SpecializationEnum $specialization;
    public readonly int $experienceMonths;
    public readonly string $password;
    public readonly ?UploadedFile $file;
}
```

### `fromRequest(RegisterDoctorRequest $request): self`

| مفتاح الـ Request | خاصية الـ DTO | تحويل |
|-------------------|---------------|-------|
| `specialization` | `$specialization` | `SpecializationEnum::from($request->specialization)` |
| `experience_months` | `$experienceMonths` | `(int) $request->experience_months` |

باقي الحقول مثل `RegisterPatientData`.

---

## 4. Action: `RegisterDoctorAction::execute()`

**الملف:** `app/Domains\Auth\Actions\RegisterDoctorAction.php`

### التدفق الداخلي:

```
RegisterDoctorAction::execute(RegisterDoctorData $data)
│
├── 1. User::create([...])
│     └── نفس حقول Patient
│
├── 2. $user->assignRole('doctor')
│
├── 3. $user->doctor()->create([
│     ├── specialization => $data->specialization,
│     └── experience_months => $data->experienceMonths
│     ])
│
├── 4. if ($data->file)
│     └── UploadImageAction::execute()
│
└── 5. return $user
```

> **الفرق الجوهري عن Patient:** ينشئ سجل `Doctor` مع `specialization` و `experience_months`.

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
    $user = $this->registerDoctorAction->execute($dto);
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
            "id": "0196f0a0-xxxx-7abc-def0-xxxxxxxxxxxx",
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
                "id": "0196f0a0-yyyy-7abc-def0-yyyyyyyyyyyy",
                "specialization": "cardiology",
                "experience_months": 120
            },
            "image": null
        }
    }
}
```

### ❌ Validation Error — `422 Unprocessable Entity`

```json
{
    "status": 422,
    "message": "Validation failed",
    "errors": {
        "specialization": ["The selected specialization is invalid."],
        "experience_months": ["The experience months must be between 0 and 1200."]
    }
}
```

---

## 7. Sequence Diagram

> نفس تدفق Register Patient، مع إضافة خطوة إنشاء `Doctor` بالـ specialization والـ experience_months.

```
Client → Controller → RegisterDoctorAction
                         ├── User::create()
                         ├── assignRole('doctor')
                         ├── $user->doctor()->create({specialization, experience_months})
                         ├── UploadImage (optional)
                         └── return $user
                      → LoginAction (auto-login)
                      → AuthResource
                      → 201 JSON
```

---

## 8. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `201` | `Registered successfully.` | نجاح |
| `422` | `Validation failed` | حقل مطلوب ناقص أو قيمة غير صحيحة |
| `429` | `Too Many Attempts.` | تجاوز rate limit |
