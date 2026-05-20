# List & Get Appointments

> Retrieve appointments. `index` is role-scoped (patients see only their own, doctors see theirs, staff see all). `show` is access-checked via `canAccess()`.

## Route Information

| Method | Path | Middleware |
|--------|------|------------|
| GET | `/v1/appointments` | `auth:api` |
| GET | `/v1/appointments/{appointment}` | `auth:api` |

## List Request

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `limit` | integer | 20 | 1–100 | Items per page |
| `status` | string | — | enum `AppointmentStatusEnum` | Filter by status |
| `date` | string | — | date (Y-m-d) | Filter by appointment date |

### Example

```
GET /v1/appointments?status=requested&date=2026-06-01&limit=10
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
        "specialization": "cardiology",
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
