# Supervisions Domain

> Doctor-Patient supervision management. A doctor can supervise multiple patients, and a patient can be supervised by multiple doctors (many-to-many). A patient **cannot** have more than one doctor with the same specialization.

## Endpoints

| Method | Endpoint | Middleware | Description |
|--------|----------|-----------|-------------|
| `GET` | `/v1/doctors/{doctor}/patients` | `auth:api`, `active` | List patients assigned to a doctor (doctor themselves or staff) |
| `GET` | `/v1/patients/{patient}/doctors` | `auth:api`, `active` | List doctors assigned to a patient (patient themselves or staff) |
| `GET` | `/v1/patients/{patient}/available-doctors` | `auth:api`, `active` | List doctors not assigned to a patient (patient or staff), filter by `specialization` |
| `POST` | `/v1/doctors/{doctor}/patients` | `auth:api`, `active`, `staff` | Assign a patient to a doctor |
| `POST` | `/v1/doctors/{doctor}/patients/bulk` | `auth:api`, `active`, `staff` | Bulk assign patients to a doctor |
| `DELETE` | `/v1/doctors/{doctor}/patients/{patient}` | `auth:api`, `active`, `staff` | Remove a patient from a doctor |

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

## Architecture

```
SupervisionController
 └── doctorPatients()      → SupervisionPatientResource collection
 └── patientDoctors()       → SupervisionDoctorResource collection
 └── availableDoctors()     → DoctorResource collection (excludes assigned)
 └── assign()               → AssignPatientToDoctorAction (with specialization check)
 └── bulkAssign()           → BulkAssignPatientsToDoctorAction (skips conflicts)
 └── remove()               → RemovePatientFromDoctorAction
```
