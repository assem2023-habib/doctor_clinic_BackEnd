# Dashboard — Role-Based Statistics

## Overview

Single endpoint `GET /api/v1/dashboard` that returns statistics tailored to the authenticated user's role.

**Middleware:** `auth:api`, `active`

**Response format:**
```json
{
    "status": 200,
    "message": "Dashboard retrieved successfully",
    "data": { ... }
}
```

---

## Admin Dashboard

**Roles:** `admin`, `super-admin`

```json
{
    "users": {
        "total": 150,
        "doctors": 25,
        "patients": 100,
        "receptionists": 10,
        "admins": 5,
        "active": 140,
        "inactive": 10,
        "new_today": 3,
        "new_this_week": 15,
        "new_this_month": 50
    },
    "appointments": {
        "total": 500,
        "today": 12,
        "this_week": 60,
        "this_month": 200,
        "by_status": {
            "pending": 30,
            "confirmed": 100,
            "completed": 350,
            "cancelled": 20
        }
    },
    "medical_records": { "total": 300 },
    "prescriptions": { "total": 450 },
    "specializations": {
        "total": 15,
        "top": [
            { "name": "Cardiology", "doctors_count": 5 },
            { "name": "Dermatology", "doctors_count": 3 }
        ]
    },
    "ratings": {
        "average": 4.2,
        "total": 80
    }
}
```

| Field | Type | Description |
|---|---|---|
| `users.total` | integer | Total users across all roles |
| `users.doctors` | integer | Users with Doctor role |
| `users.patients` | integer | Users with Patient role |
| `users.receptionists` | integer | Users with Receptionist role |
| `users.admins` | integer | Users with Admin role |
| `users.active` | integer | Active users |
| `users.inactive` | integer | Inactive users |
| `users.new_today` | integer | Users registered today |
| `users.new_this_week` | integer | Users registered this week |
| `users.new_this_month` | integer | Users registered this month |
| `appointments.*` | | Appointment counts by date range and status |
| `medical_records.total` | integer | Total medical records |
| `prescriptions.total` | integer | Total prescriptions |
| `specializations.total` | integer | Total specializations |
| `specializations.top` | array | Top 5 specializations by doctor count |
| `ratings.average` | float | Average rating across all users |
| `ratings.total` | integer | Total ratings count |

---

## Doctor Dashboard

**Role:** `doctor`

```json
{
    "patients": {
        "total": 50,
        "new_this_month": 5
    },
    "appointments": {
        "total": 200,
        "today": 8,
        "upcoming": 15,
        "by_status": {
            "pending": 5,
            "confirmed": 15,
            "completed": 175,
            "cancelled": 5
        }
    },
    "medical_records": { "total": 180 },
    "prescriptions": { "total": 250 },
    "ratings": {
        "average": 4.5,
        "total": 30
    }
}
```

| Field | Type | Description |
|---|---|---|
| `patients.total` | integer | Patients under active supervision |
| `patients.new_this_month` | integer | New patients assigned this month |
| `appointments.*` | | Doctor's own appointments |
| `appointments.upcoming` | integer | Future appointments not completed/cancelled |
| `medical_records.total` | integer | Medical records authored by this doctor |
| `prescriptions.total` | integer | Prescriptions on doctor's medical records |
| `ratings.average` | float | Average rating received by this doctor |
| `ratings.total` | integer | Total ratings received |

---

## Patient Dashboard

**Role:** `patient`

```json
{
    "doctors": { "total": 3 },
    "appointments": {
        "total": 15,
        "upcoming": 2,
        "by_status": {
            "pending": 1,
            "confirmed": 2,
            "completed": 10,
            "cancelled": 2
        }
    },
    "medical_records": { "total": 5 },
    "prescriptions": { "total": 8 }
}
```

| Field | Type | Description |
|---|---|---|
| `doctors.total` | integer | Doctors currently supervising this patient |
| `appointments.*` | | Patient's own appointments |
| `medical_records.total` | integer | Medical records for this patient |
| `prescriptions.total` | integer | Prescriptions on patient's medical records |

---

## Receptionist Dashboard

**Role:** `receptionist`

```json
{
    "appointments": {
        "today_total": 20,
        "by_status": {
            "pending": 5,
            "confirmed": 8,
            "completed": 5,
            "cancelled": 2
        }
    },
    "patients": {
        "registered_today": 3,
        "total": 100
    },
    "doctors": { "total": 25 }
}
```

| Field | Type | Description |
|---|---|---|
| `appointments.today_total` | integer | Appointments scheduled for today |
| `appointments.by_status` | object | Today's appointments grouped by status |
| `patients.registered_today` | integer | Patients registered today |
| `patients.total` | integer | Total patients |
| `doctors.total` | integer | Total doctors |

---

## Architecture

```
app/Domains/Dashboard/
├── Controllers/
│   └── DashboardController.php      # __invoke: delegates to service based on role
├── Services/
│   └── DashboardService.php         # forAdmin, forDoctor, forPatient, forReceptionist
```

### Flow

```
GET /api/v1/dashboard
  → auth:api, active middleware
  → DashboardController::__invoke()
    → reads $user->roles
    → DashboardService::forAdmin|forDoctor|forPatient|forReceptionist($user)
    → ApiResponse::success($data)
```

### Route

```php
Route::get('/v1/dashboard', DashboardController::class)
    ->middleware(['auth:api', 'active']);
```
