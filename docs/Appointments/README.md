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
| GET | `/v1/doctors/{doctor}/appointments` | `auth:api` | List doctor appointments (filtered by date, role-scoped) |
| GET | `/v1/appointments` | `auth:api` | List appointments (role-scoped, supports `doctor_id` filter) |
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

- **`Appointment`** (UUID v7): `doctor_id`, `patient_id`, `appointment_date`, `start_time`, `end_time`, `status`, `reason`, `notes`, `created_by`, `medical_record_id`
- **`AppointmentStatusLog`** (UUID v7): `appointment_id`, `old_status`, `new_status`, `changed_by`, `created_at` (immutable audit trail)

## Keys

- **Status Engine:** `AppointmentStatusEnum` (9 states: pending, requested, set, accepted, rejected, in_progress, confirmed, cancelled, completed)
- **Overlap Prevention:** `NoOverlappingAppointment` rule → `AppointmentRepositoryInterface::hasOverlap()` → `EloquentAppointmentRepository`
- **Schedule Validation:** `WithinDoctorSchedule` rule — validates the appointment date's day-of-week exists in the doctor's active `DoctorSchedule`, and optionally checks that `start_time`/`end_time` fall within a schedule time slot
- **Auto-Confirm:** `AutoConfirmAppointment` job dispatched with `(config:appointment.response_window_hours)` delay when staff sets a time
- **Notifications:** Each state transition fires a notification via `NotificationManager` (appointment.requested, .time_set, .accepted, .rejected, .in_progress, .cancelled, .completed, .alternative_suggested)
- **Slot Service:** `AvailableSlotsService::getBookedSlots()` returns future booked appointments (Set, Accepted, InProgress, Confirmed) with optional date/range filter. Always excludes past appointments.
- **Actions:** 7 actions (RequestAppointment, SetAppointmentTime, PatientRespond, StartAppointment, CancelAppointment, CompleteAppointment, SuggestAlternative) — each is a single-class use case

## Firebase Realtime Database Sync

Appointments are synchronized to Firebase RTDB so the frontend can display real-time updates per doctor.

### Structure

```
/doctors/{doctorId}: {
  "doctor_name": "Jane Smith"
}
/doctors/{doctorId}/booked-appointments/{date}/{appointmentId}: {
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

The path includes the **date** (Y-m-d) between `booked-appointments` and the appointment ID, grouping appointments by day. The `doctor_name` is stored once at the doctor level (`/doctors/{doctorId}/doctor_name`), not inside each appointment.

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
- **`AppointmentRtdbService`** (`app/Domains/Appointments/Services/AppointmentRtdbService.php`): Domain-specific service that builds appointment payloads, determines the correct RTDB path (`doctors/{doctorId}/booked-appointments/{date}/{appointmentId}`), and delegates writes/removes to `FirebaseRtdbService`. Also syncs `doctor_name` at `/doctors/{doctorId}/doctor_name`.

### Cleanup

- **`appointments:cleanup-rtdb`** — Artisan command (`app/Console/Commands/CleanupExpiredRtdbAppointments.php`) that scans for booked appointments (Set, Accepted, InProgress, Confirmed) whose `appointment_date + end_time` has passed and removes them from RTDB.
- Runs every 5 minutes via the scheduler (`routes/console.php`).

### Firebase Authentication Flow

الـ Frontend يحتاج Firebase Custom Token ليتمكن من قراءة RTDB. التدفق كالتالي:

```
1. المستخدم يسجل دخول في Laravel (POST /api/v1/auth/login) ← يحصل على Bearer token
2. الـ Frontend يرسل Bearer token إلى POST /api/v1/auth/firebase-token
3. Laravel يُنشئ Firebase Custom Token خاص بهذا المستخدم ويعيده
4. الـ Frontend يستخدم Firebase SDK: signInWithCustomToken(firebase_token)
5. الآن الـ Frontend قادر على قراءة RTDB
```

#### Request

```
POST /api/v1/auth/firebase-token
Authorization: Bearer <laravel_access_token>
```

#### Response

```json
{
  "status": 200,
  "message": "Firebase token generated successfully.",
  "data": {
    "firebase_token": "eyJhbGciOiJSUzI1NiIs...",
    "uid": "user_019e1d0f-1ec6-7289-8cb3-eb9bdb0f1009"
  }
}
```

#### Frontend Example (JavaScript)

```javascript
import { initializeApp } from "firebase/app";
import { getDatabase, ref, onValue } from "firebase/database";
import { getAuth, signInWithCustomToken } from "firebase/auth";

// 1. بعد ما تجيب firebase_token من الـ API
const firebaseToken = response.data.firebase_token;

// 2. سجل دخول في Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
await signInWithCustomToken(auth, firebaseToken);

// 3. اقرأ المواعيد ليوم معين
const db = getDatabase(app);
const date = "2026-06-01";
const appointmentsRef = ref(db, `doctors/${doctorId}/booked-appointments/${date}`);
onValue(appointmentsRef, (snapshot) => {
  const data = snapshot.val();
  // data = { appointmentId1: {...}, appointmentId2: {...}, ... }
});
```

### Firebase RTDB Security Rules

اذهب إلى **Firebase Console → Realtime Database → Rules** والصق هذه القواعد:

```json
{
  "rules": {
    ".read": false,
    ".write": false,
    "doctors": {
      "$doctorId": {
        "doctor_name": {
          ".read": "auth !== null",
          ".write": false
        },
        "booked-appointments": {
          "$date": {
            ".read": "auth !== null",
            ".write": false
          }
        }
      }
    }
  }
}
```

- **`.read`: `"auth !== null"`** — فقط المستخدمون الذين سجلوا دخولهم في Firebase (عبر Custom Token) يمكنهم القراءة
- **`.write`: `false`** — الـ frontend لا يمكنه الكتابة أبداً، فقط الـ Admin SDK (السيرفر) يكتب/يمسح عبر `FirebaseRtdbService`
