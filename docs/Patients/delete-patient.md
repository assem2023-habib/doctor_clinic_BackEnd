# Delete Patient

> Delete a patient record with cascade. Blocks deletion if the patient has active (confirmed or completed) appointments.

## Route Information

- **Method:** `DELETE`
- **Path:** `/v1/patients/{patient}`
- **Middleware:** `auth:api`, `staff`, `admin`

## Request

| Parameter | Type | Source | Description |
|-----------|------|--------|-------------|
| `patient` | string (UUID) | Route | Patient UUID v7 |

### Example

```
DELETE /v1/patients/0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d
```

## Controller Logic (`destroy`)

```php
$this->deletePatientAction->execute($patient, request()->user());
```

## Action: `DeletePatientAction`

Wraps the `PatientDeletionService`:

```php
public function execute(Patient $patient, User $admin): void
{
    $this->patientDeletionService->deletePatient($patient, $admin);
}
```

## Service: `PatientDeletionService`

```php
public function deletePatient(Patient $patient, User $actingUser): void
{
    $user = $patient->user;

    // Block deletion if patient has confirmed or completed appointments
    $activeStatuses = [
        AppointmentStatusEnum::Confirmed,
        AppointmentStatusEnum::Completed,
    ];

    $activeCount = Appointment::where('patient_id', $patient->id)
        ->whereIn('status', $activeStatuses)
        ->count();

    if ($activeCount > 0) {
        abort(409, __('Patient has active appointments. Cannot delete.'));
    }

    // Cascade delete within transaction
    DB::transaction(function () use ($user) {
        // Delete profile image from storage + database
        if ($user->image) {
            Storage::disk('local')->delete($user->image->getRawOriginal('url'));
            $user->image->delete();
        }

        // Cascade deletes patient record (via FK ON DELETE CASCADE) + user record
        $user->delete();
    });
}
```

> **Note:** The `Patient` model uses `HasUuidV7` trait. The `user_id` foreign key cascades on delete. When `$user->delete()` is called, the associated `Patient` record is automatically removed as well.

## Response

```json
{
  "success": true,
  "message": "Patient deleted successfully",
  "data": null
}
```

## Sequence Diagram

```
Client          AdminMiddleware      PatientController      DeletePatientAction      PatientDeletionService
  │                    │                     │                     │                     │
  │── DELETE /patients/{id} ──>│             │                     │                     │
  │                    │── pass (admin) ────>│                     │                     │
  │                    │                     │── execute(patient, admin) ──>│            │
  │                    │                     │                     │── deletePatient() ─>│
  │                    │                     │                     │                     │── check active appointments
  │                    │                     │                     │                     │── if count > 0 → 409
  │                    │                     │                     │                     │── DB::transaction()
  │                    │                     │                     │                     │── delete image file
  │                    │                     │                     │                     │── $user->delete()
  │                    │                     │                     │<── done ────────────│
  │                    │                     │<── complete ────────│                     │
  │                    │<── 204 No Content ──│                     │                     │
  │<── 200 OK ─────────│                     │                     │                     │
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated (missing/invalid token) |
| 403 | Forbidden (user is not staff + admin) |
| 404 | Patient not found |
| 409 | Patient has active (confirmed/completed) appointments — cannot delete |
