# Sprint 5 — Sports Profile Discovery

**Branch:** `feature/mobile-integration-sprint-5`
**Date:** 2026-05-06

---

## A1. Source services for `/sports-profile/me`

### `/profile/stats` → `PlayerController@stats`

Backed by the `PlayerStats` model (`PlayerStats::firstOrCreate(['user_id' => …])`).
The controller method returns this **payload shape**:

```json
{
  "total_bookings": int,
  "completed_bookings": int,
  "cancelled_bookings": int,
  "total_hours_played": int,
  "total_spent": int,
  "favorite_sport": "string|null (translated name)",
  "favorite_venue": "string|null (translated name)",
  "average_rating_given": "decimal:2",
  "current_streak": int,
  "bookings_this_month": int
}
```

There is no separate `ProfileStatsService` — the logic lives in the
controller method. We extract it into a tiny helper / service for
Sprint 5 so `SportsProfileController@me` can reuse it without
double-construction. Decision: **introduce
`App\Services\Profile\PlayerStatsService::statsArray(User)`** that
returns the same array shape.

### `/profile/achievements` → `PlayerController@achievements`

Reads `auth()->user()->achievements` (an Eloquent `HasMany`).
**Payload shape:**

```json
{
  "achievements": [
    {
      "type": "string (AchievementType enum value)",
      "metadata": "{...}",
      "progress": int,
      "target": int,
      "unlocked": bool,
      "unlocked_at": "ISO8601 | null"
    }
  ],
  "total_points": int
}
```

Same observation: extracted into
`App\Services\Profile\AchievementsService::achievementsArray(User)`.

### `/bookings/past` → `BookingController@past`

Returns `BookingListResource::collection(...)` paginated by 15.
For Sprint 5 we cap at **5** items and pull the same resource shape
so the wire format matches what the dedicated endpoint already
emits. Reuses the existing `Booking::scopePast()` (`status IN
[Completed, NoShow, Cancelled] OR ends_at < now()`).

---

## A2. Spec response shapes

### `GET /sports-profile/me`

Spec is light: "Aggregation of /profile/stats + /profile/achievements
+ /bookings/past." We compose:

```json
{
  "success": true,
  "message": null,
  "data": {
    "stats": { ...stats payload... },
    "achievements": [ ...achievements list... ],
    "total_points": int,
    "recent_bookings": [ ...up to 5 BookingListResource items... ]
  }
}
```

Decision: flatten `total_points` to top level (sibling of
`achievements`) so mobile doesn't have to pull two levels deep for
the badge count. Mirrors the existing `/profile/achievements`
flattening.

### `GET /sports-profile/weekly-activity`

Spec: `{ weeks: [{ week_start, bookings_count, hours_played }] }`.

Response shape:

```json
{
  "success": true,
  "message": null,
  "data": {
    "weeks": [
      {
        "week_start": "YYYY-MM-DD",   // Monday of that ISO week
        "bookings_count": int,
        "hours_played": int           // SUM(duration_minutes)/60, rounded
      }
      // ... 12 entries, oldest first
    ]
  }
}
```

12 weeks fixed (per spec), oldest first, zero-filled when no
bookings in a week. `week_start` is the Monday of each week (ISO
8601 — Carbon's default).

---

## A3. Aggregation plan

### Bookings table columns we use

- `user_id` — filter
- `starts_at` — datetime; week bucketing source
- `duration_minutes` — for hours-played sum
- `status` — filter to `confirmed`/`completed` (per spec, "activity"
  is anything that actually happened)

### Status filter

`status IN ('confirmed', 'completed')`. Cancelled and `no_show`
bookings do not count as activity. `failed` and `pending_payment`
are excluded — those are pre-confirmation states.

### Driver-aware SQL

Mirrors the Sprint 2 `Venue::scopeNearby` pattern:

- **MySQL / MariaDB:** `WEEK(starts_at, 3)` (mode 3 = ISO 8601 weeks
  starting Monday).
- **SQLite:** `strftime('%Y-%W', starts_at)` returns
  `YYYY-WW` strings; sortable lexically.

We don't actually rely on the driver's week function for the final
shape — instead we fetch raw bookings within the 12-week window
(small N) and bucket in PHP using `Carbon::parse()->startOfWeek()`.
This avoids the driver split entirely while staying performant for
the small input set (~12 weeks × few bookings per week).

If profiling later shows this is hot, the SQL `GROUP BY` path is a
straightforward optimization (driver-aware via Sprint 2 pattern).
For Sprint 5: PHP-side bucketing is correct and portable.

### Cache strategy

- **TTL:** 15 minutes (`now()->addMinutes(15)`).
- **Key:** `sports_profile:weekly:{user_id}:{Y-m-d of current
  Monday}`. Roll-over at week boundaries is automatic (the date
  segment changes).
- **Invalidation:** `WeeklyActivityService::forget(User)` is called
  from `BookingObserver::created`, `updated`, and `deleted` (added
  in Sprint 5). Any booking change for a user wipes their cache so
  the next read recomputes.

---

## A4. Decisions captured

1. Extract two tiny services (`PlayerStatsService`,
   `AchievementsService`) so `SportsProfileController@me` doesn't
   import `PlayerController`. Each returns a plain array; both
   `PlayerController` and `SportsProfileController` use them.
2. Bucket weekly activity in PHP, not SQL. Driver-aware SQL is
   well-known but unnecessary at this volume (12 weeks × at most a
   few hundred bookings per power user).
3. Cache invalidation hooks into the existing `BookingObserver`.
   No new observer.
4. Status filter for "activity": `IN ('confirmed', 'completed')`.
   Cancelled bookings explicitly excluded.
5. `recent_bookings` is capped at 5 per the prompt; uses the
   existing `BookingListResource` shape (no new resource).
