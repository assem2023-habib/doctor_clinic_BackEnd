# Appointments Domain

> Manages the full appointment lifecycle: request → set time → patient response → confirm → complete/cancel. Includes slot availability, overlap checking, auto-confirmation jobs, and status logging.

## Lifecycle

```
Requested → Set (by staff) → Accepted (by patient) → InProgress → Completed
                                                                   
Rejected (by patient) ──→ Cancelled

Alternative Suggested → (loops back to Set with new time)
Requested → Cancelled (by staff at any point)
Set → Auto-Confirmed (after response window expires via Job)
```

## Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/v1/doctors/{doctor}/available-slots` | None | Get available slots for a doctor on a date |
| GET | `/v1/appointments` | `auth:api` | List appointments (role-scoped) |
| GET | `/v1/appointments/{appointment}` | `auth:api` | Get a single appointment |
| POST | `/v1/appointments` | `auth:api` | Request a new appointment (patient only) |
| POST | `/v1/appointments/{appointment}/set-time` | `auth:api` | Set appointment time (staff/doctor) |
| POST | `/v1/appointments/{appointment}/respond` | `auth:api` | Patient accepts/rejects set time |
| POST | `/v1/appointments/{appointment}/start` | `auth:api` | Start appointment (staff/doctor — Accepted → InProgress) |
| POST | `/v1/appointments/{appointment}/cancel` | `auth:api` | Cancel appointment (staff/doctor) |
| POST | `/v1/appointments/{appointment}/complete` | `auth:api` | Complete appointment (staff/doctor — InProgress → Completed) |
| POST | `/v1/appointments/{appointment}/suggest-alternative` | `auth:api` | Suggest alternative (staff/doctor) |

## Architecture

```
AppointmentController
 ├── availableSlots() → AvailableSlotsService
 ├── index()          → AppointmentResource::collection (role-scoped query)
 ├── show()           → AppointmentResource (with canAccess guard)
 ├── store()          → RequestAppointmentAction → NotificationManager
 ├── setTime()        → SetAppointmentTimeAction → AutoConfirmAppointment Job → NotificationManager
 ├── respond()        → PatientRespondAction → NotificationManager
 ├── start()          → StartAppointmentAction → NotificationManager
 ├── cancel()         → CancelAppointmentAction → NotificationManager
 ├── complete()       → CompleteAppointmentAction → NotificationManager
 └── suggestAlternative() → SuggestAlternativeAction → NotificationManager
```

## Models

- **`Appointment`** (UUID v7): `doctor_id`, `patient_id`, `appointment_date`, `start_time`, `end_time`, `status`, `reason`, `notes`, `created_by`
- **`AppointmentStatusLog`** (UUID v7): `appointment_id`, `old_status`, `new_status`, `changed_by`, `created_at` (immutable audit trail)

## Keys

- **Status Engine:** `AppointmentStatusEnum` (9 states: pending, requested, set, accepted, rejected, in_progress, confirmed, cancelled, completed)
- **Overlap Prevention:** `NoOverlappingAppointment` rule → `AppointmentRepositoryInterface::hasOverlap()` → `EloquentAppointmentRepository`
- **Auto-Confirm:** `AutoConfirmAppointment` job dispatched with `(config:appointment.response_window_hours)` delay when staff sets a time
- **Notifications:** Each state transition fires a notification via `NotificationManager` (appointment.requested, .time_set, .accepted, .rejected, .in_progress, .cancelled, .completed, .alternative_suggested)
- **Slot Service:** `AvailableSlotsService` generates 2-hour slots from doctor schedules, excluding overlapping existing appointments (including in_progress)
- **Actions:** 7 actions (RequestAppointment, SetAppointmentTime, PatientRespond, StartAppointment, CancelAppointment, CompleteAppointment, SuggestAlternative) — each is a single-class use case
