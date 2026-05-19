# List Supervisions

> View the doctor-patient assignments. Doctors see their own patients; patients see their own doctors; staff see everything.

## Route Information

| Method | Path | Auth | Who Can Access |
|--------|------|------|----------------|
| GET | `/v1/doctors/{doctor}/patients` | `auth:api` | The doctor themselves, or staff |
| GET | `/v1/patients/{patient}/doctors` | `auth:api` | The patient themselves, or staff |

## Doctor's Patients (`doctorPatients`)

```php
$isDoctor = $user->id === $doctor->user_id;
$isStaff = in_array($user->role, [Admin, Receptionist]);

if (!$isDoctor && !$isStaff) {
    return ApiResponse::forbidden();
}

$patients = User::whereHas('patient', fn ($q) =>
    $q->whereIn('id', $doctor->patients()->pluck('patient_id'))
)->with(['patient', 'image'])->get();

// Attach pivot data to each user
$users->each(function ($user) use ($doctor) {
    $pivot = $doctor->patients()->find($user->patient->id)?->pivot;
    $user->setRelation('pivot', $pivot);
});
```

## Patient's Doctors (`patientDoctors`)

Same pattern — queries `User::whereHas('doctor')` with patient's doctor IDs and attaches pivot data.

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
  ]
}
```

The doctor variant (`SupervisionDoctorResource`) adds `specialization` and `experience_months` fields.

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden (not the doctor/patient themselves and not staff) |
| 404 | Doctor/patient not found |
