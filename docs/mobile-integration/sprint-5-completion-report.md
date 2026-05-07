# Sprint 5 — Completion Report (Sports Profile)

**Date completed:** 2026-05-06
**Branch:** `feature/mobile-integration-sprint-5`
**Total commits:** 5 feature commits + 1 completion commit

## ✅ Phase A: Discovery

- **Source services identified:**
  - `/profile/stats` → inline in `PlayerController@stats`. Sprint 5
    extracted into `App\Services\Profile\PlayerStatsService`.
  - `/profile/achievements` → inline in `PlayerController@achievements`.
    Sprint 5 extracted into `App\Services\Profile\AchievementsService`.
  - `/bookings/past` → `BookingController@past`, paginated by 15.
    Sprint 5 reuses the existing `Booking::scopePast` capped at 5.
- **Aggregation plan:** PHP-side bucketing (`Carbon::startOfWeek()`)
  rather than driver-aware SQL. At 12 weeks × low-double-digit rows
  per user the SQL split is overkill; the Sprint 2
  `Venue::scopeNearby` driver pattern is the upgrade path.
- **Cache strategy:** 15-minute TTL keyed by `user_id +
  Monday-of-week date`. `BookingObserver` (already exists) gained
  `created`/`updated`/`deleted` hooks into
  `WeeklyActivityService::forget`.

## ✅ Phase B: Implementation

### Endpoints live (2 new)

| Method + Path | Controller method | Highlights |
|---|---|---|
| GET /sports-profile/me | SportsProfileController@me | Composite of stats + achievements + last 5 past bookings; uses 3 services |
| GET /sports-profile/weekly-activity | SportsProfileController@weeklyActivity | 12-week aggregation, 15-min cache, observer-driven invalidation |

### Tests: 14 new total

- `MeTest` (5): documented shape, 5-booking cap, unlocked-achievement
  points, empty-state, 401.
- `WeeklyActivityTest` (7): 12-week zero-fill, real counts/hours
  math, cancelled-excluded, sort order, cache-hit (no DB queries),
  cache-invalidation-on-create, 401.
- `Phase09Envelope` (+2): smoke envelope tests for the two new
  endpoints.

## 📊 Test Suite Health

- **Inherited from Sprint 4:** 485 passing / 0 failing
- **After Sprint 5:** **499 passing / 0 failing** (1767 assertions)
- Net new tests added: **+14**

`vendor/bin/pint --dirty --format agent` passes on every commit.

## 📊 Phase 9 Verification (Teams + Sports Profile)

- ✅ Match: **13 of 13** (was 11 of 11; +2 new sports-profile endpoints)

Total covered endpoints across all phases: **82** (was 80).

## 📁 Files Created

### Controllers
- `app/Http/Controllers/Api/V1/SportsProfileController.php`

### Services
- `app/Services/Profile/PlayerStatsService.php`
- `app/Services/Profile/AchievementsService.php`
- `app/Services/SportsProfile/WeeklyActivityService.php`

### Tests
- `tests/Feature/SportsProfile/MeTest.php` (5 tests)
- `tests/Feature/SportsProfile/WeeklyActivityTest.php` (7 tests)

### Docs
- `docs/mobile-integration/sprint-5-discovery.md`
- `docs/mobile-integration/sprint-5-completion-report.md` (this file)

## 📝 Files Modified

- `app/Http/Controllers/Api/V1/PlayerController.php` — stats and
  achievements methods now delegate to the new services. Wire format
  unchanged.
- `app/Observers/BookingObserver.php` — gained `deleted` hook plus
  `forgetWeeklyActivityCache` helper called from
  `created`/`updated`/`deleted`. Soft failure (logged), never fatal.
- `routes/api.php` — `sports-profile/me` and
  `sports-profile/weekly-activity` registered under
  `auth:sanctum`.
- `tests/Feature/MobileEnvelope/Phase09TeamsSportsProfileTest.php`
  — +2 envelope smoke tests; header doc-block updated.
- `docs/mobile-integration/{verification-results,decision-matrix,
  api-paths-canonical,CHANGELOG}.md` — Sprint 5 entries added.

## 🎯 Recommendations for Sprint 6 (Maps)

1. **The driver-aware pattern from Sprint 2 will hold up for spatial
   queries.** `Venue::scopeNearby` already does Haversine on
   MySQL/MariaDB and falls back to a portable bounding-box on
   SQLite. Sprint 6's `GET /venues/by-bounds` endpoint can reuse the
   bounding-box arm directly (no Haversine needed for a viewport
   rectangle). Don't reinvent.
2. **Cache the by-bounds endpoint similarly to weekly-activity.**
   Tile-based cache keys (`{zoom}:{tile_x}:{tile_y}`) at 5-minute
   TTL would be a natural upgrade if profiling shows hot tiles.
   Sprint 6 should at minimum write the endpoint with a `Cache::
   remember` wrapper from day one — retrofitting caching after the
   fact is harder than baking it in.
3. **Use the BookingObserver pattern for cache invalidation.**
   Map data (venue locations, statuses) changes rarely, but if
   Sprint 6 introduces venue position edits, hook a
   `VenueObserver` to wipe affected tile caches. The Sprint 5
   `forgetWeeklyActivityCache` precedent shows the soft-failure
   shape (try/catch + log) so a stale cache never breaks a write.

## 🔥 Lessons from Sprint 5

1. **Resist scope creep — and the prompt called this out.** The
   temptation to add a 7-day version, a per-sport breakdown, or a
   monthly-stats sibling endpoint was real. Keeping to the spec
   shipped the sprint in well under the 3-4 day budget. Performance
   polish belongs in Sprint 8.
2. **Service extraction before composition.** Pulling
   `PlayerStatsService`/`AchievementsService` out before writing
   `SportsProfileController@me` meant the new endpoint never
   imported `PlayerController`. Tiny services with array-returning
   methods are the simplest abstraction for shared payload shapes —
   no DTOs, no resources, just arrays in lock-step.
3. **PHP-side aggregation is fine at this scale.** The temptation
   to write `WEEK()` SQL was strong (especially with the prompt
   suggesting it). For 12 buckets × small input the loop is faster
   to ship and equally fast to run. Drivers are an upgrade lever,
   not a default.
