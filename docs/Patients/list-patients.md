# List Patients

> Retrieve a paginated list of patients with optional search filtering.

## Route Information

- **Method:** `GET`
- **Path:** `/v1/patients`
- **Middleware:** `auth:api`, `staff:doctor`

## Request

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `limit` | integer | 20 | max 100 | Items per page |
| `search` | string | — | — | Search term for `first_name`, `last_name`, or `email` (LIKE match) |
| `gender` | string | — | `male` / `female` | Filter by gender |
| `date_from` | date | — | YYYY-MM-DD | Filter by birthday from (inclusive) |
| `date_to` | date | — | YYYY-MM-DD | Filter by birthday to (inclusive) |
| `is_active` | boolean | — | `1` / `0` | Filter by active status |

### Example

```
GET /v1/patients?limit=10&search=john&gender=male&is_active=1
```

## DTO

No DTO — query parameters used directly via `$request->integer()` and `$request->search`.

## Controller Logic (`index`)

```php
$limit = (int) $request->integer('limit', 20);
$patients = User::whereHas('roles', fn ($q) => $q->where('slug', 'patient'))
    ->with('patient', 'roles')
    ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
        $q->where('first_name', 'like', "%{$v}%")
          ->orWhere('last_name', 'like', "%{$v}%")
          ->orWhere('email', 'like', "%{$v}%");
    }))
    ->paginate(min($limit, 100));
```

## Response

```json
{
  "success": true,
  "message": "Patients retrieved successfully",
  "data": [
    {
      "id": "0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d",
      "first_name": "John",
      "last_name": "Doe",
      "username": "johndoe",
      "email": "john@example.com",
      "phone": "+123456789",
      "address": "123 Main St",
      "gender": "male",
      "birthday_date": "1990-01-15",
      "roles": [
        {
          "id": "...",
          "name": "Patient",
          "slug": "patient",
          "description": null,
          "guard_name": "api",
          "is_system": true,
          "created_at": "...",
          "updated_at": "..."
        }
      ],
      "is_active": true,
      "image": {
        "id": "0194f1e2-4a8b-7f90-9d7e-8f6a5b4c3d2e",
        "url": "/storage/images/user_abc123.jpg",
        "type": "App\\Models\\User",
        "imageable_id": "0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d",
        "created_at": "2026-05-19T10:00:00.000000Z"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 42,
    "last_page": 5,
    "next_page_url": "http://localhost/api/v1/patients?page=2",
    "prev_page_url": null
  }
}
```

## Sequence Diagram

```
Client          StaffMiddleware      PatientController          User Model
  │                    │                     │                     │
  │── GET /patients ──>│                     │                     │
   │                    │── pass (staff:doctor) ────>│                     │
  │                    │                     │── query(hasRole=patient, with=patient,roles) ──>│
  │                    │                     │<──── paginated results ───────────────│
  │                    │                     │── PatientResource::collection()       │
  │                    │<── JSON response ───│                                     │
  │<── 200 OK ─────────│                     │                                     │
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated (missing/invalid token) |
| 403 | Forbidden (user is not staff/doctor) |
| 422 | Invalid `limit` value |
