# Ratings API

## Overview

تصنيف وتقييم المستخدمين والخدمات والمراكز ونظام المواعيد. كل مستخدم يمكنه تقييم كيان واحد مرة واحدة فقط (يمنع التكرار).

- **Prefix:** `/api/v1/ratings`
- **Middleware:** `auth:api`, `active`

---

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/ratings` | List all ratings (paginated, filterable) |
| `GET` | `/api/v1/ratings/{rating}` | Get a single rating |
| `POST` | `/api/v1/ratings` | Create a new rating |
| `PUT` | `/api/v1/ratings/{rating}` | Update your own rating |
| `DELETE` | `/api/v1/ratings/{rating}` | Delete a rating (owner or admin) |

---

## 1. List Ratings

```
GET /api/v1/ratings
Authorization: Bearer <token>
```

### Query Parameters (Filters)

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `type` | string | Filter by type | `user`, `service`, `center`, `appointment_system` |
| `rater_id` | uuid | Filter by rater (who gave the rating) | `019e1d0f-...` |
| `rateable_id` | uuid | Filter by rateable entity ID | `019e1d0f-...` |
| `rateable_type` | string | Filter by rateable entity class | `App\Models\User` |
| `rating` | integer | Filter by value (1-5) | `5` |
| `limit` | integer | Items per page (max 100, default 20) | `50` |
| `page` | integer | Page number | `1` |

### Response (200)

```json
{
    "status": 200,
    "message": "Ratings retrieved successfully",
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
            "created_at": "2026-05-24T10:00:00.000000Z",
            "updated_at": "2026-05-24T10:00:00.000000Z"
        }
    ],
    "meta": {
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "limit": 20,
            "total": 100,
            "hasNextPage": true,
            "hasPreviousPage": false
        }
    }
}
```

### Notes
- `rater` relationship يتم تحميله (`eager-loaded`) تلقائياً
- `rateable` relationship **ليس** محمولاً — يعود فقط بـ `rateable_id` و `rateable_type`
- جميع الفلاتر اختيارية ويمكن دمجها

---

## 2. Get Single Rating

```
GET /api/v1/ratings/{rating}
Authorization: Bearer <token>
```

### Response (200)

```json
{
    "status": 200,
    "message": "Rating retrieved successfully",
    "data": {
        "id": "019e1d0f-...",
        "type": "user",
        "rater": { ... },
        "rateable_id": "019e1d0f-...",
        "rateable_type": "App\\Models\\User",
        "rating": 5,
        "comment": "Excellent doctor",
        "created_at": "...",
        "updated_at": "..."
    }
}
```

### Errors
| Status | Message |
|--------|---------|
| 404 | Resource not found |

---

## 3. Create Rating

```
POST /api/v1/ratings
Authorization: Bearer <token>
Content-Type: application/json
```

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `type` | string | **yes** | One of: `user`, `service`, `center`, `appointment_system` |
| `rateable_id` | string | **when type=user** | UUID of the rated entity |
| `rateable_type` | string | **when type=user** | Class name of the rated entity |
| `rating` | integer | **yes** | Value between 1–5 |
| `comment` | string | no | Max 1000 characters |

### Examples

**Rating a user (doctor):**
```json
{
    "type": "user",
    "rateable_id": "019e1d0f-...",
    "rateable_type": "App\\Models\\User",
    "rating": 5,
    "comment": "Excellent doctor, very professional"
}
```

**Service rating (no rateable_id/type):**
```json
{
    "type": "service",
    "rating": 4,
    "comment": "Good service overall"
}
```

### Response (201)

```json
{
    "status": 201,
    "message": "Rating created successfully",
    "data": {
        "id": "019e1d0f-...",
        "type": "user",
        "rater": { ... },
        "rateable_id": "019e1d0f-...",
        "rateable_type": "App\\Models\\User",
        "rating": 5,
        "comment": "Excellent doctor",
        "created_at": "...",
        "updated_at": "..."
    }
}
```

### Business Rules
- ✅ **ممنوع التكرار:** لا يمكن لنفس المستخدم تقييم نفس الكيان مرتين (يتم التحقق من `rater_id` + `type` + `rateable_id` + `rateable_type`)
- ✅ **rateable_id/type فقط عند type=user:** للأنواع الأخرى (`service`, `center`, `appointment_system`) لا حاجة لإرسال rateable_id/type

### Errors
| Status | Message |
|--------|---------|
| 409 | You have already rated this entity |
| 422 | Validation failed |

---

## 4. Update Rating

```
PUT /api/v1/ratings/{rating}
Authorization: Bearer <token>
Content-Type: application/json
```

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `rating` | integer | **yes** | Value between 1–5 |
| `comment` | string | no | Max 1000 characters |

### Example

```json
{
    "rating": 4,
    "comment": "Updated review"
}
```

### Response (200)

```json
{
    "status": 200,
    "message": "Rating updated successfully",
    "data": { ... }
}
```

### Business Rules
- ✅ **المالك فقط:** يمكن فقط للمستخدم الذي أنشأ التقييم تحديثه
- ✅ **يمكن تغيير القيمة والتعليق فقط:** لا يمكن تغيير type أو rateable_id/type

### Errors
| Status | Message |
|--------|---------|
| 403 | You can only update your own ratings |
| 404 | Resource not found |
| 422 | Validation failed |

---

## 5. Delete Rating

```
DELETE /api/v1/ratings/{rating}
Authorization: Bearer <token>
```

### Response (204)

```json
{
    "status": 204,
    "message": "Rating deleted successfully",
    "data": null
}
```

### Business Rules
- ✅ **المالك أو الأدمن:** يمكن للمستخدم الذي أنشأ التقييم أو أي مستخدم لديه صلاحية `admin` حذف التقييم
- ✅ **حذف نهائي:** التقييم يُحذف نهائياً من قاعدة البيانات

### Errors
| Status | Message |
|--------|---------|
| 403 | You can only delete your own ratings |
| 404 | Resource not found |

---

## Rating Types (`RatingTypeEnum`)

| Value | Description | rateable_id | rateable_type |
|-------|-------------|-------------|---------------|
| `user` | تقييم مستخدم (طبيب، مريض، إلخ) | ✅ مطلوب | ✅ مطلوب |
| `service` | تقييم الخدمة المقدمة | ❌ null | ❌ null |
| `center` | تقييم المركز الطبي | ❌ null | ❌ null |
| `appointment_system` | تقييم نظام المواعيد | ❌ null | ❌ null |

---

## Architecture

```
app/Domains/Ratings/
├── Actions/
│   ├── CreateRatingAction.php       # منع التكرار + إنشاء
│   ├── UpdateRatingAction.php       # التحقق من الملكية + تحديث
│   └── DeleteRatingAction.php       # التحقق من الملكية/الأدمن + حذف
├── Controllers/
│   └── RatingController.php         # 5 endpoints
├── DTOs/
│   └── RatingData.php               # fromStoreRequest / fromUpdateRequest
├── Models/
│   └── Rating.php                   # rater(), rateable() (morphTo)
├── Requests/
│   ├── StoreRatingRequest.php       # validation: type, rating 1-5, rateable required_if type=user
│   └── UpdateRatingRequest.php      # validation: rating 1-5, comment max 1000
└── Resources/
    └── RatingResource.php           # id, type, rater, rateable_id, rateable_type, rating, comment
```

## Database Schema

```sql
ratings
├── id              uuid PK
├── rater_id        FK → users              -- من قام بالتقييم
├── type            enum (RatingTypeEnum)    -- user, service, center, appointment_system
├── rateable_id     uuid nullable           -- ID الكيان المُقيّم (لنوع user فقط)
├── rateable_type   string nullable         -- class الكيان المُقيّم (لنوع user فقط)
├── rating          tinyint unsigned (1-5)  -- القيمة
├── comment         text nullable           -- التعليق
├── created_at      timestamp
└── updated_at      timestamp

Index: (rateable_type, rateable_id)
Index: (rater_id)
```

> **ملاحظة:** `rateable_id` + `rateable_type` هما polymorphic relationship — يُستخدمان فقط عندما `type = user`. للأنواع الأخرى يكونان `null`.
