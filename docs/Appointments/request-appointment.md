# Request Appointment

> A patient requests a new appointment with a doctor. The appointment is created in `Requested` status.

## Route Information

- **Method:** `POST`
- **Path:** `/v1/appointments`
- **Middleware:** `auth:api`

## Request

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `doctor_id` | string | required, exists:doctors,id | Target doctor UUID |
| `preferred_date` | string | nullable, date, after_or_equal:today, must exist in doctor's schedule | Patient's preferred date (day-of-week must be in doctor's active schedule) |
| `reason` | string | nullable, max:2000 | Reason for visit |

### Example

```json
{
  "doctor_id": "0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d",
  "preferred_date": "2026-06-05",
  "reason": "Annual checkup"
}
```

## DTO: `RequestAppointmentData`

```php
public function __construct(
    public readonly string $doctorId,
    public readonly string $patientId,
    public readonly ?string $preferredDate,
    public readonly ?string $reason,
    public readonly string $createdBy,  // user ID
) {}
```

## Action: `RequestAppointmentAction`

```php
$appointment = Appointment::create([
    'doctor_id' => $data->doctorId,
    'patient_id' => $data->patientId,
    'medical_record_id' => $medicalRecordId,
    'status' => AppointmentStatusEnum::Requested,
    'reason' => $data->reason,
    'notes' => $data->preferredDate ? "Preferred date: {$data->preferredDate}" : null,
    'created_by' => $data->createdBy,
]);
```

Stores preferred date in the `notes` field as `"Preferred date: Y-m-d"`.

## Notification

Fires `appointment.requested` event to the doctor's user:

```php
$this->notificationManager->send('appointment.requested', new NotificationData(
    topic: 'appointment.requested',
    title: 'New Appointment Request',
    body: ['appointment_id', 'doctor_id', 'patient_id', 'reason'],
    userIds: [$appointment->doctor->user_id],
    type: 'appointment',
));
```

## Response

```json
{
  "success": true,
  "message": "Appointment requested successfully",
  "data": {
    "id": "0194f1e2-6a7b-8f90-0c6d-1e2f3a4b5c6d",
    "status": "requested",
    "reason": "Annual checkup",
    "notes": "Preferred date: 2026-06-05",
    "appointment_date": null,
    "start_time": null,
    "end_time": null,
    "created_by": "patient-user-uuid",
    "created_at": "2026-05-19T10:00:00+00:00",
    "updated_at": "2026-05-19T10:00:00+00:00",
    "patient": { ... },
    "doctor": { ... }
  }
}
```

## Sequence Diagram

```
Patient     AppointmentController    RequestAppointmentAction    NotificationManager
  │                   │                       │                       │
  │── POST /appointments ──>│                 │                       │
  │                   │── guard: only patient ─│                       │
  │                   │── RequestAppointmentData::fromRequest()       │
  │                   │── execute(data) ──────>│                      │
  │                   │                       │── Appointment::create │
  │                   │                       │── status: Requested   │
  │                   │<── Appointment ───────│                       │
  │                   │── load(patient, doctor, image)                │
  │                   │── send 'appointment.requested' ─────────────>│
  │                   │<── 201 Created ───────│                       │
  │<── 201 OK ────────│                       │                       │
```

## Errors

| Status | Condition |
|--------|-----------|
| 400 | Patient profile not found |
| 401 | Unauthenticated |
| 403 | Only patients can request appointments |
| 404 | Doctor not found |
| 422 | Validation failed — includes schedule validation (`doctor_not_working_that_day`) |
