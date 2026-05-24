# Ratings Domain — التوثيق الكامل

> نظام التقييم — تقييم الأطباء والخدمات والمركز ونظام المواعيد.

## قائمة الـ Endpoints

| # | Method | Endpoint | Auth | الوصف |
|---|--------|----------|------|-------|
| 1 | `GET` | `/api/v1/ratings` | ✅ auth:api | قائمة التقييمات مع الفلترة |
| 2 | `GET` | `/api/v1/ratings/{rating}` | ✅ auth:api | عرض تقييم |
| 3 | `POST` | `/api/v1/ratings` | ✅ auth:api | إنشاء تقييم جديد |
| 4 | `PUT` | `/api/v1/ratings/{rating}` | ✅ auth:api | تحديث تقييم (فقط صاحبه) |
| 5 | `DELETE` | `/api/v1/ratings/{rating}` | ✅ auth:api | حذف تقييم (صاحبه أو الأدمن) |

## الموديلات

| الموديل | الجدول | الحقول |
|---------|--------|--------|
| `Rating` | `ratings` | id, rater_id, type, rateable_id, rateable_type, rating, comment, timestamps |

### علاقات Rating:
- `rater()` — BelongsTo → User (المقيّم)
- `rateable()` — MorphTo (الكيان الذي يتم تقييمه)

### أنواع التقييم (RatingTypeEnum):
- `user` — تقييم مستخدم (دكتور)
- `service` — تقييم الخدمة
- `center` — تقييم المركز
- `appointment_system` — تقييم نظام المواعيد

## هيكل المجلدات

```
app/Domains/Ratings/
├── Actions/
│   ├── CreateRatingAction.php
│   ├── UpdateRatingAction.php
│   └── DeleteRatingAction.php
├── Controllers/
│   └── RatingController.php
├── DTOs/
│   └── RatingData.php
├── Models/
│   └── Rating.php
├── Requests/
│   ├── StoreRatingRequest.php
│   └── UpdateRatingRequest.php
└── Resources/
    └── RatingResource.php
```

## التوثيق

### Store Rating

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `type` | string | required, in: user/service/center/appointment_system | نوع التقييم |
| `rateable_id` | string | required_if:type,user | UUID الكيان المُقيَّم |
| `rateable_type` | string | required_if:type,user | كلاس الكيان المُقيَّم |
| `rating` | integer | required, 1-5 | التقييم |
| `comment` | string | nullable, max:1000 | تعليق |

```json
{
  "type": "user",
  "rateable_id": "019e1d0f-...",
  "rateable_type": "App\\Models\\User",
  "rating": 5,
  "comment": "Excellent doctor"
}
```

### Update Rating

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `rating` | integer | required, 1-5 | التقييم |
| `comment` | string | nullable, max:1000 | تعليق |

### Response (RatingResource)

```json
{
  "id": "019e1d0f-...",
  "type": "user",
  "rater": {
    "id": "019e1d0f-...",
    "first_name": "Ahmed",
    "last_name": "Ali",
    "email": "patient@example.com"
  },
  "rateable_id": "019e1d0f-...",
  "rateable_type": "App\\Models\\User",
  "rating": 5,
  "comment": "Excellent doctor",
  "created_at": "2026-05-24T00:00:00.000000Z",
  "updated_at": "2026-05-24T00:00:00.000000Z"
}
```

### أخطاء

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden (ليس صاحب التقييم) |
| 404 | Not found |
| 409 | مقيَّم مسبقاً (duplicate) |
| 422 | Validation failed |
