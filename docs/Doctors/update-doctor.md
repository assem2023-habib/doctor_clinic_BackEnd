# PUT /api/v1/doctors/{doctor}

تحديث كامل لبيانات الطبيب.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `PUT` |
| **URL** | `/api/v1/doctors/{doctor}` |
| **Auth** | ✅ `Bearer` + `admin` |
| **Middleware** | `auth:api`, `admin` |
| **Content-Type** | `multipart/form-data` |

---

## 2. Request Body

| الحقل | النوع | الحالة | التحقق |
|-------|-------|--------|--------|
| `first_name` | `string` | **مطلوب** | max:255 |
| `last_name` | `string` | **مطلوب** | max:255 |
| `username` | `string` | **مطلوب** | max:255, unique (ignore self) |
| `email` | `string` (email) | **مطلوب** | max:255, unique (ignore self) |
| `phone` | `string` | اختياري | max:20 |
| `address` | `string` | اختياري | max:1000 |
| `gender` | `string` (enum) | **مطلوب** | `male`, `female` |
| `birthday_date` | `string` (date) | اختياري | YYYY-MM-DD |
| `specialization_id` | `string` (UUID) | **مطلوب** | FK → specializations table |
| `experience_months` | `integer` | **مطلوب** | min:0, max:1200 |
| `file` | `file` (image) | اختياري | max:2MB, jpg,jpeg,png,webp |

### مثال:

```json
{
    "first_name": "Khaled",
    "last_name": "Suleiman",
    "username": "drkhaled",
    "email": "doctor@example.com",
    "phone": "+963912345679",
    "address": "Aleppo, Syria",
    "gender": "male",
    "birthday_date": "1985-03-20",
    "specialization_id": "0196f0a0-...",
    "experience_months": 72
}
```

---

## 3. DTO + Action

### `UpdateDoctorData::fromRequest()`

**الملف:** `app/Domains/Doctors/DTOs/UpdateDoctorData.php`

```
fromRequest(UpdateDoctorRequest $request):
├── userFields: first_name, last_name, username, email, phone, address, gender, birthday_date
├── doctorFields: specialization_id, experience_months
└── file: $request->file('file')
```

### `UpdateDoctorAction::execute()`

**الملف:** `app/Domains/Doctors/Actions/UpdateDoctorAction.php`

```
execute(Doctor $doctor, UpdateDoctorData $data):
├── $user = $doctor->user
├── $user->update($data->getUserFields())
├── if (!empty($data->getDoctorFields()))
│     └── $doctor->update($data->getDoctorFields())
├── if ($data->hasFile())
│     └── UploadImageAction::execute(file, ImageTypeEnum::User, user->id)
└── return $user->fresh()
```

---

## 4. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Doctor updated successfully",
    "data": {
        "id": "0196f0a0-...",
        "first_name": "Khaled",
        "last_name": "Suleiman",
        "username": "drkhaled",
        "email": "doctor@example.com",
        "roles": ["Doctor"],
        "specialization": {
                "id": "0196f0a0-...",
                "slug": "cardiology",
                "name": {
                    "ar": "طب القلب",
                    "en": "Cardiology"
                },
                "description": null
            },
        "experience_months": 72,
        "schedules": []
    }
}
```

### ❌ Forbidden — `403`

```json
{ "status": 403, "message": "Forbidden" }
```

---

## 5. PATCH (تحديث جزئي)

**نفس المسار** `/api/v1/doctors/{doctor}` لكن بـ `PATCH`.

يستخدم `PatchDoctorRequest` (كل الحقول اختيارية بـ `sometimes`).

DTO: `UpdateDoctorData::fromRequestPartial()` — يفحص `$request->exists()` لكل حقل قبل إضافته.

---

## 6. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Doctor updated successfully` | نجاح |
| `403` | `Forbidden` | ليس admin |
| `422` | `Validation failed` | حقل ناقص أو غير صحيح |
| `401` | `Unauthenticated.` | التوكن غير صالح |
