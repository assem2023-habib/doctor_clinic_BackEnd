# List & Get Appointments

> Retrieve appointments. `index` is role-scoped (patients see only their own, doctors see theirs, staff see all). `show` is access-checked via `canAccess()`.

## Routes

| Method | Path | Middleware |
|--------|------|------------|
| GET | `/v1/appointments` | `auth:api` |
| GET | `/v1/appointments/{appointment}` | `auth:api` |
| GET | `/v1/doctors/{doctor}/appointments` | `auth:api` |

## List Request (`/v1/appointments`)

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `limit` | integer | 20 | 1–100 | Items per page |
| `page` | integer | 1 | min:1 | Page number |
| `status` | string/array | — | enum `AppointmentStatusEnum` | Filter by status(es). Single (`?status=accepted`) or multiple (`?status[]=set&status[]=accepted`) |
| `date` | string | — | date (Y-m-d) | Filter by exact date. When set, `from_date`/`to_date` are ignored |
| `from_date` | string | — | date (Y-m-d) | Filter from date |
| `to_date` | string | — | date (Y-m-d) | Filter to date |
| `from_time` | string | — | H:i | Filter by `start_time >= value` |
| `to_time` | string | — | H:i | Filter by `end_time <= value` |
| `doctor_id` | string/array | — | uuid | Filter by doctor(s) using their User UUID (maps to doctors.user_id). Single (`?doctor_id=uuid`) or multiple (`?doctor_id[]=uuid1&uuid2`). Internally converted to doctor PK for DB query |
| `order_by` | string | `created_at` | `created_at`, `appointment_date`, `start_time` | Sort field |
| `order_dir` | string | `desc` | `asc`, `desc` | Sort direction |

### Examples

```
GET /v1/appointments?status=requested&date=2026-06-01&limit=10
GET /v1/appointments?status[]=set&status[]=accepted&from_date=2026-06-01&to_date=2026-06-30
GET /v1/appointments?from_time=09:00&to_time=17:00&order_by=appointment_date&order_dir=asc
GET /v1/appointments?doctor_id[]=uuid1&doctor_id[]=uuid2&limit=50
```

## Doctor Appointments (`/v1/doctors/{doctor}/appointments`)

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `date` | string | — | date (Y-m-d) | Filter by exact date |
| `from_date` | string | — | date (Y-m-d) | Filter from date |
| `to_date` | string | — | date (Y-m-d) | Filter to date |
| `status` | string | — | enum `AppointmentStatusEnum` | Filter by status |
| `limit` | integer | 20 | 1–100 | Items per page |
| `page` | integer | 1 | min:1 | Page number |

### Example

```
GET /v1/doctors/{doctor}/appointments?date=2026-06-01&status=accepted&limit=10&page=1
```

## Role-Based Scoping (`index`)

```php
if ($user->hasRole('patient')) {
    $query->where('patient_id', $patient->id);
} elseif ($user->hasRole('doctor')) {
    $query->where('doctor_id', $doctor->id);
} elseif (!$user->hasAnyRole(['admin', 'receptionist'])) {
    return ApiResponse::forbidden(__('Unauthorized'));
}
```

## Access Check (`show`)

```php
private function canAccess($user, Appointment $appointment): bool
{
    // Admin/Receptionist → all appointments
    // Patient → only their own
    // Doctor → only their own
    // Others → denied
}
```

## Response

```json
{
  "success": true,
  "message": "Appointments retrieved successfully",
  "data": [
    {
      "id": "0194f1e2-6a7b-8f90-0c6d-1e2f3a4b5c6d",
      "status": "requested",
      "reason": "Check-up",
      "notes": "Preferred date: 2026-06-01",
      "appointment_date": null,
      "start_time": null,
      "end_time": null,
      "created_by": "user-uuid",
      "created_at": "2026-05-19T10:00:00+00:00",
      "updated_at": "2026-05-19T10:00:00+00:00",
      "patient": {
        "id": "patient-user-uuid",
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "+123456789",
        "gender": "male",
        "birthday_date": "1990-01-15",
        "image": {
          "id": "image-uuid",
          "url": "/storage/images/abc.jpg",
          "type": "App\\Models\\User",
          "imageable_id": "patient-user-uuid",
          "created_at": "2026-05-19T09:00:00.000000Z"
        }
      },
      "doctor": {
        "id": "doctor-user-uuid",
        "first_name": "Jane",
        "last_name": "Smith",
        "email": "jane@clinic.com",
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
        "image": {
          "id": "image-uuid-2",
          "url": "/storage/images/def.jpg",
          "type": "App\\Models\\User",
          "imageable_id": "doctor-user-uuid",
          "created_at": "2026-05-19T09:00:00.000000Z"
        }
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 5,
    "last_page": 1,
    "next_page_url": null,
    "prev_page_url": null
  }
}
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden (invalid role for list, or not owner/staff for show) |
| 404 | Appointment not found (show) |
