# Create, Update & Delete (Admin)

> Admin-only write operations for countries and cities.

## Route Information

| Method | Path | Middleware |
|--------|------|------------|
| POST | `/v1/countries` | `auth:api`, `admin` |
| POST | `/v1/cities` | `auth:api`, `admin` |
| PUT | `/v1/countries/{country}` | `auth:api`, `admin` |
| PUT | `/v1/cities/{city}` | `auth:api`, `admin` |
| DELETE | `/v1/countries/{country}` | `auth:api`, `admin` |
| DELETE | `/v1/cities/{city}` | `auth:api`, `admin` |

## Requests

### Store/Update Country

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `name_ar` | string | required, max:255 | Arabic name |
| `name_en` | string | required, max:255 | English name |
| `code` | string | required, size:2, unique | ISO 3166-1 alpha-2 code |
| `flag` | string | nullable, max:500 | Flag emoji or URL |

```json
{
  "name_ar": "مصر",
  "name_en": "Egypt",
  "code": "EG",
  "flag": "🇪🇬"
}
```

### Store/Update City

| Parameter | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `name_ar` | string | required, max:255 | Arabic name |
| `name_en` | string | required, max:255 | English name |
| `country_id` | string | required, exists:countries,id | Parent country UUID |

```json
{
  "name_ar": "القاهرة",
  "name_en": "Cairo",
  "country_id": "0194f1e2-7a8b-9f01-0c6d-1e2f3a4b5c6d"
}
```

## Actions

All actions are straightforward pass-throughs:

```php
// CreateCountryAction / CreateCityAction
return Model::create($data->toArray());

// UpdateCountryAction / UpdateCityAction
$model->update($data->toArray());
return $model->fresh();

// DeleteCountryAction / DeleteCityAction
$model->delete();
```

## Responses

### Created

```json
{
  "success": true,
  "message": "Country created successfully",
  "data": { ... }
}
```

Status: **201 Created**

### Updated

```json
{
  "success": true,
  "message": "Country updated successfully",
  "data": { ... }
}
```

### Deleted

```json
{
  "success": true,
  "message": "Country deleted successfully",
  "data": null
}
```

Status: **204 No Content**

## Errors

| Status | Condition |
|--------|-----------|
| 401 | Unauthenticated |
| 403 | Forbidden (not admin) |
| 404 | Country/city not found |
| 422 | Validation failed (duplicate code, missing fields, etc.) |
