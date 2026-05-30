# PUT /api/v1/auth/me

تحديث ملف المستخدم الحالي. جميع الحقول اختيارية (يُرسل فقط الحقول التي تريد تغييرها).

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `PUT` |
| **URL** | `/api/v1/auth/me` |
| **Auth** | ✅ `Bearer <access_token>` |
| **Middleware** | `auth:api`, `image.content` |
| **Content-Type** | `multipart/form-data` (لأنه يدعم رفع صورة) |

---

## 2. Request Body

جميع الحقول **اختيارية** (باستخدام `sometimes` في الـ validation).

| الحقل | النوع | الحالة | التحقق |
|-------|-------|--------|--------|
| `first_name` | `string` | اختياري | max:255 |
| `last_name` | `string` | اختياري | max:255 |
| `username` | `string` | اختياري | max:255, unique:users (ignore current user) |
| `email` | `string` (email) | اختياري | max:255, unique:users (ignore current user) |
| `phone` | `string` | اختياري | max:20 |
| `address` | `string` | اختياري | max:1000 |
| `gender` | `string` (enum) | اختياري | `male`, `female` |
| `birthday_date` | `string` (date) | اختياري | صيغة YYYY-MM-DD |
| `city_id` | `string` (UUID) | اختياري | exists:cities,id |
| `file` | `file` (image) | اختياري | max:2MB, mimes:jpg,jpeg,png,webp |

### مثال (JSON):

```json
{
    "first_name": "أحمد",
    "last_name": "عبدالله",
    "phone": "0555999999"
}
```

### مثال (multipart/form-data مع صورة):

| Field | Value |
|-------|-------|
| `first_name` | `أحمد` |
| `file` | `avatar.jpg` (ملف مرفوع) |

---

## 3. الـ Request: `UpdateProfileRequest`

**الملف:** `app/Domains\Auth\Requests\UpdateProfileRequest.php`

```php
public function rules(): array
{
    $userId = $this->user()->id;

    return [
        'first_name' => ['sometimes', 'required', 'string', 'max:255'],
        'last_name' => ['sometimes', 'required', 'string', 'max:255'],
        'username' => ['sometimes', 'required', 'string', 'max:255',
            Rule::unique('users', 'username')->ignore($userId)],
        'email' => ['sometimes', 'required', 'email', 'max:255',
            Rule::unique('users', 'email')->ignore($userId)],
        'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
        'gender' => ['sometimes', 'required', Rule::enum(GenderEnum::class)],
        'birthday_date' => ['sometimes', 'nullable', 'date'],
        'city_id' => ['sometimes', 'nullable', 'string', 'exists:cities,id'],
        'file' => ['sometimes', 'nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
    ];
}
```

> يستخدم `Rule::unique(...)->ignore($userId)` لضمان عدم تعارض الإيميل أو اسم المستخدم مع المستخدم نفسه.

---

## 4. DTO: `UpdateProfileData`

**الملف:** `app/Domains\Auth\DTOs\UpdateProfileData.php`

```php
class UpdateProfileData
{
    private array $fields = [];
    public ?UploadedFile $file = null;
}
```

### `fromRequest(UpdateProfileRequest $request): self`

```
1. ينشئ DTO فارغاً
2. يمر على الحقول: first_name, last_name, username, email, phone, address, birthday_date, city_id
   - إذا الحقل موجود في الـ Request ← يضيفه إلى $fields
3. إذا gender موجود ← يحوله من string إلى GenderEnum value
4. إذا file موجود ← يخزنه في $this->file
5. return $dto
```

### `toUpdateArray(): array`

يرجع `$this->fields` ليستخدم في `$user->update(...)`.

### `hasFile(): bool`

يتحقق هل يوجد ملف مرفوع.

---

## 5. Action: `UpdateProfileAction::execute()`

**الملف:** `app/Domains\Auth\Actions\UpdateProfileAction.php`

```php
public function execute(User $user, UpdateProfileData $data): User
{
    $user->update($data->toUpdateArray());

    if ($data->hasFile()) {
        $this->uploadImageAction->execute(UploadImageData::fromArray([
            'file' => $data->file,
            'type' => ImageTypeEnum::User,
            'imageable_id' => $user->id,
        ]));
    }

    return $user->fresh();
}
```

**التدفق:**

```
1. $user->update($data->toUpdateArray())
   └── يحدث فقط الحقول الموجودة (ما يرسله العميل)

2. if ($data->hasFile())
   └── UploadImageAction::execute()
         ├── file = $data->file
         ├── type = ImageTypeEnum::User
         └── imageable_id = $user->id

3. return $user->fresh()
   └── يعيد تحميل المستخدم من قاعدة البيانات بأحدث البيانات
```

### Dependencies:

| الخدمة | الغرض |
|--------|-------|
| `UploadImageAction` | رفع الصورة الجديدة إن وجدت |

---

## 6. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Auth/AuthController.php`

```php
public function updateProfile(UpdateProfileRequest $request): JsonResponse
{
    $user = $request->user();
    $dto = UpdateProfileData::fromRequest($request);
    $user = $this->updateProfileAction->execute($user, $dto)->load('roles');

    $resource = match (true) {
        $user->hasRole('patient')      => new PatientResource($user),
        $user->hasRole('doctor')       => new DoctorResource($user),
        $user->hasRole('receptionist') => new ReceptionistResource($user),
        default                        => new UserResource($user),
    };

    return ApiResponse::success($resource, __('Profile updated successfully'));
}
```

---

## 7. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Profile updated successfully",
    "data": {
        "id": "0196f0a0-1234-7abc-def0-123456789abc",
        "first_name": "أحمد",
        "last_name": "عبدالله",
        "username": "ahmed_m",
        "email": "ahmed@example.com",
        "phone": "0555999999",
        "address": "الرياض، المملكة العربية السعودية",
        "gender": "male",
        "birthday_date": "1990-01-15",
        "roles": ["Patient"],
        "is_active": true,
        "city_id": null,
        "city": null,
        "country": null,
        "patient": {
            "id": "0196f0a0-5678-7def-abcd-987654321abc"
        },
        "image": {
            "id": "0196f0a0-9999-7abc-def0-999999999999",
            "url": "http://localhost:8000/storage/uploads/users/0196f0a0-1234/avatar.jpg"
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
        "file": ["The file must be an image."]
    }
}
```

---

## 8. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Profile updated successfully` | نجاح |
| `422` | `Validation failed` | أحد الحقول لا يمرر التحقق |
| `401` | `Unauthenticated.` | التوكن غير صالح |
