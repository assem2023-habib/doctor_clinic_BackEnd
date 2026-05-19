# Locations Domain

> Manages countries and cities with multilingual names (Arabic/English). Countries are public read-only; write operations require admin.

## Endpoints

### Countries

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| GET | `/v1/countries` | None | List all countries (with cities) |
| GET | `/v1/countries/{country}` | None | Get a single country |
| POST | `/v1/countries` | `auth:api`, `admin` | Create a country |
| PUT | `/v1/countries/{country}` | `auth:api`, `admin` | Update a country |
| DELETE | `/v1/countries/{country}` | `auth:api`, `admin` | Delete a country (cascade cities) |

### Cities

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| GET | `/v1/cities` | None | List cities (filterable by country_id) |
| GET | `/v1/cities/{city}` | None | Get a single city |
| POST | `/v1/cities` | `auth:api`, `admin` | Create a city |
| PUT | `/v1/cities/{city}` | `auth:api`, `admin` | Update a city |
| DELETE | `/v1/cities/{city}` | `auth:api`, `admin` | Delete a city |

## Architecture

```
CountryController / CityController
 ├── index()  → CountryResource/CityResource::collection (with search + city/country_id filter)
 ├── show()   → loads relation, returns resource
 ├── store()  → CreateAction (CountryData/CityData DTO from StoreRequest)
 ├── update() → UpdateAction (CountryData/CityData DTO from UpdateRequest)
 └── destroy() → DeleteAction
```

- **Models:** `Country` (UUID v7, `name` as JSON `{ar, en}`, `code` 2-letter, `flag`), `City` (UUID v7, `name` as JSON `{ar, en}`, `country_id` FK)
- **Resources:** `CountryResource` includes `cities` when loaded, `CityResource` includes `country_id`
- **Names:** Multilingual — stored as JSON `{"ar": "مصر", "en": "Egypt"}`; searchable by both languages
- **DTOs:** `CountryData` / `CityData` — construct from store or update requests, convert to `toArray()`
- **Actions:** 6 total — simple pass-through to `Model::create()` / `->update()` / `->delete()`
