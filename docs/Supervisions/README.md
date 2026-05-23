# Supervisions Domain

> Doctor-Patient supervision management. A doctor can supervise multiple patients, and a patient can be supervised by multiple doctors (many-to-many). A patient **cannot** have more than one doctor with the same specialization. Patients can request supervision from doctors via **supervision requests**, which doctors approve or reject.

## Endpoints

| Method | Endpoint | Middleware | Description |
|--------|----------|-----------|-------------|
| `GET` | `/v1/doctors/{doctor}/patients` | `auth:api`, `active` | List patients assigned to a doctor (doctor themselves or staff) |
| `GET` | `/v1/patients/{patient}/doctors` | `auth:api`, `active` | List doctors assigned to a patient (patient themselves or staff) |
| `GET` | `/v1/patients/{patient}/available-doctors` | `auth:api`, `active` | List doctors not assigned to a patient (patient or staff), filter by `specialization` |
| `POST` | `/v1/doctors/{doctor}/patients` | `auth:api`, `active`, `staff` | Assign a patient to a doctor (staff) |
| `POST` | `/v1/doctors/{doctor}/patients/self` | `auth:api`, `active` | Doctor self-assign a patient |
| `POST` | `/v1/doctors/{doctor}/patients/bulk` | `auth:api`, `active`, `staff` | Bulk assign patients to a doctor |
| `DELETE` | `/v1/doctors/{doctor}/patients/{patient}` | `auth:api`, `active`, `staff` | Remove a patient from a doctor |
| `POST` | `/v1/patients/{patient}/supervision-requests` | `auth:api`, `active` | Patient requests supervision from a doctor |
| `GET` | `/v1/patients/{patient}/supervision-requests` | `auth:api`, `active` | List patient's supervision requests |
| `GET` | `/v1/doctors/{doctor}/supervision-requests` | `auth:api`, `active` | List doctor's supervision requests (filter by `status`) |
| `POST` | `/v1/supervision-requests/{id}/approve` | `auth:api`, `active` | Doctor approves a request |
| `POST` | `/v1/supervision-requests/{id}/reject` | `auth:api`, `active` | Doctor rejects a request |
| `POST` | `/v1/supervision-requests/{id}/cancel` | `auth:api`, `active` | Patient cancels their own request |

## Pivot Table: `doctor_patient`

| Column | Type | Description |
|--------|------|-------------|
| `doctor_id` | uuid | FK to doctors |
| `patient_id` | uuid | FK to patients |
| `assigned_by` | string(500) | `"{user_id}: {first_name} {last_name}"` |
| `notes` | text/nullable | Assignment notes |
| `supervision_status` | string(20) | `active` (default) or `suspended` |
| `supervision_start` | timestamp/nullable | Supervision start date |
| `supervision_end` | timestamp/nullable | Supervision end date |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

## Constraints

- **Unique** `(doctor_id, patient_id)` — prevents duplicate assignments
- **Specialization uniqueness** — a patient cannot have more than one doctor with the same specialization (enforced in `AssignPatientToDoctorAction`)
- **Supervision request specialization** — a patient can only request supervision from a doctor if they don't already have an active supervision with a doctor of the **same specialization** (enforced in `CreateSupervisionRequestAction`)
- **Pending request uniqueness** — a patient cannot have more than one pending request to the same doctor
- **Multiple requests allowed** — a patient can request from multiple doctors with different specializations simultaneously
- **Max 5 pending requests** — a patient cannot have more than 5 pending supervision requests at a time (enforced in `CreateSupervisionRequestAction`)

## Supervision Requests Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | uuid PK | |
| `patient_id` | uuid FK→patients | |
| `doctor_id` | uuid FK→doctors | |
| `status` | string(20) | `pending`, `approved`, `rejected`, `cancelled` |
| `notes` | text/nullable | Patient's note |
| `responded_at` | timestamp/nullable | When doctor responded or patient cancelled |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

## Architecture

```
SupervisionController
 └── doctorPatients()      → SupervisionPatientResource collection
 └── patientDoctors()       → SupervisionDoctorResource collection
 └── availableDoctors()     → DoctorResource collection (excludes assigned)
 └── assign()               → AssignPatientToDoctorAction (with specialization check)
 └── selfAssign()           → AssignPatientToDoctorAction (doctor self-assign)
 └── bulkAssign()           → BulkAssignPatientsToDoctorAction (skips conflicts)
 └── remove()               → RemovePatientFromDoctorAction

SupervisionRequestController
 └── store()                → CreateSupervisionRequestAction (checks same-specialization)
 └── indexPatient()         → SupervisionRequestResource collection
 └── indexDoctor()          → SupervisionRequestResource collection
 └── approve()              → ApproveSupervisionRequestAction (creates supervision + cancels same-specialization pending)
 └── reject()               → RejectSupervisionRequestAction
 └── cancel()               → CancelSupervisionRequestAction (patient cancels own request)
```
