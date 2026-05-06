# Sprint 6 — Maps Discovery

**Branch:** `feature/mobile-integration-sprint-6`
**Date:** 2026-05-06

---

## A1. Existing venue routes (mobile API only)

```
GET   /api/v1/venues                    VenueController@index
GET   /api/v1/venues/clusters           Geography\GeographyController@venueClusters   ← exists, NOT a stub
GET   /api/v1/venues/featured           VenueController@featured
GET   /api/v1/venues/nearby             VenueController@nearby
GET   /api/v1/venues/popular            VenueController@popular
GET   /api/v1/venues/recently-viewed    VenueController@recentlyViewed
GET   /api/v1/venues/search             VenueController@search
POST  /api/v1/venues/compare            SearchController@compareVenues
GET   /api/v1/venues/{venue}            VenueController@show
GET   /api/v1/venues/{venue}/availability
GET   /api/v1/venues/{venue}/reviews
GET   /api/v1/venues/{venue}/slots
GET   /api/v1/venues/{slug}/photos
GET   /api/v1/venues/{slug}/similar
POST  /api/v1/venues/{slug}/report
```

Aliases under `/geography/venues/clusters` also exist (deprecated).

`GET /venues/by-bounds`: **NOT registered.** Confirmed via `grep` —
this is the gap Sprint 6 fills.

## A2. /venues/clusters current state

**The Sprint 6 prompt stated this was "added in Sprint 2 as a stub".
That is NOT what discovery shows.** The current implementation
returns a *real, working, city-grouped* clustering response:

- **Controller method:** `Api\V1\Geography\GeographyController@venueClusters`
- **Service:** `App\Services\Geography\GeographyService::getVenueClusters($lat, $lng, $radiusKm, $zoom)`
- **Query params:** `lat` (required), `lng` (required), `radius` (optional, default 50, max 500), `zoom` (optional, default 12, accepted but ignored)
- **Caching:** 5-minute `Cache::remember`
- **Current return shape:**
  ```json
  {
    "success": true,
    "message": null,
    "data": {
      "clusters": [
        {
          "lat": 33.5138,
          "lng": 36.2765,
          "count": 24,
          "city": "Damascus",
          "distance_km": 0.0
        }
      ],
      "total_venues": 24
    },
    "errors": null
  }
  ```

This is "city-cluster" grouping (each city = one cluster anchored at
the city center) — a legitimate first-pass clustering and exactly
what `BACKEND_REQUIREMENTS.md` line 4622 *suggests* (with `count` per
cluster). The spec's `bounds` field per cluster is not implemented;
city-center coords + venues_count is what's there.

### Decision: do NOT rewrite `/venues/clusters`

The Sprint 6 prompt asked us to convert it to accept
`north/south/east/west` and return the by-bounds shape. That would
be a **regression** — destroying real working clustering for clients
that already use it. Per the Cardinal Rule's Decision Rule 1 (spec
wins), and per the Anti-pattern "don't change semantic behavior
beyond polish", we leave the `/venues/clusters` endpoint
**untouched**.

What we *do* in B4: tighten the Phase-Maps envelope test to assert
the documented success shape (was previously just the loose
envelope) and add a focused data-shape test in `tests/Feature/Venue/`.
No code change to the controller or service.

This deviation from the prompt is logged in CHANGELOG.

## A3. Venue model and indexes

- **`latitude`** column: `decimal(10,8) NULLABLE`
- **`longitude`** column: `decimal(11,8) NULLABLE`
- **Indexes on `venues`:** `club_id`, `category_id`, `status`,
  `(club_id, status, order_column)`. **No `(latitude, longitude)`
  composite index.** Bounding-box queries scan the table today.
- **Existing scopes:** `scopeNearby($lat, $lng, $radiusKm = 10)` —
  driver-aware (MySQL Haversine; SQLite portable bounding-box).
  Uses `whereNotNull` + `whereBetween` on lat/lng for the SQLite arm,
  which is exactly the shape Sprint 6 needs for `withinBounds`.
- **Relationships used by map response:** `category` (BelongsTo
  `VenueCategory`), `club.city`. We won't need media for the
  lightweight map shape.

## A4. /venues/by-bounds spec

Spec is **light** (this is a documented GAP):

> Query: `?ne_lat=&ne_lng=&sw_lat=&sw_lng=`. Returns venues visible
> in current map viewport.
> Auth: Bearer token required.

### Decisions (spec is silent — captured)

- **Query param naming:** the spec example uses `ne_lat`, `ne_lng`,
  `sw_lat`, `sw_lng`. The Sprint 6 prompt suggested `north/south/east/west`.
  We accept **both styles** — the FormRequest normalizes to the
  4-corner floats. Mobile clients can use either.
- **Cap:** 200 default, 500 max. Documented in CHANGELOG as the
  abuse-prevention default.
- **Filters:** `category_id` (optional, exists check). The spec's
  hint about sport filtering is the same column under a different
  name — we accept either `category_id` or `sport_id` (alias).
- **Status filter:** **only `active` venues** are returned by default.
  This matches what every other public-facing venue endpoint does.
- **Auth:** the spec says Bearer token required. We register under
  `auth:sanctum`.
- **Response shape:** lightweight `VenueMapResource`:
  `{id, slug, name, latitude, longitude, category_id, address, city, is_active}`.
  No media, no reviews, no pricing tiers — the map view is dense
  and must paint fast on mobile.

## Implementation plan

### B1 — migration

Add composite index `venues_lat_lng_idx` on `(latitude, longitude)`.
Guard with `Schema::hasIndex` if available, otherwise use a
duplicate-tolerant try/catch.

### B2 — `Venue::scopeWithinBounds`

Portable `whereBetween` on both columns plus `whereNotNull`. No
driver branch needed — `BETWEEN` semantics are identical across
MySQL, MariaDB and SQLite for our decimal lat/lng columns.

PHPDoc explicitly notes: **does NOT handle the antimeridian case**
(box crossing 180°). YAGNI for Syria, where all venues are within
±5° of longitude 38°.

### B3 — endpoint plumbing

- `App\Http\Requests\Api\V1\Venue\ByBoundsRequest`
- `App\Http\Resources\Venue\VenueMapResource` (`@mixin Venue`)
- `App\Http\Controllers\Api\V1\VenueController@byBounds`
- Route registered **before** `/venues/{venue}` wildcard (Sprint 2 lesson)
- Tests: happy path, empty-result, validation failures, filter,
  limit, default-limit, 401.

### B4 — `/venues/clusters` polish

NO controller / service change. Add:
- `tests/Feature/Venue/ClustersTest.php` — asserts the documented
  data-shape (clusters array, count, city, total_venues) and that
  zoom param is accepted.
- Update `PhaseSprintMapsSettingsTest::test_get_venues_clusters_returns_envelope`
  if needed (currently asserts envelope only — keep as is).

### B5 — performance test

Seed 200 venues with random Damascus-area lat/lng, time a
by-bounds query covering 33.4..33.6 / 36.1..36.4, assert <100ms
local / <250ms on slow CI.

### Test fixtures needed

- 200 venues for performance test (use `Venue::factory()->count(200)`)
- A handful of fixed-coord venues for shape tests (10 inside, 5
  outside, 2 with null lat/lng)

### Caching

Per the prompt's Decision Rule 4: **NOT cached.** The data is too
dynamic and the query is cheap.
