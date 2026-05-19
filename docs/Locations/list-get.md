# List & Get Locations

> Public read-only endpoints for browsing countries and cities. Countries include nested cities when loaded.

## Route Information

| Method | Path | Auth |
|--------|------|------|
| GET | `/v1/countries` | None |
| GET | `/v1/countries/{country}` | None |
| GET | `/v1/cities` | None |
| GET | `/v1/cities/{city}` | None |

## List Countries

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `limit` | integer | 20 | 1–100 | Items per page |
| `search` | string | — | — | Search in `name.ar` or `name.en` (LIKE) |

```php
Country::with('cities')
    ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
        $q->where('name->ar', 'like', "%{$v}%")
          ->orWhere('name->en', 'like', "%{$v}%");
    }))
    ->paginate(min($limit, 100));
```

## List Cities

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `limit` | integer | 20 | 1–100 | Items per page |
| `country_id` | string | — | UUID | Filter by country |
| `search` | string | — | — | Search in `name.ar` or `name.en` |

```php
City::with('country')
    ->when($request->country_id, fn ($q, $v) => $q->where('country_id', $v))
    ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
        $q->where('name->ar', 'like', "%{$v}%")
          ->orWhere('name->en', 'like', "%{$v}%");
    }))
    ->paginate(min($limit, 100));
```

## Show

Uses implicit route model binding; loads related `cities` (for Country) or `country` (for City).

## Responses

### Country

```json
{
  "success": true,
  "message": "Country retrieved successfully",
  "data": {
    "id": "0194f1e2-7a8b-9f01-0c6d-1e2f3a4b5c6d",
    "name": { "ar": "مصر", "en": "Egypt" },
    "code": "EG",
    "flag": "🇪🇬",
    "cities": [
      {
        "id": "0194f1e2-8a9b-0f12-1c6d-2e3f4a5b6c7d",
        "name": { "ar": "القاهرة", "en": "Cairo" },
        "country_id": "0194f1e2-7a8b-9f01-0c6d-1e2f3a4b5c6d",
        "created_at": "2026-05-19T10:00:00.000000Z",
        "updated_at": "2026-05-19T10:00:00.000000Z"
      }
    ],
    "created_at": "2026-05-19T10:00:00.000000Z",
    "updated_at": "2026-05-19T10:00:00.000000Z"
  }
}
```

### City

```json
{
  "success": true,
  "message": "City retrieved successfully",
  "data": {
    "id": "0194f1e2-8a9b-0f12-1c6d-2e3f4a5b6c7d",
    "name": { "ar": "القاهرة", "en": "Cairo" },
    "country_id": "0194f1e2-7a8b-9f01-0c6d-1e2f3a4b5c6d",
    "created_at": "2026-05-19T10:00:00.000000Z",
    "updated_at": "2026-05-19T10:00:00.000000Z"
  }
}
```

## Errors

| Status | Condition |
|--------|-----------|
| 404 | Country/city not found |
