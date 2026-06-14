# Get Patient

> Retrieve a single patient by ID, including their associated user data.

## Route Information

- **Method:** `GET`
- **Path:** `/v1/patients/{patient}`
- **Middleware:** `auth:api`, `staff`

## Request

| Parameter | Type | Source | Description |
|-----------|------|--------|-------------|
| `patient` | string (UUID) | Route | Patient UUID v7 |

### Example

```
GET /v1/patients/0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d
```

## Controller Logic (`show`)

```php
$patient->load('user');
$user = $patient->user;
$user->setRelation('patient', $patient);

return ApiResponse::success(
    new PatientResource($user),
    __('Patient retrieved successfully')
);
```

The route uses implicit model binding — `getRouteKeyName()` returns `'user_id'`, so Laravel resolves `Patient` by `user_id` (the User UUID). The controller loads the `user` relation, then attaches the patient relationship back to the user so `PatientResource` (which extends `UserResource`) can access it.

## Response

```json
{
  "success": true,
  "message": "Patient retrieved successfully",
  "data": {
    "id": "0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d",
    "first_name": "John",
    "last_name": "Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "phone": "+123456789",
    "address": "123 Main St",
    "gender": "male",
    "birthday_date": "1990-01-15",
    "roles": ["Patient"],
    "is_active": true,
    "image": {
      "id": "0194f1e2-4a8b-7f90-9d7e-8f6a5b4c3d2e",
      "url": "/storage/images/user_abc123.jpg",
      "type": "App\\Models\\User",
      "imageable_id": "0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d",
      "created_at": "2026-05-19T10:00:00.000000Z"
    }
  }
}
```

## Sequence Diagram

```
Client          StaffMiddleware      PatientController          Patient Model
  │                    │                     │                     │
  │── GET /patients/{id} ──>│                 │                     │
  │                    │── pass (staff) ────>│                     │
  │                    │                     │── implicit binding ─>│
  │                    │                     │── load('user') ─────>│
  │                    │                     │<── patient + user ──│
  │                    │                     │── PatientResource    │
  │                    │<── JSON response ───│                     │
  │<── 200 OK ─────────│                     │                     │
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated (missing/invalid token) |
| 403 | Forbidden (user is not staff) |
| 404 | Patient not found |
