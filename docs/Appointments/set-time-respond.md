# Set Time & Patient Response

> Two-step workflow: staff/doctor sets a concrete date/time for a requested appointment → patient accepts or rejects it.

## Set Time

- **Method:** `POST /v1/appointments/{appointment}/set-time`
- **Middleware:** `auth:api`
- **Who can call:** Admin, Receptionist, or the appointment's Doctor

### State Guard

Status must be `Requested` — otherwise returns 400.

### Request

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `appointment_date` | string | required, date, after_or_equal:today, must exist in doctor's schedule | Proposed date (day-of-week must be in doctor's active schedule) |
| `start_time` | string | required, format H:i, overlap check, must be within doctor's schedule hours | Start time |
| `end_time` | string | required, format H:i, after:start_time | End time |

```json
{
  "appointment_date": "2026-06-10",
  "start_time": "10:00",
  "end_time": "12:00"
}
```

### Schedule Validation

The `WithinDoctorSchedule` validation rule checks that the appointment date's day of week exists in the doctor's active `DoctorSchedule` records. When `start_time` and `end_time` are also provided, they are validated to fall within one of the doctor's schedule time ranges for that day.

### Overlap Prevention

The `NoOverlappingAppointment` validation rule checks via `AppointmentRepositoryInterface::hasOverlap()`:

```php
Appointment::where('doctor_id', $doctorId)
    ->whereDate('appointment_date', $date)
    ->whereNotIn('status', [AppointmentStatusEnum::Cancelled])
    ->where('start_time', '<', $endTime)
    ->where('end_time', '>', $startTime)
    ->exists();
```

### Action: `SetAppointmentTimeAction`

```php
$appointment->update([
    'appointment_date' => $data->appointmentDate,
    'start_time' => $data->startTime,
    'end_time' => $data->endTime,
    'status' => AppointmentStatusEnum::Set,
]);
// Creates AppointmentStatusLog (Requested → Set)
// Dispatches AutoConfirmAppointment job with configurable delay
```

### Auto-Confirm Job

After setting the time, a queued `AutoConfirmAppointment` job is dispatched with a delay of `config('appointment.response_window_hours')` (default 6 hours). If the patient hasn't responded by then, the appointment auto-transitions to `Accepted`:

```php
if (!$appointment || $appointment->status !== AppointmentStatusEnum::Set) {
    return; // skip if already responded or cancelled
}
$appointment->update(['status' => AppointmentStatusEnum::Accepted]);
```

### Notification

Fires `appointment.time_set` to the patient.

---

## Patient Response

- **Method:** `POST /v1/appointments/{appointment}/respond`
- **Middleware:** `auth:api`
- **Who can call:** The appointment's Patient (via `patient_id`)

### State Guard

Status must be `Set` — otherwise returns 400.

### Request

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `response` | string | required, enum: `accepted`, `rejected` | Patient's decision |

```json
{
  "response": "accepted"
}
```

### Action: `PatientRespondAction`

```php
$newStatus = match ($response) {
    PatientResponseEnum::Accepted => AppointmentStatusEnum::Accepted,
    PatientResponseEnum::Rejected => AppointmentStatusEnum::Rejected,
};
$appointment->update(['status' => $newStatus]);
// Creates AppointmentStatusLog (Set → Accepted|Rejected)
```

### Notification

Fires `appointment.accepted` or `appointment.rejected` to the doctor.

---

## Response

```json
{
  "success": true,
  "message": "Appointment confirmed successfully",
  "data": {
    "id": "0194f1e2-6a7b-8f90-0c6d-1e2f3a4b5c6d",
    "status": "accepted",
    "appointment_date": "2026-06-10",
    "start_time": "10:00",
    "end_time": "12:00",
    "patient": { ... },
    "doctor": { ... }
  }
}
```

## Sequence Diagram

```
Staff           AppointmentController    SetTimeAction    AutoConfirmJob    Patient    RespondAction
  │                     │                    │                │               │             │
  │── POST /set-time ──>│                    │                │               │             │
  │                     │── guard: staff/doctor               │               │             │
  │                     │── overlap validation                │               │             │
  │                     │── SetTimeData                       │               │             │
  │                     │── execute() ───────>│                │               │             │
  │                     │                    │── update(status=Set)           │             │
  │                     │                    │── log Requested→Set            │             │
  │                     │                    │<── Appointment                  │             │
  │                     │── dispatch job ────│                │               │             │
  │                     │── notify patient ──│                │               │             │
  │<── 200 OK ──────────│                    │                │               │             │
  │                     │                    │                │               │             │
  │                     │                    │    (6 hours later)             │             │
  │                     │                    │                │── handle()    │             │
  │                     │                    │                │── if still Set│             │
  │                     │                    │                │── auto-accept │             │
  │                     │                    │                │               │             │
  │                     │                    │                │  OR            │             │
  │                     │                    │                │               │             │
  │                     │                    │                │               │── POST /respond{"accepted"} │
  │                     │                    │                │               │── guard: patient │
  │                     │                    │                │               │── respondAction  │
  │                     │                    │                │               │── status=Accepted│
  │                     │                    │                │               │<── 200 OK ──────│
```

## Errors

| Status | Condition |
|--------|-----------|
| 400 | Invalid status transition (e.g. not in `Requested` for set-time, not in `Set` for respond) |
| 401 | Unauthenticated |
| 403 | Forbidden (wrong role for set-time, not the patient for respond) |
| 404 | Appointment not found |
| 409 | Overlapping appointment detected (set-time) |
| 422 | Validation failed — includes schedule validation (`doctor_not_working_that_day`, `outside_doctor_schedule`) |
