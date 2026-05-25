# Prescriptions Domain

> Medicines management and prescription handling.

## Medicines Endpoints

| Method | Endpoint | Middleware | Description |
|--------|----------|-----------|-------------|
| `GET` | `/v1/medicines` | `auth:api`, `active` | List all medicines (searchable, filterable by `manufacturer`) |
| `GET` | `/v1/medicines/{medicine}` | `auth:api`, `active` | Get a single medicine |
| `POST` | `/v1/medicines` | `auth:api`, `active` | Create a new medicine (requires `medicines.create` permission; patients limited to **15/day**) |
| `PUT` | `/v1/medicines/{medicine}` | `auth:api`, `active`, `staff:doctor` | Update a medicine |
| `DELETE` | `/v1/medicines/{medicine}` | `auth:api`, `active`, `staff:doctor` | Delete a medicine |

> `staff:doctor` middleware allows **admin**, **receptionist**, and **doctor** roles. **Patients** can also create medicines (up to 15 per day). All authenticated users can read.

## Query Parameters (GET /v1/medicines)

| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | integer | Items per page (max 100, default 20) |
| `page` | integer | Page number |
| `search` | string | Search in name (ar/en), description (ar/en), barcode, or manufacturer |
| `manufacturer` | string | Filter by exact manufacturer name |

## Prescriptions Endpoints

| Method | Endpoint | Middleware | Description |
|--------|----------|-----------|-------------|
| `GET` | `/v1/medical-records/{medical_record}/prescriptions` | `auth:api`, `active` | List prescriptions for a medical record (role-scoped) |
| `POST` | `/v1/medical-records/{medical_record}/prescriptions` | `auth:api`, `active` | Create a prescription (admin/doctor only) |
| `GET` | `/v1/prescriptions/{prescription}` | `auth:api`, `active` | Get a single prescription (role-scoped) |
| `PUT` | `/v1/prescriptions/{prescription}` | `auth:api`, `active` | Update a prescription (admin/doctor only) |
| `DELETE` | `/v1/prescriptions/{prescription}` | `auth:api`, `active` | Delete a prescription (admin/doctor only) |

### Authorization Rules

| Role | List/Show | Create/Update/Delete |
|------|-----------|---------------------|
| **Admin** | All records | ✅ Full access |
| **Doctor** | Own patients only (`medical_record.doctor_id = this`) | ✅ Own records only |
| **Receptionist** | All records (read-only) | ❌ |
| **Patient** | Own records only (`medical_record.patient_id = this`) | ❌ |

### Deletion Protection

Prescriptions **cannot** be deleted if:
- Status is `archived` or `expired`
- More than **2 days** have passed since creation (`created_at`)

When a prescription is deleted, all associated items are **cascade-deleted** automatically (DB-level).

### Request Body (POST)

```json
{
    "prescription_date": "2026-05-25",
    "status": "active",
    "notes": "Take twice daily with meals",
    "items": [
        {
            "medicine_id": "019e1d0f-...",
            "dosage": "500mg",
            "frequency": "3 times daily",
            "duration": "7 days",
            "instructions": "After meals"
        }
    ]
}
```

- `prescription_date` (date, optional, defaults to today)
- `status` (string, optional, one of: `active`, `archived`, `expired`, defaults to `active`)
- `notes` (string, optional, max 5000)
- `items` (array, optional, max 50) — create items inline with the prescription
  - `items.*.medicine_id` (string, required, uuid, exists:medicines,id)
  - `items.*.dosage` (string, required, max 255)
  - `items.*.frequency` (string, required, max 255)
  - `items.*.duration` (string, required, max 255)
  - `items.*.instructions` (string, optional, max 5000)

### Request Body (PUT)

```json
{
    "prescription_date": "2026-05-26",
    "status": "active",
    "notes": "Updated notes"
}
```

### Response

```json
{
    "status": 200,
    "message": "Prescription retrieved successfully",
    "data": {
        "id": "019e1d0f-...",
        "medical_record_id": "019e1d0f-...",
        "prescription_date": "2026-05-25",
        "status": "active",
        "notes": "Take twice daily",
        "items": [
            {
                "id": "019e1d0f-...",
                "prescription_id": "019e1d0f-...",
                "medicine_id": "019e1d0f-...",
                "dosage": "500mg",
                "frequency": "3 times daily",
                "duration": "7 days",
                "instructions": "After meals",
                "created_at": "2026-05-25T00:00:00.000000Z",
                "updated_at": "2026-05-25T00:00:00.000000Z"
            }
        ],
        "created_at": "2026-05-25T00:00:00.000000Z",
        "updated_at": "2026-05-25T00:00:00.000000Z"
    }
}
```

