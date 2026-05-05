# Sprint 0 — Completion Report

**Date completed:** 2026-05-05
**Branch:** feature/mobile-integration-sprint-0
**Total commits:** 10 (this report makes the 10th)

## ✅ Tasks Completed

- Determined the default branch (`master`) and cut
  `feature/mobile-integration` and `feature/mobile-integration-sprint-0`
  from it.
- Created the `docs/mobile-integration/` scaffold: README, CHANGELOG,
  BLOCKERS, plus stubs for `api-paths-canonical.md` and
  `verification-results.md`.
- Wrote `tests/MobileIntegrationTest.php` exposing `assertEnvelope`,
  `assertPaginatedEnvelope`, `assertErrorEnvelope`, and `actingAsRole` for
  the four documented roles.
- Wrote `tests/Feature/MobileIntegrationBaseTest.php` with seven probes
  exercising the helpers against existing endpoints (5 pass, 2 fail
  intentionally — see Test Suite Health).
- Created `docs/mobile-integration/postman/local.postman_environment.json`
  with all variables referenced by the existing handoff Postman
  collection plus the prompt-mandated set.
- Captured the MySQL schema baseline at
  `database/schema/mysql-schema.sql` and copied it to
  `docs/mobile-integration/schema-snapshot-sprint-0.sql`.
- Ran the full test suite, fixed all mechanical pre-existing failures
  (27 tests; venue route key was `slug`, not `id`), and recorded the
  remaining failures in BLOCKERS.md.
- Audited the 8 §Backlog Quick Win items and documented the audit (none
  apply to the current branch).
- Wrote `scripts/verify_endpoints.php` (Sprint 0 enumeration skeleton)
  and seeded `docs/postman/inferred/` with a placeholder README.
- Wrote this report.

## ⚠️ Tasks Deferred

None. Every checkbox in §Concrete Deliverables is closed.

## 🔴 Blockers Encountered

Recorded in [BLOCKERS.md](./BLOCKERS.md):

1. **`BACKEND_REQUIREMENTS.md` not present** — Sprint 0 proceeded with
   `docs/api-handoff/mobile-integration-guide.md` as the de-facto
   contract. Decision logged in CHANGELOG.
2. **Venue search scope is MySQL-only** — `Venue::scopeSearchTranslated`
   uses `JSON_UNQUOTE(JSON_EXTRACT(...))` via `whereRaw`, which fails on
   the SQLite test driver and breaks 5 tests.
3. **`POST /api/v1/auth/register` route is missing** — `RoleAssignmentTest`
   expects it; the route doesn't exist. 1 test affected. Sprint 3 (Auth
   flows hardening) decides whether to wire the route or rewrite the
   test.

## 📊 Test Suite Health

The suite runs PHPUnit on SQLite in-memory. `php artisan test --parallel`
falls back to a single process on this machine, so the numbers below come
from `php artisan test --compact`.

|                        | Total | Passing | Failing |
| ---------------------- | ----- | ------- | ------- |
| Before sprint          | 295   | 262     | 33      |
| After sprint           | 302   | 294     | 8       |
| Net change             | +7    | +32     | −25     |

(Before-sprint numbers reconstructed from the first full run after
`composer install`. The 7 tests added are the new
`MobileIntegrationBaseTest` probes.)

### Failing tests remaining (file:line — reason)

Intentional Sprint-0 probes (un-fix in Sprint 1):

- `tests/Feature/MobileIntegrationBaseTest.php:24` —
  `test_envelope_assertion_against_categories_index` —
  `GET /api/v1/categories` returns a raw `CategoryResource::collection()`
  with no `success` / `message` envelope. **Sprint 1 verification
  target.**
- `tests/Feature/MobileIntegrationBaseTest.php:32` —
  `test_paginated_envelope_against_venues_index` —
  `GET /api/v1/venues` returns Laravel's default paginated resource
  shape (`{data, links, meta}`) with no `success` / `message` envelope.
  **Sprint 1 verification target.**

Documented blockers (out of scope this sprint):

- `tests/Feature/VenueDealBrowseTest.php:18` —
  `test_venue_search_is_public_and_does_not_require_authentication` —
  `Illuminate\Database\QueryException` (MySQL JSON functions on SQLite).
- `tests/Feature/VenueDealBrowseTest.php:33` —
  `test_venue_search_returns_matching_venues` — same root cause.
- `tests/Feature/VenueDealBrowseTest.php:42` —
  `test_venue_search_returns_empty_when_no_match` — same root cause.
- `tests/Feature/FieldDealBrowseTest.php:18` —
  `test_venue_search_endpoint_is_publicly_accessible` — same root cause.
- `tests/Feature/FieldDealBrowseTest.php:23` —
  `test_venue_search_returns_ok_with_query_param` — same root cause.
- `tests/Feature/RoleAssignmentTest.php:52` —
  `test_registered_user_has_player_spatie_role` —
  `POST /api/v1/auth/register` returns 404 (route not registered).

## 📁 Files Created

- `docs/mobile-integration/README.md`
- `docs/mobile-integration/CHANGELOG.md`
- `docs/mobile-integration/BLOCKERS.md`
- `docs/mobile-integration/api-paths-canonical.md` (stub)
- `docs/mobile-integration/verification-results.md` (stub)
- `docs/mobile-integration/postman/local.postman_environment.json`
- `docs/mobile-integration/schema-snapshot-sprint-0.sql`
- `docs/mobile-integration/sprint-0-completion-report.md` (this file)
- `docs/postman/inferred/README.md`
- `database/schema/mysql-schema.sql`
- `scripts/verify_endpoints.php`
- `tests/MobileIntegrationTest.php`
- `tests/Feature/MobileIntegrationBaseTest.php`

## 📝 Files Modified

Pre-existing test files updated to use `$venue->slug` for the route key
(grouped under one commit, `fix(sprint-0): use venue slug for route key in
feature tests`):

- `tests/Feature/VenueShowTest.php`
- `tests/Feature/DealShowTest.php`
- `tests/Feature/FieldShowTest.php`
- `tests/Feature/VenueFieldBrowseTest.php`
- `tests/Feature/AvailabilityServiceTest.php`
- `tests/Feature/AvailableSlotsServiceTest.php`
- `tests/Feature/FieldAvailableSlotsTest.php`

## 🎯 Recommendations for Sprint 1

1. **The two probe-test failures are the canonical Sprint 1 verification
   targets.** Decide whether endpoints that return raw
   `Resource::collection()` (e.g. `/api/v1/categories`, `/api/v1/venues`)
   should be wrapped in the `ApiResponse::success()` envelope — and if
   so, do it via a global `JsonResource::wrap()` override or a
   middleware, not endpoint-by-endpoint.
2. **The handoff guide and the existing controllers disagree on
   pagination shape.** Controllers using
   `Resource::collection($paginator)` produce `{data, links, meta}`;
   `ApiResponse::success()` produces `{success, message, data}`. Either
   the contract or the implementation needs to flex. Sprint 1 should
   pick one and document it in `api-paths-canonical.md`.
3. **Make `Venue::scopeSearchTranslated` portable** before any test that
   touches search starts being written. Replace the raw JSON SQL with
   Laravel's `where('name->ar', 'like', $term)` syntax so SQLite tests
   pass.
4. **Confirm whether `POST /v1/auth/register` should exist.** The
   handoff guide documents it; the codebase doesn't expose it. If
   mobile is OTP-only, retire the route and the test.
5. **Generate `docs/postman/inferred/collection.json` early in
   Sprint 1.** Either by hand-writing it from `routes/api.php` or by
   wiring a small Artisan command. The verification script is ready to
   consume it.
