# Cancel, Complete & Suggest Alternative

> Three staff/doctor-only actions for managing appointment lifecycle after the initial request.

## Cancel Appointment

- **Method:** `POST /v1/appointments/{appointment}/cancel`
- **Middleware:** `auth:api`
- **Who can call:** Admin, Receptionist, or the appointment's Doctor

### Request

No body required — the appointment is cancelled with a status transition tracked in `AppointmentStatusLog`.

### Action: `CancelAppointmentAction`

```php
$oldStatus = $appointment->status;
$appointment->update(['status' => AppointmentStatusEnum::Cancelled]);
AppointmentStatusLog::create([
    'appointment_id' => $appointment->id,
    'old_status' => $oldStatus,
    'new_status' => AppointmentStatusEnum::Cancelled,
    'changed_by' => "{$user->id}: {$user->first_name} {$user->last_name}",
]);
```

### Notification

Fires `appointment.cancelled` to both the patient and doctor.

### Response

```json
{
  "success": true,
  "message": "Appointment cancelled successfully",
  "data": {
    "id": "0194f1e2-6a7b-8f90-0c6d-1e2f3a4b5c6d",
    "status": "cancelled",
    "patient": { ... },
    "doctor": { ... }
  }
}
```

---

## Complete Appointment

- **Method:** `POST /v1/appointments/{appointment}/complete`
- **Middleware:** `auth:api`
- **Who can call:** Admin, Receptionist, or the appointment's Doctor

### State Guard

Status must be `Accepted` — otherwise returns 400.

### Action: `CompleteAppointmentAction`

```php
$oldStatus = $appointment->status;
$appointment->update(['status' => AppointmentStatusEnum::Completed]);
AppointmentStatusLog::create([
    'appointment_id' => $appointment->id,
    'old_status' => $oldStatus,
    'new_status' => AppointmentStatusEnum::Completed,
    'changed_by' => "{$user->id}: {$user->first_name} {$user->last_name}",
]);
```

### Notification

Fires `appointment.completed` to the patient.

### Response

```json
{
  "success": true,
  "message": "Appointment completed successfully",
  "data": {
    "id": "0194f1e2-6a7b-8f90-0c6d-1e2f3a4b5c6d",
    "status": "completed",
    "patient": { ... },
    "doctor": { ... }
  }
}
```

---

## Suggest Alternative

- **Method:** `POST /v1/appointments/{appointment}/suggest-alternative`
- **Middleware:** `auth:api`
- **Who can call:** Admin, Receptionist, or the appointment's Doctor

### State Guard

Status must be `Requested` — the appointment hasn't had a time set yet.

### Request

```json
{
  "message": "Dr. Smith is unavailable on June 10. Would June 12 at 14:00 work?"
}
```

### Action: `SuggestAlternativeAction`

```php
$existingNotes = $appointment->notes;
$newNotes = "Staff suggestion: {$data->message}";
$appointment->update([
    'notes' => $existingNotes
        ? $existingNotes . "\n\n" . $newNotes
        : $newNotes,
]);
```

Does **not** change the status — stays `Requested`. The staff should then call `set-time` with a new slot.

### Notification

Fires `appointment.alternative_suggested` to the patient.

### Response

```json
{
  "success": true,
  "message": "Alternative suggested successfully",
  "data": {
    "id": "0194f1e2-6a7b-8f90-0c6d-1e2f3a4b5c6d",
    "status": "requested",
    "notes": "Preferred date: 2026-06-05\n\nStaff suggestion: Dr. Smith is unavailable on June 10. Would June 12 at 14:00 work?",
    "patient": { ... },
    "doctor": { ... }
  }
}
```

---

## Sequence Diagram

```
Staff   AppointmentController   CancelAction / CompleteAction / SuggestAction   NotificationManager
  │              │                        │                           │
  │── POST /cancel|complete|suggest ─────>│                           │
  │              │── guard: staff/doctor ─│                           │
  │              │── status guard ────────│                           │
  │              │── execute() ──────────>│                           │
  │              │                        │── update status / notes   │
  │              │                        │── create StatusLog        │
  │              │<── Appointment ────────│                           │
  │              │── send notification ──────────────────────────────>│
  │<── 200 OK ───│                        │                           │
```

## Errors

| Status | Condition |
|--------|-----------|
| 400 | Invalid status transition (e.g. complete on non-accepted, suggest on non-requested) |
| 401 | Unauthenticated |
| 403 | Forbidden (not staff/doctor or not appointment's doctor) |
| 404 | Appointment not found |
| 422 | Validation failed (suggest alternative needs `message`) |
