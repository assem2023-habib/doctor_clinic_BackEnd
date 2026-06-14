# Assign & Remove

> Staff-only operations to manage which patients are supervised by which doctors.

## Route Information

| Method | Path | Middleware |
|--------|------|------------|
| POST | `/v1/doctors/{doctor}/patients` | `auth:api`, `staff` |
| DELETE | `/v1/doctors/{doctor}/patients/{patient}` | `auth:api`, `staff` |

## Assign Patient

### Request

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `patient_id` | string | required, exists:patients,user_id | Patient UUID (user_id) to assign |
| `notes` | string | nullable, max:1000 | Assignment notes |

```json
{
  "patient_id": "0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d",
  "notes": "Weekly monitoring required"
}
```

### Action: `AssignPatientToDoctorAction`

```php
$assignedBy = "{$assigner->id}: {$assigner->first_name} {$assigner->last_name}";

$doctor->patients()->syncWithoutDetaching([
    $patient->id => [
        'assigned_by' => $assignedBy,
        'notes' => $notes,
    ],
]);
```

Uses `syncWithoutDetaching` — if already assigned, the call has no effect and existing pivot data is preserved.

### Response

```json
{
  "success": true,
  "message": "Patient assigned to doctor successfully",
  "data": null
}
```

## Remove Patient

### Request

No body required. The doctor and patient are identified via route parameters:

```
DELETE /v1/doctors/{doctor}/patients/{patient}
```

### Action: `RemovePatientFromDoctorAction`

```php
$doctor->patients()->detach($patient->id);
```

### Response

```json
{
  "success": true,
  "message": "Patient removed from doctor successfully",
  "data": null
}
```

## Sequence Diagram

```
Staff       SupervisionController    AssignAction / RemoveAction    DoctorPatient Pivot
  │                   │                        │                        │
  │── POST /assign ──>│                        │                        │
  │                   │── guard: staff ────────│                        │
  │                   │── execute() ──────────>│                        │
  │                   │                        │── syncWithoutDetaching │
  │                   │                        │   / detach() ─────────>│
  │                   │<── done ───────────────│                        │
  │                   │<── JSON response ──────│                        │
  │<── 200 OK ────────│                        │                        │
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden (user is not staff) |
| 404 | Doctor or patient not found |
| 422 | Validation failed (invalid patient_id) |
