# POST /api/v1/auth/register/patient

تسجيل مريض جديد + تسجيل دخول تلقائي.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/register/patient` |
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
| `file` | `file` (image) | اختياري | max:2MB, mimes:jpg,jpeg,png,webp |

### مثال (JSON):

```json
{
    "first_name": "أحمد",
    "last_name": "محمد",
    "username": "ahmed_m",
    "email": "ahmed@example.com",
    "phone": "0555123456",
    "address": "الرياض، المملكة العربية السعودية",
    "gender": "male",
    "birthday_date": "1990-01-15",
    "password": "SecurePass123!"
}
```

---

## 3. DTO: `RegisterPatientData`

**الملف:** `app/Domains/Auth/DTOs/RegisterPatientData.php`

```php
class RegisterPatientData
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
    public readonly ?UploadedFile $file;
}
```

### `fromRequest(RegisterPatientRequest $request): self`

| مفتاح الـ Request | خاصية الـ DTO | تحويل |
|-------------------|---------------|-------|
| `first_name` | `$firstName` | مباشر |
| `last_name` | `$lastName` | مباشر |
| `username` | `$username` | مباشر |
| `email` | `$email` | مباشر |
| `phone` | `$phone` | مباشر |
| `address` | `$address` | مباشر |
| `gender` | `$gender` | `GenderEnum::from($request->gender)` |
| `birthday_date` | `$birthdayDate` | مباشر |
| `password` | `$password` | مباشر |
| `file` | `$file` | `$request->file('file')` |

---

## 4. Action: `RegisterPatientAction::execute()`

**الملف:** `app/Domains\Auth\Actions\RegisterPatientAction.php`

### التدفق الداخلي:

```
RegisterPatientAction::execute(RegisterPatientData $data)
│
├── 1. User::create([...])
│     ├── first_name, last_name, username, email
│     ├── phone, address, gender, birthday_date
│     ├── is_active = true
│     └── password = bcrypt($data->password)
│
├── 2. $user->assignRole('patient')
│
├── 3. $user->patient()->create([])
│     └── ينشئ سجل Patient مرتبط بالمستخدم
│
├── 4. if ($data->file)
│     └── UploadImageAction::execute()
│           ├── file = $data->file
│           ├── type = ImageTypeEnum::User
│           └── imageable_id = $user->id
│
└── 5. return $user
```

### Dependencies:

| الخدمة | الغرض |
|--------|-------|
| `UploadImageAction` | رفع الصورة إن وجدت |

---

## 5. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function registerPatient(RegisterPatientRequest $request): JsonResponse
{
    $dto = RegisterPatientData::fromRequest($request);
    $user = $this->registerPatientAction->execute($dto);
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

> **ملاحظة:** بعد التسجيل، يتم استدعاء `LoginAction::execute()` تلقائياً لإصدار التوكنات.

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
        "expires_in": 86400,
        "token_type": "Bearer",
        "user": {
            "id": "0196f0a0-1234-7abc-def0-123456789abc",
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
                "id": "0196f0a0-5678-7def-abcd-987654321abc"
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
        "email": ["The email has already been taken."],
        "password": ["The password field is required."]
    }
}
```

---

## 7. الـ Resource: `AuthResource`

**الملف:** `app/Domains/Auth/Resources/AuthResource.php`

```php
class AuthResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'access_token'  => $this->tokenData->accessToken,
            'refresh_token' => $this->tokenData->refreshToken,
            'expires_in'    => $this->tokenData->expiresIn,
            'token_type'    => 'Bearer',
            'user'          => match (true) {
                $user->hasRole('patient')      => new PatientResource($user),
                $user->hasRole('doctor')       => new DoctorResource($user),
                $user->hasRole('receptionist') => new ReceptionistResource($user),
                default                        => new UserResource($user),
            },
        ];
    }
}
```

> الـ `user` داخل الـ Resource يستخدم Resource مخصص حسب الـ Role:
> - **Patient** ← `PatientResource` (يضيف `patient` object)
> - **Doctor** ← `DoctorResource` (يضيف `specialization` كـ object مع id/slug/name/description, `experience_months`)
> - **Receptionist** ← `ReceptionistResource` (يضيف `receptionist.shift_start`, `receptionist.shift_end`)
> - **Admin** ← `UserResource` (بيانات المستخدم الأساسية فقط)

---

## 8. Sequence Diagram

```
Client                     Controller              RegisterPatientAction       LoginAction       AuthService
  │                            │                          │                       │                  │
  │── POST /register/patient ──→│                          │                       │                  │
  │                            │                          │                       │                  │
  │                            │── RegisterPatientData    │                       │                  │
  │                            │── ::fromRequest() ───────│── $dto                │                  │
  │                            │                          │                       │                  │
│                            │                          │── User::create() ─────│── User ──────────│
│                            │                          │── assignRole('patient')                   │
│                            │                          │── patient()->create() │                  │
│                            │                          │── UploadImage (optional)                  │
  │                            │                          │                       │                  │
  │                            │◄── return $user ─────────│                       │                  │
  │                            │                          │                       │                  │
  │                            │── LoginData              │                       │                  │
  │                            │── ::fromCredentials() ───│───────────────────────│── execute($dto)  │
  │                            │                          │                       │                  │
  │                            │                          │                       │── checkBlocked() │
  │                            │                          │                       │── Hash::check()  │
  │                            │                          │                       │── handleSuccess()│
  │                            │                          │                       │── issueToken() ──│── OAuth2
  │                            │                          │                       │                  │
  │                            │◄── TokenData ────────────│───────────────────────│◄── TokenData ────│
  │                            │                          │                       │                  │
  │                            │── AuthResource ──────────│───────────────────────│──────────────────│
  │◄── 201 + JSON ────────────│                          │                       │                  │
```

---

## 9. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `201` | `Registered successfully.` | نجاح |
| `422` | `Validation failed` | حقل مطلوب ناقص أو قيمة غير صحيحة |
| `429` | `Too Many Attempts.` | تجاوز rate limit (3 محاولات/دقيقة) |
