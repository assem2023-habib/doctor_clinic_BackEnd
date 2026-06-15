# GET /api/v1/doctors/{doctor}/ratings

عرض تقييمات طبيب محدد مع بحث و pagination.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **URL** | `/api/v1/doctors/{doctor}/ratings` |
| **Auth** | ✅ مطلوب (`auth:api`, `active`) |
| **Middleware** | `auth:api`, `active` |

---

## 2. Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | `integer` | `20` | عدد العناصر (max: 100) |
| `page` | `integer` | `1` | رقم الصفحة |
| `search` | `string` | — | فلترة بتعليق التقييم أو اسم الرائيتر |

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Doctor/DoctorController.php`

```php
public function ratings(Request $request, string $doctor): JsonResponse
{
    $limit = (int) $request->integer('limit', 20);
    $version = Cache::get('doctors:cache_version', 0);
    $cacheKey = 'doctors:ratings:v' . $version . ':' . $doctor . ':' . md5(serialize($request->only(['search', 'page', 'limit'])));

    $paginator = Cache::remember($cacheKey, 172800, function () use ($request, $doctor, $limit) {
        $doctor = Doctor::where('user_id', $doctor)->firstOrFail();

        return $doctor->ratings()
            ->with('rater')
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('comment', 'like', "%{$v}%")
                  ->orWhereHas('rater', fn ($q) => $q->where('first_name', 'like', "%{$v}%")
                      ->orWhere('last_name', 'like', "%{$v}%"));
            }))
            ->latest()
            ->paginate(min($limit, 100));
    });

    return ApiResponse::success(
        RatingResource::collection($paginator),
        __('Doctor ratings retrieved successfully'),
        pagination: ApiResponse::pagination($paginator)
    );
}
```

**التدفق:**
```
1. Doctor::where('user_id', $doctor)->firstOrFail() — البحث بالـ user_id
2. $doctor->ratings() — علاقة التقييمات (type=user, rateable_type=User)
3. with('rater') — تحميل الرائيتر
4. if search → filter by comment or rater first_name/last_name
5. latest() — ترتيب من الأحدث
6. paginate(min(limit, 100))
7. RatingResource::collection()
8. return 200
```

---

## 4. الـ Resource: `RatingResource`

**الملف:** `app/Domains/Ratings/Resources/RatingResource.php`

```php
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'type' => $this->type,
        'rater' => new UserResource($this->whenLoaded('rater')),
        'rateable_id' => $this->rateable_id,
        'rateable_type' => $this->rateable_type,
        'rating' => $this->rating,
        'comment' => $this->comment,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}
```

---

## 5. Response

### ✅ Success — `200 OK`

```json
{
    "status": 200,
    "message": "Doctor ratings retrieved successfully",
    "data": [
        {
            "id": "019e1d0f-...",
            "type": "user",
            "rater": {
                "id": "019e1d0f-...",
                "first_name": "Ahmed",
                "last_name": "Ali",
                "email": "ahmed@example.com",
                ...
            },
            "rateable_id": "019e1d0f-...",
            "rateable_type": "App\\Models\\User",
            "rating": 5,
            "comment": "Excellent doctor",
            "created_at": "2026-06-02T10:00:00.000000Z",
            "updated_at": "2026-06-02T10:00:00.000000Z"
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

## 6. الأخطاء المحتملة

| كود الحالة | الرسالة | السبب |
|-----------|---------|-------|
| `200` | `Doctor ratings retrieved successfully` | نجاح |
| `401` | `Unauthenticated` | لم يتم تسجيل الدخول |
| `404` | `Resource not found` | الـ UUID للطبيب غير موجود |

---

## 7. Caching

| الخاصية | القيمة |
|---------|--------|
| **مخبأ** | ✅ نعم |
| **مدة التخزين** | 2 يوم (172800 ثانية) |
| **مفتاح الكاش** | `doctors:ratings:v{version}:{doctor_id}:{md5(filters)}` |
| **الإبطال** | إنشاء/تحديث/حذف طبيب أو إضافة تقييم جديد (`type=user`) |

---

## 8. ملاحظات

- التقييمات مرتبة من الأحدث (`latest()`)
- البحث (`search`) يطابق في `comment` أو `rater.first_name` أو `rater.last_name`
- `rater` relationship يتم تحميله (`eager-loaded`) تلقائياً
- جميع التقييمات من نوع `user` خاصة بهذا الطبيب
- النتيجة تُخبّأ لمدة يومين وتُبطل تلقائياً عند أي تعديل
