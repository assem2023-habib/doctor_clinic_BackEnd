# Supervisions Domain

> Manages the many-to-many relationship between doctors and patients via the `doctor_patient` pivot table. Allows staff to assign/remove patients to/from doctors, and lets doctors/patients view their respective supervision lists.

## Endpoints

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| GET | `/v1/doctors/{doctor}/patients` | `auth:api` | List patients supervised by a doctor |
| GET | `/v1/patients/{patient}/doctors` | `auth:api` | List doctors supervising a patient |
| POST | `/v1/doctors/{doctor}/patients` | `auth:api`, `staff` | Assign a patient to a doctor |
| DELETE | `/v1/doctors/{doctor}/patients/{patient}` | `auth:api`, `staff` | Remove a patient from a doctor |

## Architecture

```
SupervisionController
 ├── doctorPatients() → SupervisionPatientResource::collection
 ├── patientDoctors() → SupervisionDoctorResource::collection
 ├── assign()         → AssignPatientToDoctorAction
 └── remove()         → RemovePatientFromDoctorAction
```

- **Pivot Model:** `DoctorPatient` (UUID v7, extends `Pivot`, table `doctor_patient`)
- **Pivot Fields:** `doctor_id`, `patient_id`, `assigned_by`, `notes`, `created_at`, `updated_at`
- **Relations:** `Doctor::patients()` (BelongsToMany via `doctor_patient`), `Patient::doctors()` (BelongsToMany via `doctor_patient`)
- **Resources:** `SupervisionPatientResource` (includes `supervision` key with pivot data), `SupervisionDoctorResource` (same structure + doctor fields)
- **Actions:** `AssignPatientToDoctorAction` (syncWithoutDetaching), `RemovePatientFromDoctorAction` (detach)
