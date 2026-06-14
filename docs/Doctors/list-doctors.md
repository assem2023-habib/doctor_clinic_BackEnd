# GET /api/v1/doctors

قائمة الأطباء مع البحث والدعم.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **URL** | `/api/v1/doctors` |
| **Auth** | ✅ مطلوب (`auth:api`, `active`) |
| **Middleware** | `auth:api`, `active` |

---

## 2. Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | `string` | — | فلترة بالاسم أو الإيميل |
| `specialization_id` | `string` (UUID) | — | فلترة حسب المعرف الفريد للتخصص |
| `experience_from` | `integer` | — | خبرة من (شهر) |
| `experience_to` | `integer` | — | خبرة إلى (شهر) |
| `gender` | `string` | — | فلترة بالجنس (male/female) |
| `date_from` | `date` | — | birthday_date من |
| `date_to` | `date` | — | birthday_date إلى |
| `is_active` | `boolean` | — | فلترة بالحالة (true/false) |
| `limit` | `integer` | `20` | عدد العناصر (max: 100) |

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Doctor/DoctorController.php`

```php
public function index(Request $request): JsonResponse
{
    $doctors = User::whereHas('roles', fn ($q) => $q->where('slug', 'doctor'))
        ->with('doctor.schedules', 'roles')
        ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
            $q->where('first_name', 'like', "%{$v}%")
              ->orWhere('last_name', 'like', "%{$v}%")
              ->orWhere('email', 'like', "%{$v}%");
        }))
        ->when($request->specialization_id, fn ($q, $v) => $q->whereHas('doctor', fn ($q) => $q->where('specialization_id', $v)))
        ->when($request->experience_from, fn ($q, $v) => $q->whereHas('doctor', fn ($q) => $q->where('experience_months', '>=', (int) $v)))
        ->when($request->experience_to, fn ($q, $v) => $q->whereHas('doctor', fn ($q) => $q->where('experience_months', '<=', (int) $v)))
        ->when($request->gender, fn ($q, $v) => $q->where('gender', $v))
        ->when($request->date_from, fn ($q, $v) => $q->where('birthday_date', '>=', $v))
        ->when($request->date_to, fn ($q, $v) => $q->where('birthday_date', '<=', $v))
        ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
        ->paginate(min($limit, 100));

    return ApiResponse::success(
        DoctorResource::collection($doctors),
        __('Doctors retrieved successfully'),
        pagination: ApiResponse::pagination($doctors)
    );
}
```

**التدفق:**
```
 1. User::whereHas('roles', slug=doctor)
 2. with('doctor.schedules', 'roles') — eager load
 3. if search → filter by first_name, last_name, email
 4. if specialization_id → whereHas doctor.specialization_id
 5. if experience_from/to → whereHas doctor.experience_months >= / <=
 6. if gender → where gender
 7. if date_from/to → where birthday_date >= / <=
 8. if is_active → where is_active
 9. paginate(min(limit, 100))
10. DoctorResource::collection()
11. return 200
```

---

## 4. الـ Resource: `DoctorResource`

**الملف:** `app/Domains/Doctors/Resources/DoctorResource.php`

يمتد من `UserResource` (id, first_name, last_name, username, email, phone, address, gender, birthday_date, roles, is_active, image) ويضيف:
- `specialization`
- `experience_months`
- `schedules`
- `rating` (avg, count, recent) — فقط في show

> **ملاحظة:** في قائمة الأطباء `rating` يكون `{"avg": 0, "count": 0, "recent": []}` لأن بيانات التقييمات تُحمّل فقط في show.

---

## 5. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Doctors retrieved successfully",
    "data": [
        {
            "id": "0196f0a0-...",
            "first_name": "Khaled",
            "last_name": "Suleiman",
            "username": "drkhaled",
            "email": "doctor@example.com",
            "phone": "+963912345679",
            "address": "Aleppo, Syria",
            "gender": "male",
            "birthday_date": "1985-03-20",
            "roles": ["Doctor"],
            "is_active": true,
            "image": null,
            "specialization": {
                "id": "0196f0a0-...",
                "slug": "cardiology",
                "name": {
                    "ar": "طب القلب",
                    "en": "Cardiology"
                },
                "description": null
            },
            "experience_months": 60,
            "schedules": [
                {
                    "id": "0196f0a0-...",
                    "day_of_week": "sunday",
                    "start_time": "09:00",
                    "end_time": "17:00",
                    "is_active": true
                }
            ],
            "rating": {
                "avg": 0,
                "count": 0,
                "recent": []
            }
        }
    ],
    "meta": {
        "pagination": {
            "current_page": 1,
            "last_page": 1,
            "limit": 20,
            "total": 1,
            "hasNextPage": false,
            "hasPreviousPage": false
        }
    }
}
```

---

## 6. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Doctors retrieved successfully` | نجاح |
| `401` | `Unauthenticated` | لم يتم تسجيل الدخول |
