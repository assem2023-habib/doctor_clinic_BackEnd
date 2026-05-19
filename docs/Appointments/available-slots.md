# Available Slots

> Get available appointment time slots for a doctor on a given date. No authentication required.

## Route Information

- **Method:** `GET`
- **Path:** `/v1/doctors/{doctor}/available-slots`
- **Middleware:** None

## Request

| Parameter | Type | Default | Constraints | Description |
|-----------|------|---------|-------------|-------------|
| `date` | string (query) | — | required, date, after_or_equal:today | Target date (Y-m-d) |
| `limit` | integer (query) | all (or 20) | 1–100 | Items per page |
| `page` | integer (query) | 1 | min 1 | Page number |
| `doctor` | string (route) | — | UUID v7 | Doctor ID |

### Example

```
GET /v1/doctors/0194f1e2-3a7b-7f80-9c6d-8e5f4a3b2c1d/available-slots?date=2026-06-01&limit=2&page=1
```

## Service: `AvailableSlotsService`

```php
public function getAvailableSlots(Doctor $doctor, string $date, int $slotDurationMinutes = 120): array
```

1. Parses day of week from the date (e.g. `monday`)
2. Loads the doctor's active `schedules` for that day
3. Loads existing appointments for that date with blocking statuses (Set, Accepted, Pending, Confirmed)
4. Generates contiguous 120-minute slots from each schedule's `start_time → end_time`
5. Filters out slots that overlap with existing appointments
6. Returns array of `['start_time' => 'H:i', 'end_time' => 'H:i']`

## Controller Logic

```php
$allSlots = $this->slotsService->getAvailableSlots($doctor, $request->date);
// Manual pagination on the slots array
$page = $request->integer('page', 1);
$limit = $request->integer('limit', count($allSlots) ?: 20);
$offset = ($page - 1) * $limit;
$slots = array_slice($allSlots, $offset, $limit);
```

## Response

```json
{
  "success": true,
  "message": "Available slots retrieved successfully",
  "data": [
    { "start_time": "09:00", "end_time": "11:00" },
    { "start_time": "11:00", "end_time": "13:00" }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "limit": 2,
      "total": 4,
      "hasNextPage": true,
      "hasPreviousPage": false
    }
  }
}
```

## Sequence Diagram

```
Client     AvailableSlotsService          Doctor Model        Appointment Model
  │                │                          │                     │
  │── GET /available-slots?date=2026-06-01 ──>│                     │
  │                │── parse dayOfWeek ───────>│                     │
  │                │── schedules(day, active) ─>│                    │
  │                │<── schedule list ─────────│                     │
  │                │── existing appointments   │                     │
  │                │── (blocking statuses) ───>│                     │
  │                │<── appointment list ──────│                     │
  │                │── generate non-overlapping 2h slots             │
  │                │── return slots                                  │
  │<── 200 OK ─────│                          │                     │
```

## Errors

| Status | Condition |
|--------|-----------|
| 404 | Doctor not found |
| 422 | Invalid or missing `date` parameter |
