## Phase 1: Bootstrap and Repo Baseline

### Sprint 1: Delivery Baseline and Planning Control — CLOSED

| # | Task | Status |
|---|------|--------|
| 1 | Create `plan/current.md` as source of truth | done |
| 2 | Confirm repo execution baseline (Docker, Laravel, DB, tests) | done |
| 3 | Initialize Git repository and create initial baseline commit | done |
| 4 | Confirm Laravel Boost MCP is installed and operational | done |
| 5 | Run full test suite clean — zero failures | done |

---

## Phase 2: Domain Foundation

### Sprint 2: Schema and First Domain Layer — CLOSED

| # | Task | Status |
|---|------|--------|
| 1 | Create `plan/schema.md` — core entities, fields, relationships | done |
| 2 | Tighten `plan/schema.md` to migration-ready state | done |
| 3 | Write first migration wave (7 tables) | done |
| 4 | Write first model wave + enums | done |

---

### Sprint 3: Factory and Test Foundation — CLOSED

| # | Task | Status |
|---|------|--------|
| 1 | Create factories for all domain models and write relationship/enum tests | done |

---

## Phase 3: Domain Logic

### Sprint 4: Availability and Booking Core — CLOSED

| # | Task | Status |
|---|------|--------|
| 1 | Implement `AvailabilityService` | done |
| 2 | Implement `BookingCreationService` with input guards | done |
| 3 | Implement `AvailableSlotsService` | done |
| 4 | Expose `GET /api/fields/{field}/available-slots` endpoint | done |

---

## Phase 4: Booking Creation API

### Sprint 5: Create Booking Endpoint

**Goal:** Expose the booking creation flow as a protected API endpoint.

---

### Sprint 5 Task Queue

| # | Task | Status |
|---|------|--------|
| 1 | Implement `POST /api/fields/{field}/bookings` — authenticated booking creation | next |

**Task 1 scope:**
- Sanctum token authentication on the route (auth:sanctum middleware)
- `BookingController@store` or invokable `CreateBookingController`
- Validate: `starts_at` (required, date), `ends_at` (required, date, after:starts_at)
- Delegate to `BookingCreationService`
- Return created booking as JSON (no Eloquent Resource yet — plain `$booking->toArray()` is fine)
- Return 422 with error message when slot is unavailable or input guards fail
- No payment logic, no admin confirmation logic yet

**Tests required:** yes.

**Acceptance criteria:**
- Unauthenticated request returns 401.
- Valid request creates a booking and returns 201 with booking data.
- Request for unavailable slot returns 422.
- Request with `ends_at` before `starts_at` returns 422.
- `php artisan test --compact` passes with zero failures.

---

## Tests Policy

- Every task that touches app code must include or update tests.
- Feature tests are preferred over unit tests.
- Tests must cover happy path, failure path, and key edge cases.
- No task is complete until its tests pass.

---

## Acceptance Criteria Style

- One task at a time.
- Acceptance criteria are written before execution begins.
- Criteria must be verifiable (test output, route response, or artisan command result).
- No task is signed off without criteria being met.

---

## Notes / Guardrails

- Do not start auth implementation, payment, or admin panel work yet.
- Do not build booking creation until availability checking is tested and accepted.
- Do not expand a task's scope during execution.
- One active task at a time. Complete and verify before moving to the next.
