# Database Schema — Doctor Clinic

## Conventions

- All primary keys use **UUID v7** (generated via `HasUuidV7` trait)
- All foreign keys are `foreignUuid` with `cascadeOnDelete` unless noted
- `created_by` / `changed_by` columns store `"{uuid}: {first_name} {last_name}"` as a plain string (not FK) — allows doctor/user deletion without losing history
- Timestamps: `created_at` / `updated_at` unless noted

---

## Enums

### `GenderEnum`
```
male, female
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
prescription, prescription_item, medicine, notification, rating,
doctor_patient, supervision_request
```

### `RatingTypeEnum`
```
user, service, center, appointment_system
```

### `ImageTypeEnum`
```
user, country
```

> **Max size per type** (defined in `config/images.php`):
> - `user` → 2048 KB (2 MB)
> - `country` → 5120 KB (5 MB)

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
| fcm_tokens | json | nullable |
| is_active | boolean | default true |
| country_id | uuid | FK → countries, nullable |
| city_id | uuid | FK → cities, nullable |
| email_verified_at | timestamp | nullable |
| password | string | hashed |
| remember_token | string | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 2. `roles`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| name | string | unique |
| slug | string | unique |
| description | text | nullable |
| guard_name | string | default 'api' |
| is_system | boolean | default false |
| created_at | timestamp | |
| updated_at | timestamp | |

### 3. `permissions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| name | string | unique |
| slug | string | unique |
| description | text | nullable |
| guard_name | string | default 'api' |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4. `role_permission` (pivot)
| Column | Type | Constraints |
|--------|------|-------------|
| role_id | uuid | FK → roles, PK |
| permission_id | uuid | FK → permissions, PK |

### 5. `role_user` (pivot)
| Column | Type | Constraints |
|--------|------|-------------|
| role_id | uuid | FK → roles, PK |
| user_id | uuid | FK → users, PK |

### 6. `password_reset_tokens`
| Column | Type | Constraints |
|--------|------|-------------|
| email | string | PK |
| token | string | |
| created_at | timestamp | nullable |

### 3. `countries`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| name | json | `{"ar": "…", "en": "…"}` |
| code | string(2) | unique |
| flag | string | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4. `cities`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| name | json | `{"ar": "…", "en": "…"}` |
| country_id | uuid | FK → countries |
| created_at | timestamp | |
| updated_at | timestamp | |

### 5. `sessions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | string | PK |
| user_id | string(36) | nullable, indexed |
| ip_address | string(45) | nullable |
| user_agent | text | nullable |
| payload | longText | |
| last_activity | integer | indexed |

### 6. `doctors`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| user_id | uuid | FK → users |
| specialization_id | uuid (FK → specializations) | required |
| experience_months | integer | unsigned, nullable, months |
| created_at | timestamp | |
| updated_at | timestamp | |

### 7. `receptionists`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| user_id | uuid | FK → users |
| shift_start | time | nullable |
| shift_end | time | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 8. `doctor_schedules`
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

### 9. `patients`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| user_id | uuid | FK → users |
| created_at | timestamp | |
| updated_at | timestamp | |

### 10. `doctor_patient` (pivot)
| Column | Type | Constraints |
|--------|------|-------------|
| doctor_id | uuid | FK → doctors |
| patient_id | uuid | FK → patients |
| assigned_by | string(500) | `"{uuid}: {name}"`, no FK |
| notes | text | nullable |
| supervision_status | string(20) | default `active` |
| supervision_start | timestamp | nullable |
| supervision_end | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| Unique | | `(doctor_id, patient_id)` |

> Default `supervision_status` is `active`. When a supervision request is approved, the assign action creates a row here with `supervision_status=active`.

### 11. `supervision_requests`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| patient_id | uuid | FK → patients |
| doctor_id | uuid | FK → doctors |
| status | `SupervisionRequestStatusEnum` | `pending`, `approved`, `rejected`, `cancelled` |
| responded_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| Index | | `(patient_id, status)` |
| Index | | `(doctor_id, status)` |

