# GET /api/v1/doctors

قائمة الأطباء مع البحث والدعم.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **URL** | `/api/v1/doctors` |
| **Auth** | ❌ عام |
| **Middleware** | لا يوجد |

---

## 2. Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | `string` | — | فلترة بالاسم أو الإيميل |
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
4. paginate(min(limit, 100))
5. DoctorResource::collection()
6. return 200
```

---

## 4. الـ Resource: `DoctorResource`

**الملف:** `app/Domains/Doctors/Resources/DoctorResource.php`

```php
class DoctorResource extends UserResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'specialization' => $this->doctor?->specialization?->value,
            'experience_months' => $this->doctor?->experience_months,
            'schedules' => $this->doctor?->schedules->map(fn ($s) => [
                'id' => $s->id,
                'day_of_week' => $s->day_of_week?->value,
                'start_time' => $s->start_time?->format('H:i'),
                'end_time' => $s->end_time?->format('H:i'),
                'is_active' => $s->is_active,
            ]),
        ]);
    }
}
```

يمتد من `UserResource` (id, first_name, last_name, username, email, phone, address, gender, birthday_date, roles, is_active, image).

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
            "image": null,
            "specialization": "cardiology",
            "experience_months": 60,
            "schedules": [
                {
                    "id": "0196f0a0-...",
                    "day_of_week": "sunday",
                    "start_time": "09:00",
                    "end_time": "17:00",
                    "is_active": true
                }
            ]
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
