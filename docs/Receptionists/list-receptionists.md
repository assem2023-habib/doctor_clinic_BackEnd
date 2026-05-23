# GET /api/v1/receptionists

قائمة موظفي الاستقبال مع البحث والفلترة.

---

## 1. Route Info

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **URL** | `/api/v1/receptionists` |
| **Auth** | ❌ عام |
| **Middleware** | لا يوجد |

---

## 2. Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | `string` | — | فلترة بالاسم أو الإيميل |
| `gender` | `string` | — | فلترة بالجنس (male/female) |
| `date_from` | `date` | — | birthday_date من |
| `date_to` | `date` | — | birthday_date إلى |
| `is_active` | `boolean` | — | فلترة بالحالة (true/false) |
| `shift_start_from` | `time` | — | بداية الوردية من (H:i) |
| `shift_start_to` | `time` | — | بداية الوردية إلى (H:i) |
| `shift_end_from` | `time` | — | نهاية الوردية من (H:i) |
| `shift_end_to` | `time` | — | نهاية الوردية إلى (H:i) |
| `limit` | `integer` | `20` | عدد العناصر (max: 100) |

---

## 3. الـ Controller

**الملف:** `app/Http/Controllers/Api/V1/Receptionist/ReceptionistController.php`

```php
public function index(Request $request): JsonResponse
{
    $receptionists = User::whereHas('roles', fn($q) => $q->where('slug', 'receptionist'))
        ->with('receptionist', 'roles')
        ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
            $q->where('first_name', 'like', "%{$v}%")
              ->orWhere('last_name', 'like', "%{$v}%")
              ->orWhere('email', 'like', "%{$v}%");
        }))
        ->when($request->gender, fn ($q, $v) => $q->where('gender', $v))
        ->when($request->date_from, fn ($q, $v) => $q->where('birthday_date', '>=', $v))
        ->when($request->date_to, fn ($q, $v) => $q->where('birthday_date', '<=', $v))
        ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
        ->when($request->shift_start_from, fn ($q, $v) => $q->whereHas('receptionist', fn ($q) => $q->where('shift_start', '>=', $v)))
        ->when($request->shift_start_to, fn ($q, $v) => $q->whereHas('receptionist', fn ($q) => $q->where('shift_start', '<=', $v)))
        ->when($request->shift_end_from, fn ($q, $v) => $q->whereHas('receptionist', fn ($q) => $q->where('shift_end', '>=', $v)))
        ->when($request->shift_end_to, fn ($q, $v) => $q->whereHas('receptionist', fn ($q) => $q->where('shift_end', '<=', $v)))
        ->paginate(min($limit, 100));

    return ApiResponse::success(
        ReceptionistResource::collection($receptionists),
        __('Receptionists retrieved successfully'),
        pagination: ApiResponse::pagination($receptionists)
    );
}
```

**التدفق:**
```
1. User::whereHas('roles', slug=receptionist)
2. with('receptionist', 'roles') — eager load
3. if search → filter by first_name, last_name, email
4. if gender → where gender
5. if date_from/to → where birthday_date >= / <=
6. if is_active → where is_active
7. if shift_start_from/to → whereHas receptionist.shift_start >= / <=
8. if shift_end_from/to → whereHas receptionist.shift_end >= / <=
9. paginate(min(limit, 100))
10. ReceptionistResource::collection()
11. return 200
```
