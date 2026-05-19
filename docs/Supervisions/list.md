# List Supervisions

> View the doctor-patient assignments. Doctors see their own patients; patients see their own doctors; staff see everything.

## Route Information

| Method | Path | Auth | Who Can Access |
|--------|------|------|----------------|
| GET | `/v1/doctors/{doctor}/patients` | `auth:api` | The doctor themselves, or staff |
| GET | `/v1/patients/{patient}/doctors` | `auth:api` | The patient themselves, or staff |

## Request Parameters

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `limit` | integer | 20 | 1–100 | Items per page |
| `search` | string | — | — | Search in `first_name`, `last_name`, or `email` (LIKE match) |

Both methods also support `page` for pagination (default 1).

### Example

```
GET /v1/doctors/{{doctor_id}}/patients?search=Ali&limit=10&page=1
```

## Doctor's Patients (`doctorPatients`)

```php
$isDoctor = $user->id === $doctor->user_id;
$isStaff = in_array($user->role, [Admin, Receptionist]);

if (!$isDoctor && !$isStaff) {
    return ApiResponse::forbidden();
}

$patients = User::whereHas('patient', fn ($q) =>
    $q->whereIn('id', $doctor->patients()->pluck('patient_id'))
)->with(['patient', 'image'])
  ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
      $q->where('first_name', 'like', "%{$v}%")
        ->orWhere('last_name', 'like', "%{$v}%")
        ->orWhere('email', 'like', "%{$v}%");
  }))
  ->paginate(min($limit, 100));

// Attach pivot data to each user
$patients->getCollection()->each(function ($user) use ($doctor) {
    $pivot = $doctor->patients()->find($user->patient->id)?->pivot;
    $user->setRelation('pivot', $pivot);
});
```

## Patient's Doctors (`patientDoctors`)

Same pattern — queries `User::whereHas('doctor')` with patient's doctor IDs, adds search/pagination, and attaches pivot data.

## Response

```json
{
  "success": true,
  "message": "Patients retrieved successfully",
  "data": [
    {
      "id": "user-uuid",
      "first_name": "John",
      "last_name": "Doe",
      "username": "johndoe",
      "email": "john@example.com",
      "phone": "+123456789",
      "address": "123 Main St",
      "gender": "male",
      "birthday_date": "1990-01-15",
      "role": "patient",
      "is_active": true,
      "image": { ... },
      "supervision": {
        "assigned_by": "admin-uuid: Admin Name",
        "notes": "Regular checkups",
        "assigned_at": "2026-05-19T10:00:00+00:00"
      }
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "limit": 10,
      "total": 15,
      "hasNextPage": true,
      "hasPreviousPage": false
    }
  }
}
```

The doctor variant (`SupervisionDoctorResource`) adds `specialization` and `experience_months` fields.

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden (not the doctor/patient themselves and not staff) |
| 404 | Doctor/patient not found |
