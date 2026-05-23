# Patients Domain

> Patients are users with the `patient` role. The Patient model (UUID v7) links to a User record and manages appointment history, doctor assignments, and profile data.

## Endpoints

| Method | Endpoint | Middleware | Controller | Description |
|--------|----------|-----------|------------|-------------|
| POST | `/v1/patients` | `auth:api`, `active`, `admin` | `store` | Create a new patient (admin only) |
| GET | `/v1/patients` | `auth:api`, `active`, `staff:doctor` | `index` | List/search patients with filters |
| GET | `/v1/patients/{patient}` | `auth:api`, `active`, `staff:doctor` | `show` | Get a single patient |
| PUT | `/v1/patients/{patient}` | `auth:api`, `active`, `admin` | `update` | Fully update a patient |
| PATCH | `/v1/patients/{patient}` | `auth:api`, `active`, `admin` | `updatePartial` | Partially update a patient |
| DELETE | `/v1/patients/{patient}` | `auth:api`, `active`, `admin` | `destroy` | Delete a patient (cascade) |

## Architecture

```
PatientController
 └── index()          → PatientResource::collection(User::whereHas('roles', slug=patient)) + filters
 └── show()           → PatientResource (loads user.roles relation)
 └── store()          → CreatePatientAction → PatientResource (status 201)
 └── update()         → UpdatePatientAction → UpdatePatientData (fromRequest)
 └── updatePartial()  → UpdatePatientAction → UpdatePatientData (fromRequestPartial)
 └── destroy()        → DeletePatientAction → PatientDeletionService
```

- **Model:** `Patient` (UUID v7, `HasUuidV7`, `user_id` FK)
- **Relations:** `user` (BelongsTo User), `appointments` (HasMany), `doctors` (BelongsToMany via `doctor_patient`)
- **Resource:** `PatientResource` extends `UserResource` → returns `id, first_name, last_name, username, email, phone, address, gender, birthday_date, roles, is_active, image`
- **DTO:** `UpdatePatientData` — built from either full PUT request or partial PATCH request; supports optional file upload
- **Action:** `UpdatePatientAction` → updates User model, optionally uploads image via `UploadImageAction`
- **Service:** `PatientDeletionService` — blocks deletion if patient has active (confirmed/completed) appointments; cascade deletes image file + user record in transaction
- **Policies/Repositories/Enums:** Empty (not yet implemented)