### 13. `appointments`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| doctor_id | uuid | nullable, no FK |
| patient_id | uuid | FK → patients |
| appointment_date | date | |
| start_time | time | |
| end_time | time | |
| status | enum (AppointmentStatusEnum) | default 'pending' |
| reason | text | nullable |
| notes | text | nullable |
| created_by | string(500) | `"{uuid}: {name}"`, no FK |
| created_at | timestamp | |
| updated_at | timestamp | |

### 14. `appointment_status_logs`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| appointment_id | uuid | FK → appointments |
| old_status | enum (AppointmentStatusEnum) | |
| new_status | enum (AppointmentStatusEnum) | |
| changed_by | string(500) | `"{uuid}: {name}"`, no FK |
| created_at | timestamp | nullable |

> No `updated_at` — immutable log.

### 15. `medical_records`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| patient_id | uuid | FK → patients |
| doctor_id | uuid | nullable, no FK |
| appointment_id | uuid | FK → appointments |
| diagnosis | text | |
| notes | text | nullable |
| created_at | timestamp | nullable |

> No `updated_at` — immutable record.

### 16. `prescriptions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| medical_record_id | uuid | FK → medical_records |
| doctor_id | uuid | nullable, no FK |
| patient_id | uuid | FK → patients |
| notes | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 17. `prescription_items`
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

### 18. `medicines`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| name | string | |
| description | text | nullable |
| price | decimal(10,2) | default 0 |
| created_at | timestamp | |
| updated_at | timestamp | |

### 19. `notifications`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| topic | string | |
| title | string | |
| body | json | |
| created_at | timestamp | |
| updated_at | timestamp | |

### 20. `notification_user` (pivot)
| Column | Type | Constraints |
|--------|------|-------------|
| notification_id | uuid | FK → notifications, PK |
| user_id | uuid | FK → users, PK |
| read_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 21. `ratings`
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

### 22. `activity_logs`
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

### 23. `images`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| url | string | |
| imageable_type | string | `user` \| `country` |
| imageable_id | uuid | |
| created_at | timestamp | |
| updated_at | timestamp | |
| Index | | unique (imageable_type, imageable_id) |

> Polymorphic table: `imageable_type` stores the enum value (`user` or `country`). 1:1 relation — each object has one image.
> File size limits are configured centrally in `config/images.php` per `ImageTypeEnum`.

---

## Relationships (ER)

```
countries 1──N cities

users N──1 countries
users N──1 cities

users 1──1 doctors
users 1──1 receptionists
users 1──1 patients

doctors 1──N doctor_schedules
doctors N──M patients  (pivot: doctor_patient)
    └── pivot: assigned_by, notes, supervision_status, supervision_start, supervision_end
doctors 1──N supervision_requests
    └── polymorphic: (doctor_id)
doctors 1──N appointments        (doctor_id is nullable, no FK — nulled on doctor delete)
doctors 1──N medical_records     (doctor_id is nullable, no FK — nulled on doctor delete)
doctors 1──N prescriptions       (doctor_id is nullable, no FK — nulled on doctor delete)

patients N──M doctors  (pivot: doctor_patient)
    └── pivot: assigned_by, notes, supervision_status, supervision_start, supervision_end
patients 1──N supervision_requests
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

users N──M roles  (pivot: role_user)
roles N──M permissions  (pivot: role_permission)

users 1──1 images (morph — imageable)
countries 1──1 images (morph — imageable)
    └── polymorphic: (imageable_type, imageable_id) UNIQUE
    └── type values: user, country (ImageTypeEnum)

```

> **Notes**
> - `appointments.created_by` and `appointment_status_logs.changed_by` are plain strings (`"{uuid}: {name}"`), not foreign keys — they preserve audit history when users/doctors are deleted.
> - `appointments.doctor_id`, `medical_records.doctor_id`, and `prescriptions.doctor_id` are nullable with no FK constraint — set to `null` when a doctor is deleted for non-pending records.

---

> Total custom tables: 25

## Laravel Default Tables

- `cache`, `cache_locks` — cache driver
- `jobs`, `job_batches`, `failed_jobs` — queue driver
- `oauth_auth_codes`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_clients`, `oauth_device_codes` — Laravel Passport
