# Database Schema — Doctor Clinic

## Conventions

- All primary keys use **UUID v7** (generated via `HasUuidV7` trait)
- All foreign keys are `foreignUuid` with `cascadeOnDelete`
- Timestamps: `created_at` / `updated_at` unless noted

---

## Enums

### `GenderEnum`
```
male, female
```

### `RoleEnum`
```
admin, doctor, patient, receptionist
```

### `DayOfWeekEnum`
```
sunday, monday, tuesday, wednesday, thursday, friday, saturday
```

### `AppointmentStatusEnum`
```
pending, confirmed, cancelled, completed
```

### `ModelTypeEnum`
```
user, doctor, patient, receptionist, doctor_schedule,
appointment, appointment_status_log, medical_record,
prescription, prescription_item, medicine, notification, rating
```

### `RatingTypeEnum`
```
user, service, center, appointment_system
```

---

## Tables

### 1. `users`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| first_name | string | |
| last_name | string | |
| username | string | unique |
| email | string | unique |
| phone | string | nullable |
| address | text | nullable |
| gender | enum (GenderEnum) | |
| birthday_date | date | nullable |
| role | enum (RoleEnum) | |
| is_active | boolean | default true |
| email_verified_at | timestamp | nullable |
| password | string | hashed |
| remember_token | string | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 2. `password_reset_tokens`
| Column | Type | Constraints |
|--------|------|-------------|
| email | string | PK |
| token | string | |
| created_at | timestamp | nullable |

### 3. `sessions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | string | PK |
| user_id | string(36) | nullable, indexed |
| ip_address | string(45) | nullable |
| user_agent | text | nullable |
| payload | longText | |
| last_activity | integer | indexed |

### 4. `doctors`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| user_id | uuid | FK → users |
| created_at | timestamp | |
| updated_at | timestamp | |

### 5. `receptionists`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| user_id | uuid | FK → users |
| shift_start | time | nullable |
| shift_end | time | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 6. `doctor_schedules`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| doctor_id | uuid | FK → doctors |
| day_of_week | enum (DayOfWeekEnum) | |
| start_time | time | |
| end_time | time | |
| is_active | boolean | default true |
| created_at | timestamp | |
| updated_at | timestamp | |

### 7. `patients`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| user_id | uuid | FK → users |
| created_at | timestamp | |
| updated_at | timestamp | |

### 8. `appointments`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| doctor_id | uuid | FK → doctors |
| patient_id | uuid | FK → patients |
| appointment_date | date | |
| start_time | time | |
| end_time | time | |
| status | enum (AppointmentStatusEnum) | default 'pending' |
| reason | text | nullable |
| notes | text | nullable |
| created_by | uuid | FK → users |
| created_at | timestamp | |
| updated_at | timestamp | |

### 9. `appointment_status_logs`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| appointment_id | uuid | FK → appointments |
| old_status | enum (AppointmentStatusEnum) | |
| new_status | enum (AppointmentStatusEnum) | |
| changed_by | uuid | FK → users |
| created_at | timestamp | nullable |

> No `updated_at` — immutable log.

### 10. `medical_records`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| patient_id | uuid | FK → patients |
| doctor_id | uuid | FK → doctors |
| appointment_id | uuid | FK → appointments |
| diagnosis | text | |
| notes | text | nullable |
| created_at | timestamp | nullable |

> No `updated_at` — immutable record.

### 11. `prescriptions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| medical_record_id | uuid | FK → medical_records |
| doctor_id | uuid | FK → doctors |
| patient_id | uuid | FK → patients |
| notes | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 12. `prescription_items`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| prescription_id | uuid | FK → prescriptions |
| medicine_id | uuid | FK → medicines |
| dosage | string | |
| frequency | string | |
| duration | string | |
| instructions | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 13. `medicines`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| name | string | |
| description | text | nullable |
| price | decimal(10,2) | default 0 |
| created_at | timestamp | |
| updated_at | timestamp | |

### 14. `notifications`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| topic | string | |
| title | string | |
| body | json | |
| created_at | timestamp | |
| updated_at | timestamp | |

### 15. `notification_user` (pivot)
| Column | Type | Constraints |
|--------|------|-------------|
| notification_id | uuid | FK → notifications, PK |
| user_id | uuid | FK → users, PK |
| read_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 16. `ratings`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| rater_id | uuid | FK → users |
| type | enum (RatingTypeEnum) | user, service, center, appointment_system |
| rateable_id | uuid | nullable (polymorphic) |
| rateable_type | string | nullable (polymorphic) |
| rating | tinyint unsigned | 1–5 |
| comment | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| Index | | (rateable_type, rateable_id) |
| Index | | (rater_id) |

> `rateable_id` + `rateable_type` are nullable — only used when `type = user`.

### 17. `activity_logs`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| user_id | uuid | FK → users |
| action | string | |
| model_type | enum (ModelTypeEnum) | |
| model_id | string(36) | |
| description | text | nullable |
| created_at | timestamp | nullable |
| Index | | (model_type, model_id) |

> Polymorphic reference: `model_type` + `model_id` point to any table.

---

## Relationships (ER)

```
users 1──1 doctors
users 1──1 receptionists
users 1──1 patients

doctors 1──N doctor_schedules
doctors 1──N appointments
doctors 1──N medical_records
doctors 1──N prescriptions

patients 1──N appointments
patients 1──N medical_records
patients 1──N prescriptions

appointments 1──N appointment_status_logs
appointments 1──N medical_records

medical_records 1──N prescriptions

prescriptions 1──N prescription_items

medicines 1──N prescription_items

notifications N──M users  (pivot: notification_user)
    └── pivot: read_at

users 1──N activity_logs
    └── polymorphic: (model_type, model_id)

users 1──N ratings (rater_id)

users 1──N ratings (rateable — morph, type = user only)
    └── polymorphic: (rateable_type, rateable_id)

rating.type enum: user, service, center, appointment_system
    └── when type ≠ user → rateable_id + rateable_type are NULL
```

---

## Laravel Default Tables

- `cache`, `cache_locks` — cache driver
- `jobs`, `job_batches`, `failed_jobs` — queue driver
- `oauth_auth_codes`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_clients`, `oauth_device_codes` — Laravel Passport
