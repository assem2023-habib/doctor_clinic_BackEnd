# GET /api/v1/doctors/{doctor}

عرض طبيب محدد مع بيانات تقييماته.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **URL** | `/api/v1/doctors/{doctor}` |
| **Auth** | ✅ مطلوب (`auth:api`, `active`) |
| **Middleware** | `auth:api`, `active` |

**Route Model Binding:** `{doctor}` هو `user_id` (UUID).

---

## 2. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Doctor/DoctorController.php`

```php
public function show(Request $request, string $doctor): JsonResponse
{
    $doctor = Doctor::where('user_id', $doctor)
        ->with('user.roles', 'schedules')
        ->firstOrFail();

    $doctor->loadCount('ratings');
    $doctor->loadAvg('ratings', 'rating');

    if ($request->user()?->patient) {
        $supervisionRequest = SupervisionRequest::where('patient_id', $request->user()->patient->id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if ($supervisionRequest) {
            $doctor->supervision_request_status = $supervisionRequest->status->value;
        }

        $doctor->user->has_rated_doctor = Rating::where('rater_id', $request->user()->id)
            ->where('rateable_id', $doctor->user_id)
            ->where('type', 'user')
            ->where('rateable_type', 'App\Models\User')
            ->exists();
    }

    $recentRatings = $doctor->ratings()
        ->with('rater')
        ->latest()
        ->limit(5)
        ->get();

    $doctor->setRelation('recentRatings', $recentRatings);

    $user = $doctor->user;
    $user->setRelation('doctor', $doctor);

    return ApiResponse::success(
        new DoctorResource($user),
        __('Doctor retrieved successfully')
    );
}
```

**التدفق:**
```
1. Doctor::where('user_id', $doctor)->firstOrFail() — بحث بـ user_id
2. with('user.roles', 'schedules') — eager load
3. loadCount('ratings') — عدد التقييمات
4. loadAvg('ratings', 'rating') — متوسط التقييمات
5. if patient → SupervisionRequest::where(patient_id, doctor_id) + Rating::where(rater_id, rateable_id) — تحميل طلب الإشراف و has_rated
6. ratings()->with('rater')->latest()->limit(5) — آخر 5 تقييمات مع الرائيتر
7. setRelation('recentRatings', ...) — تخزينها مؤقتاً
8. $user->setRelation('doctor', $doctor) — ربط doctor بالمستخدم
9. new DoctorResource($user)
10. return 200
```

---

## 3. الـ Resource: `DoctorResource`

**الملف:** `app/Domains/Doctors/Resources/DoctorResource.php`

```php
public function toArray($request): array
{
    $data = [
        'specialization' => ...,
        'experience_months' => ...,
        'schedules' => ...,
        'rating' => [
            'avg' => round((float) ($this->doctor?->ratings_avg_rating ?? 0), 1),
            'count' => (int) ($this->doctor?->ratings_count ?? 0),
            'recent' => $this->doctor?->recentRatings?->map(fn ($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'rater' => $r->rater ? [
                    'id' => $r->rater->id,
                    'first_name' => $r->rater->first_name,
                    'last_name' => $r->rater->last_name,
                ] : null,
                'created_at' => $r->created_at,
            ]) ?? [],
        ],
    ];

    if ($request->user()?->patient && $this->doctor) {
        $data['supervision_request'] = [
            'has_request' => isset($this->doctor->supervision_request_status),
            'status' => $this->doctor->supervision_request_status ?? null,
        ];

        $data['has_rated'] = $this->has_rated_doctor ?? false;
    }

    return array_merge(parent::toArray($request), $data);
}
```

---

## 4. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Doctor retrieved successfully",
    "data": {
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
        "supervision_request": {
            "has_request": false,
            "status": null
        },
        "has_rated": false,
        "rating": {
            "avg": 4.5,
            "count": 12,
            "recent": [
                {
                    "id": "0196f0a0-...",
                    "rating": 5,
                    "comment": "Excellent doctor, very professional",
                    "rater": {
                        "id": "0196f0a0-...",
                        "first_name": "Ahmed",
                        "last_name": "Ali"
                    },
                    "created_at": "2026-06-02T10:00:00.000000Z"
                }
            ]
        }
    }
}
```

### ❌ Unauthenticated — `401`

```json
{
    "status": 401,
    "message": "Unauthenticated"
}
```

### ❌ Not Found — `404`

```json
{
    "status": 404,
    "message": "Resource not found"
}
```

---

## 5. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Doctor retrieved successfully` | نجاح |
| `401` | `Unauthenticated` | لم يتم تسجيل الدخول |
| `404` | `Resource not found` | الـ UUID غير موجود |
