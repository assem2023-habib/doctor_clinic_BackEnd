# Users Domain (إدارة المستخدمين)

> Admin endpoints for managing all users across the system. Requires `admin` role.

---

## Endpoints

| Method | Endpoint | Middleware | Controller | Description |
|--------|----------|-----------|------------|-------------|
| `GET` | `/api/v1/users` | `auth:api`, `active`, `admin` | `index` | List/search users with filters |
| `GET` | `/api/v1/users/{user}` | `auth:api`, `active`, `admin` | `show` | Get a single user (role-aware resource) |
| `PUT` | `/api/v1/users/{user}` | `auth:api`, `active`, `admin` | `update` | Update a user's profile fields |
| `PUT` | `/api/v1/users/{user}/toggle-active` | `auth:api`, `active`, `admin` | `toggleActive` | Toggle user active/inactive |
| `DELETE` | `/api/v1/users/{user}` | `auth:api`, `active`, `admin` | `destroy` | Delete a user (except admin) |

---

## Request Body — `PUT /api/v1/users/{user}`

| Field | Type | Status | Validation |
|-------|------|--------|------------|
| `first_name` | `string` | optional | max:255 |
| `last_name` | `string` | optional | max:255 |
| `username` | `string` | optional | max:255, unique:users (ignore current) |
| `email` | `string` (email) | optional | max:255, unique:users (ignore current) |
| `phone` | `string` | optional | max:20 |
| `address` | `string` | optional | max:1000 |
| `gender` | `string` (enum) | optional | `male`, `female` |
| `birthday_date` | `string` (date) | optional | YYYY-MM-DD |
| `city_id` | `string` (UUID) | optional | exists:cities,id |

### Example:

```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "city_id": "019e1d0f-1ec6-7289-8cb3-eb9bdb0f1009"
}
```

---

## Query Parameters — `GET /api/v1/users`

| Parameter | Type | Description |
|-----------|------|-------------|
| `role` | `string` | Filter by role slug (`patient`, `doctor`, `receptionist`, `admin`) |
| `search` | `string` | Search by `first_name`, `last_name`, `email`, or `username` |
| `gender` | `string` | Filter by gender (`male`, `female`) |
| `is_active` | `boolean` | Filter by active status |
| `date_from` | `string` (date) | Filter by `created_at >=` |
| `date_to` | `string` (date) | Filter by `created_at <=` |
| `limit` | `integer` | Items per page (default: 20, max: 100) |
| `page` | `integer` | Page number (default: 1) |
| `sort` | `string` | Sort field (default: `created_at`) |
| `order` | `string` | Sort order (`asc`, `desc`, default: `desc`) |

---

## Response

### `GET /api/v1/users` — 200 OK

```json
{
    "status": 200,
    "message": "Users retrieved successfully",
    "data": [
        {
            "id": "019e1d0f-...",
            "first_name": "Admin",
            "last_name": "User",
            "username": "admin",
            "email": "admin@gmail.com",
            "phone": null,
            "address": null,
            "gender": "male",
            "birthday_date": null,
            "roles": [{"slug": "admin"}],
            "is_active": true,
            "city_id": null,
            "city": null,
            "country": null,
            "image": null
        }
    ],
    "pagination": {
        "total": 1,
        "per_page": 20,
        "current_page": 1,
        "last_page": 1
    }
}
```

### `GET /api/v1/users/{user}` — 200 OK (role-aware resource)

Returns the appropriate resource based on the user's role:
- **Patient** → `PatientResource` (includes `patient` object)
- **Doctor** → `DoctorResource` (includes `specialization`, `experience_months`, `schedules`)
- **Receptionist** → `ReceptionistResource` (includes `shift_start`, `shift_end`)
- **Admin/Other** → `UserResource` (base fields only)

### `PUT /api/v1/users/{user}/toggle-active` — 200 OK

```json
{
    "status": 200,
    "message": "User activated successfully",
    "data": {
        "is_active": true
    }
}
```

### `DELETE /api/v1/users/{user}` — 204 No Content

---

## Architecture

```
UserController (App\Domains\Users\Controllers)
 ├── index()          → UserResource::collection(User::with('roles','city','country')->paginate())
 ├── show()           → Role-aware resource (PatientResource/DoctorResource/ReceptionistResource/UserResource)
 ├── update()         → $user->update($request->only([...])) → UserResource
 ├── toggleActive()   → $user->update(['is_active' => !$user->is_active])
 └── destroy()        → Blocks admin deletion, soft-deletes others
```

- **Model:** `User` (UUID v7)
- **Resource:** `UserResource` — base resource with `city_id`, `city`, `country` fields
- **Request:** `UpdateUserRequest` — validation with unique ignores for email/username

---

## Business Rules

| Rule | Behavior |
|------|----------|
| Admin protection | Admin users **cannot** be deleted |
| Toggle active | Flips `is_active` boolean; deactivated users cannot authenticate |
| Role-aware show | Returns role-specific resource with all relevant relations |
| Cascade delete | Deleting a user cascades to `patient`/`doctor`/`receptionist` profile |