## Prescription Items Endpoints

| Method | Endpoint | Middleware | Description |
|--------|----------|-----------|-------------|
| `GET` | `/v1/prescriptions/{prescription}/items` | `auth:api`, `active` | List all items for a prescription (role-scoped) |
| `POST` | `/v1/prescriptions/{prescription}/items` | `auth:api`, `active` | Add an item to a prescription (admin/doctor only) |
| `GET` | `/v1/prescription-items/{prescription_item}` | `auth:api`, `active` | Get a single item (role-scoped) |
| `PUT` | `/v1/prescription-items/{prescription_item}` | `auth:api`, `active` | Update an item (admin/doctor only) |
| `DELETE` | `/v1/prescription-items/{prescription_item}` | `auth:api`, `active` | Delete an item (admin/doctor only) |

### Authorization Rules

Same access control as Prescription (inherited via the parent prescription's medical record).

| Role | List/Show | Create/Update/Delete |
|------|-----------|---------------------|
| **Admin** | All items | ✅ Full access |
| **Doctor** | Own patients only | ✅ Own prescriptions only |
| **Receptionist** | All items (read-only) | ❌ |
| **Patient** | Own items only | ❌ |

### Request Body (POST / PUT /v1/prescriptions/{prescription}/items)

```json
{
    "medicine_id": "019e1d0f-...",
    "dosage": "500mg",
    "frequency": "3 times daily",
    "duration": "7 days",
    "instructions": "After meals"
}
```

- `medicine_id` (string, required, uuid, exists:medicines,id)
- `dosage` (string, required, max 255)
- `frequency` (string, required, max 255)
- `duration` (string, required, max 255)
- `instructions` (string, optional, max 5000)

### Response

```json
{
    "status": 200,
    "message": "Prescription item retrieved successfully",
    "data": {
        "id": "019e1d0f-...",
        "prescription_id": "019e1d0f-...",
        "medicine_id": "019e1d0f-...",
        "dosage": "500mg",
        "frequency": "3 times daily",
        "duration": "7 days",
        "instructions": "After meals",
        "created_at": "2026-05-25T00:00:00.000000Z",
        "updated_at": "2026-05-25T00:00:00.000000Z"
    }
}
```

## Prescriptions Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | uuid PK | |
| `medical_record_id` | uuid FK | References `medical_records.id` |
| `prescription_date` | date/nullable | Date the prescription was issued |
| `status` | string | `active`, `archived`, `expired` (default: `active`) |
| `notes` | text/nullable | Doctor's notes |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

## Architecture

```
MedicineController
 └── index()               → MedicineResource collection (with search & filter)
 └── show()                → MedicineResource
 └── store()               → CreateMedicineAction
 └── update()              → UpdateMedicineAction
 └── destroy()             → DeleteMedicineAction

PrescriptionController
 └── index()               → PrescriptionResource collection (role-scoped)
 └── show()                → PrescriptionResource (role-scoped)
 └── store()               → CreatePrescriptionAction (admin/doctor only)
 └── update()              → UpdatePrescriptionAction (admin/doctor only)
 └── destroy()             → DeletePrescriptionAction (admin/doctor only; blocked if archived/expired/older than 2 days)

PrescriptionItemController
 └── index()               → PrescriptionItemResource collection (role-scoped)
 └── show()                → PrescriptionItemResource (role-scoped)
 └── store()               → CreatePrescriptionItemAction (admin/doctor only)
 └── update()              → UpdatePrescriptionItemAction (admin/doctor only)
 └── destroy()             → DeletePrescriptionItemAction (admin/doctor only)
```

## Prescription Items Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | uuid PK | |
| `prescription_id` | uuid FK | References `prescriptions.id` (cascade on delete) |
| `medicine_id` | uuid FK | References `medicines.id` |
| `dosage` | string | e.g. "500mg" |
| `frequency` | string | e.g. "twice daily" |
| `duration` | string | e.g. "7 days" |
| `instructions` | text/nullable | e.g. "Take with meals" |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
