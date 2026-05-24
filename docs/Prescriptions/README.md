# Prescriptions Domain

> Medicines management and prescription handling. Currently implemented: **Medicines CRUD**. Prescriptions and Prescription Items are defined in the database schema but not yet exposed via API.

## Medicines Endpoints

| Method | Endpoint | Middleware | Description |
|--------|----------|-----------|-------------|
| `GET` | `/v1/medicines` | `auth:api`, `active` | List all medicines (searchable, filterable by `manufacturer`) |
| `GET` | `/v1/medicines/{medicine}` | `auth:api`, `active` | Get a single medicine |
| `POST` | `/v1/medicines` | `auth:api`, `active`, `staff:doctor` | Create a new medicine |
| `PUT` | `/v1/medicines/{medicine}` | `auth:api`, `active`, `staff:doctor` | Update a medicine |
| `DELETE` | `/v1/medicines/{medicine}` | `auth:api`, `active`, `staff:doctor` | Delete a medicine |

> `staff:doctor` middleware allows **admin**, **receptionist**, and **doctor** roles. Read-only endpoints are available to **patients** as well.

## Query Parameters (GET /v1/medicines)

| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | integer | Items per page (max 100, default 20) |
| `page` | integer | Page number |
| `search` | string | Search in name (ar/en), description (ar/en), barcode, or manufacturer |
| `manufacturer` | string | Filter by exact manufacturer name |

## Medicines Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | uuid PK | |
| `name` | json | `{ "ar": "...", "en": "..." }` |
| `description` | json/nullable | `{ "ar": "...", "en": "..." }` |
| `barcode` | string/nullable | Barcode number |
| `manufacturer` | string/nullable | Manufacturer name |
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
```
