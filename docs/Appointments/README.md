# Appointments Domain

> Manages the full appointment lifecycle: request → set time → patient response → confirm → complete/cancel. Includes slot availability, overlap checking, auto-confirmation jobs, status logging, and Firebase Realtime Database synchronization.

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
| GET | `/v1/doctors/{doctor}/booked-slots` | None | Get booked slots for a doctor (future only, optional date range) |
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
 ├── bookedSlots()   → AvailableSlotsService::getBookedSlots()
 ├── index()          → AppointmentResource::collection (role-scoped query)
 ├── show()           → AppointmentResource (with canAccess guard)
 ├── store()          → RequestAppointmentAction → NotificationManager
 ├── setTime()        → SetAppointmentTimeAction → AutoConfirmAppointment Job → NotificationManager → AppointmentRtdbService::sync()
 ├── respond()        → PatientRespondAction → NotificationManager → AppointmentRtdbService::sync()/remove()
 ├── start()          → StartAppointmentAction → NotificationManager → AppointmentRtdbService::sync()
 ├── cancel()         → CancelAppointmentAction → NotificationManager → AppointmentRtdbService::remove()
 ├── complete()       → CompleteAppointmentAction → NotificationManager → AppointmentRtdbService::remove()
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
- **Slot Service:** `AvailableSlotsService::getBookedSlots()` returns future booked appointments (Set, Accepted, InProgress, Confirmed) with optional date/range filter. Always excludes past appointments.
- **Actions:** 7 actions (RequestAppointment, SetAppointmentTime, PatientRespond, StartAppointment, CancelAppointment, CompleteAppointment, SuggestAlternative) — each is a single-class use case

## Firebase Realtime Database Sync

Appointments are synchronized to Firebase RTDB so the frontend can display real-time updates per doctor.

### Structure

```
/doctors/{doctorId}/booked-appointments/{appointmentId}: {
  "id": "uuid",
  "doctor_id": "uuid",
  "patient_id": "uuid",
  "patient_name": "Patient Name",
  "patient_phone": "+123456789",
  "appointment_date": "2026-06-01",
  "start_time": "10:00",
  "end_time": "11:00",
  "status": "accepted",
  "reason": "Checkup",
  "notes": "...",
  "synced_at": "2026-05-23T12:00:00+00:00",
  "synced_at_timestamp": 123456789
}
```

### Sync Rules

| Transition | Status After | RTDB Action |
|---|---|---|
| `setTime()` → status becomes `Set` | Booked | **Write** to RTDB |
| `respond(accept)` / `AutoConfirm` → `Accepted` | Booked | **Write** (update status) |
| `respond(reject)` → `Rejected` | Not Booked | **Remove** from RTDB |
| `start()` → `InProgress` | Booked | **Write** (update status) |
| `cancel()` → `Cancelled` | Not Booked | **Remove** from RTDB |
| `complete()` → `Completed` | Not Booked | **Remove** from RTDB |

### Services

- **`FirebaseRtdbService`** (`app/Domains/Notifications/Services/FirebaseRtdbService.php`): Low-level wrapper around the Kreait Firebase RTDB client. Provides `setValue()`, `removeValue()`, `getValue()` for any path. Registered as a singleton.
- **`AppointmentRtdbService`** (`app/Domains/Appointments/Services/AppointmentRtdbService.php`): Domain-specific service that builds appointment payloads, determines the correct RTDB path (`doctors/{doctorId}/booked-appointments/{appointmentId}`), and delegates writes/removes to `FirebaseRtdbService`.

### Cleanup

- **`appointments:cleanup-rtdb`** — Artisan command (`app/Console/Commands/CleanupExpiredRtdbAppointments.php`) that scans for booked appointments (Set, Accepted, InProgress, Confirmed) whose `appointment_date + end_time` has passed and removes them from RTDB.
- Runs every 5 minutes via the scheduler (`routes/console.php`).

### Firebase RTDB Security Rules

اذهب إلى **Firebase Console → Realtime Database → Rules** والصق هذه القواعد. تسمح بالقراءة فقط للمستخدمين المصادقين (Admin SDK يكتب دون تأثير القواعد):

```json
{
  "rules": {
    ".read": false,
    ".write": false,
    "doctors": {
      "$doctorId": {
        "booked-appointments": {
          ".read": "auth !== null",
          ".write": false
        }
      }
    }
  }
}
```

- **`.read`: `"auth !== null"`** — أي مستخدم سجل دخوله في Firebase يمكنه قراءة المواعيد
- **`.write`: `false`** — الـ frontend لا يمكنه الكتابة أبداً، فقط الـ Admin SDK (السيرفر) يكتب/يمسح
