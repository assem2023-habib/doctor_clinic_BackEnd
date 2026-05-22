# Booked Slots

> Get future booked appointment time slots for a doctor. No authentication required. Only appointments from "now" onwards are returned (past appointments excluded).

## Route Information

- **Method:** `GET`
- **Path:** `/v1/doctors/{doctor}/booked-slots`
- **Middleware:** None

## Request

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `date` | string (query) | — | nullable, date | Filter by exact date (Y-m-d) |
| `from_date` | string (query) | — | nullable, date | Filter from date (Y-m-d) |
| `to_date` | string (query) | — | nullable, date | Filter to date (Y-m-d) |
| `limit` | integer (query) | 20 | 1–100 | Items per page |
| `page` | integer (query) | 1 | min 1 | Page number |
| `doctor` | string (route) | — | UUID v7 | Doctor ID |

### Notes

- `from_date` and `to_date` must be used together (both required if one is provided).
- If no date filter is given, all future booked slots are returned.
- All results are filtered to exclude past appointments (before now).

### Example

```
GET /v1/doctors/0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d/booked-slots?from_date=2026-06-01&to_date=2026-06-30&limit=2&page=1
```

## Service: `AvailableSlotsService`

```php
public function getBookedSlots(
    Doctor $doctor,
    int $perPage = 20,
    ?string $date = null,
    ?string $fromDate = null,
    ?string $toDate = null,
): LengthAwarePaginator
```

1. Queries appointments for the given doctor with blocking statuses (Set, Accepted, InProgress, Confirmed)
2. Always excludes past appointments: `appointment_date > today` OR (`appointment_date = today` AND `start_time > now`)
3. Applies optional date filters: exact `date` or range `from_date`/`to_date`
4. Orders by `appointment_date ASC, start_time ASC`
5. Returns a paginated result of `['appointment_date', 'start_time', 'end_time']`

## Controller Logic

```php
$paginator = $this->slotsService->getBookedSlots(
    $doctor, $limit, $request->date, $request->from_date, $request->to_date,
);

$slots = collect($paginator->items())->map(fn ($a) => [
    'appointment_date' => $a->appointment_date?->format('Y-m-d'),
    'start_time' => $a->start_time?->format('H:i'),
    'end_time' => $a->end_time?->format('H:i'),
]);
```

## Response

```json
{
  "status": 200,
  "message": "Booked slots retrieved successfully",
  "data": [
    {
      "appointment_date": "2026-06-01",
      "start_time": "10:00",
      "end_time": "11:00"
    },
    {
      "appointment_date": "2026-06-05",
      "start_time": "09:00",
      "end_time": "10:00"
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "limit": 20,
      "total": 2,
      "hasNextPage": false,
      "hasPreviousPage": false
    }
  }
}
```

## Errors

| Status | Condition |
|--------|-----------|
| 404 | Doctor not found |
| 422 | Invalid date format or `from_date` without `to_date` |
