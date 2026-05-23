# CRUD Operations — Receptionists

---

## PUT /api/v1/receptionists/{receptionist}

تحديث كامل لموظف الاستقبال.

### Route Info

| Field | Value |
|-------|-------|
| **Method** | `PUT` |
| **URL** | `/api/v1/receptionists/{receptionist}` |
| **Auth** | ✅ `auth:api` + `active` + `admin` |

### Request Body (JSON)

```json
{
    "first_name": "Layla",
    "last_name": "Hassan Updated",
    "username": "laylah",
    "email": "receptionist@example.com",
    "phone": "+963912345680",
    "address": "Homs, Syria",
    "gender": "female",
    "birthday_date": "1998-11-05",
    "shift_start": "09:00",
    "shift_end": "17:00"
}
```

### Response `200 OK`

```json
{
    "status": 200,
    "message": "Receptionist updated successfully",
    "data": { "...": "..." }
}
```

---

## PATCH /api/v1/receptionists/{receptionist}

تحديث جزئي — نفس الحقول ولكن كلها optional.

### Response `200 OK`

---

## DELETE /api/v1/receptionists/{receptionist}

حذف موظف الاستقبال.

### Route Info

| Field | Value |
|-------|-------|
| **Method** | `DELETE` |
| **URL** | `/api/v1/receptionists/{receptionist}` |
| **Auth** | ✅ `auth:api` + `active` + `admin` |

### Responses

| Code | Description |
|------|-------------|
| `204` | Receptionist deleted successfully |
| `409` | Receptionist has active appointments (لا يمكن الحذف) |
| `403` | Forbidden (admin only) |
| `401` | Unauthenticated |

---

## GET /api/v1/receptionists/{receptionist}

عرض موظف استقبال واحد.

### Route Info

| Field | Value |
|-------|-------|
| **Method** | `GET` |
| **URL** | `/api/v1/receptionists/{receptionist}` |
| **Auth** | ❌ عام |

### Response `200 OK`

```json
{
    "status": 200,
    "message": "Receptionist retrieved successfully",
    "data": {
        "id": "019e1d0f-...",
        "first_name": "Layla",
        "last_name": "Hassan",
        "username": "laylah",
        "email": "receptionist@example.com",
        "phone": "+963912345680",
        "address": "Homs, Syria",
        "gender": "female",
        "birthday_date": "1998-11-05",
        "is_active": true,
        "shift_start": "09:00",
        "shift_end": "17:00"
    }
}
```
