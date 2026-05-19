# Update Patient (PUT / PATCH)

> Update a patient's profile. Supports both full (PUT) and partial (PATCH) updates including an optional profile image upload.

## Route Information

| Method | Path | Middleware |
|--------|------|------------|
| PUT | `/v1/patients/{patient}` | `auth:api`, `staff`, `admin` |
| PATCH | `/v1/patients/{patient}` | `auth:api`, `staff`, `admin` |

## Request

| Parameter | Type | PUT | PATCH | Constraints | Description |
|-----------|------|-----|-------|-------------|-------------|
| `first_name` | string | required | sometimes | max 255 | First name |
| `last_name` | string | required | sometimes | max 255 | Last name |
| `username` | string | required | sometimes | max 255, unique (users) | Username |
| `email` | string | required | sometimes | max 255, email, unique (users) | Email address |
| `phone` | string | — | sometimes | max 20 | Phone number |
| `address` | string | — | sometimes | max 1000 | Address |
| `gender` | string | required | sometimes | `male`, `female` | Gender |
| `birthday_date` | date | — | sometimes | format Y-m-d | Date of birth |
| `file` | file | — | sometimes | image, max 2MB, jpg/jpeg/png/webp | Profile image |

### Examples

```
PUT /v1/patients/0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d
Content-Type: multipart/form-data

first_name=John
last_name=Smith
username=johnsmith
email=john.smith@example.com
phone=+987654321
address=456 Oak Ave
gender=male
birthday_date=1990-06-20
file=@avatar.jpg
```

```
PATCH /v1/patients/0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d
Content-Type: application/json

{
  "phone": "+111222333"
}
```

## DTO: `UpdatePatientData`

### Full Update (`fromRequest`)

```php
$dto->fields = [
    'first_name'   => $request->first_name,
    'last_name'    => $request->last_name,
    'username'     => $request->username,
    'email'        => $request->email,
    'phone'        => $request->phone,
    'address'      => $request->address,
    'gender'       => GenderEnum::from($request->gender)->value,
    'birthday_date'=> $request->birthday_date,
];
$dto->file = $request->file('file');
```

### Partial Update (`fromRequestPartial`)

Iterates over a whitelist of fields and includes only those present in the request:

```php
foreach (['first_name', 'last_name', 'username', 'email', 'phone', 'address', 'birthday_date'] as $field) {
    if ($request->exists($field)) {
        $dto->fields[$field] = $request->$field;
    }
}
```

Gender and file are handled separately with the same `exists` check.

## Action: `UpdatePatientAction`

```php
public function execute(Patient $patient, UpdatePatientData $data): User
{
    $user = $patient->user;
    $user->update($data->toArray());

    if ($data->hasFile()) {
        $this->uploadImageAction->execute(UploadImageData::fromArray([
            'file'         => $data->file,
            'type'         => ImageTypeEnum::User,
            'imageable_id' => $user->id,
        ]));
    }

    return $user->fresh();
}
```

The action updates `User` model fields, then optionally uploads a new profile image via `UploadImageAction` (which replaces any existing image for the user).

## Response

```json
{
  "success": true,
  "message": "Patient updated successfully",
  "data": {
    "id": "0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d",
    "first_name": "John",
    "last_name": "Smith",
    "username": "johnsmith",
    "email": "john.smith@example.com",
    "phone": "+987654321",
    "address": "456 Oak Ave",
    "gender": "male",
    "birthday_date": "1990-06-20",
    "role": "patient",
    "is_active": true,
    "image": {
      "id": "0194f1e2-5b9c-8f01-0e8f-9a7b6c5d4e3f",
      "url": "/storage/images/user_xyz789.jpg",
      "type": "App\\Models\\User",
      "imageable_id": "0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d",
      "created_at": "2026-05-19T11:00:00.000000Z"
    }
  }
}
```

## Sequence Diagram

```
Client          AdminMiddleware      PatientController      UpdatePatientAction      UploadImageAction
  │                    │                     │                     │                     │
  │── PUT /patients/{id} ──>│                 │                     │                     │
  │                    │── pass (admin) ────>│                     │                     │
  │                    │                     │── UpdatePatientRequest (validation)       │
  │                    │                     │── UpdatePatientData::fromRequest()        │
  │                    │                     │── execute(patient, dto) ──>│               │
  │                    │                     │                     │── $user->update()   │
  │                    │                     │                     │── if hasFile() ───>│
  │                    │                     │                     │                     │── UploadImageData
  │                    │                     │                     │                     │── store image
  │                    │                     │                     │<── success ────────│
  │                    │                     │                     │── $user->fresh()   │
  │                    │                     │<── PatientResource ─│                     │
  │                    │<── JSON response ───│                     │                     │
  │<── 200 OK ─────────│                     │                     │                     │
```

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated (missing/invalid token) |
| 403 | Forbidden (user is not staff + admin for write operations) |
| 404 | Patient not found |
| 422 | Validation failed (e.g. duplicate email, invalid gender, image too large) |
