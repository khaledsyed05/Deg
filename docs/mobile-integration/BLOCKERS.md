# Mobile Integration — BLOCKERS

Add an entry only if a step is genuinely impossible to complete without
external action.

Format:

```
## YYYY-MM-DD — Sprint N — <title>
**Blocker:** <what's blocked>
**Why:** <root cause>
**Needs:** <what action from Khaled / external party>
**Workaround applied:** <if any>
```

---

## 2026-05-05 — Sprint 0 — `BACKEND_REQUIREMENTS.md` not present

**Blocker:** The Sprint 0 pre-requisite document is missing from the repo
root.
**Why:** The file was never committed to this repository. A search across
`/var/www/deg-ehjezli` and `~` for any case-insensitive match of
`BACKEND_REQUIREMENTS*` returned no results. The closest existing artefacts
are `docs/api-handoff/mobile-integration-guide.md`, `docs/api-handoff/README.md`,
and `docs/api-handoff/Daq-Ehjezly-API.postman_collection.json`.
**Needs:** Khaled to either (a) drop `BACKEND_REQUIREMENTS.md` at the repo
root, or (b) confirm that `docs/api-handoff/mobile-integration-guide.md` is
the canonical contract (in which case this entry can be retired).
**Workaround applied:** Sprint 0 proceeded using
`docs/api-handoff/mobile-integration-guide.md` as the contract for the
response envelope, role list, and Postman variables. See CHANGELOG entry for
2026-05-05.

---

## 2026-05-05 — Sprint 0 — Venue search scope is MySQL-only

**Blocker:** Five tests fail with `Illuminate\Database\QueryException` when
hitting `GET /api/v1/venues/search`:

- `tests/Feature/VenueDealBrowseTest::test_venue_search_is_public_and_does_not_require_authentication`
- `tests/Feature/VenueDealBrowseTest::test_venue_search_returns_matching_venues`
- `tests/Feature/VenueDealBrowseTest::test_venue_search_returns_empty_when_no_match`
- `tests/Feature/FieldDealBrowseTest::test_venue_search_endpoint_is_publicly_accessible`
- `tests/Feature/FieldDealBrowseTest::test_venue_search_returns_ok_with_query_param`

**Why:** `App\Models\Venue::scopeSearchTranslated()`
(`app/Models/Venue.php:156`) uses
`JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar'))` etc. via `whereRaw`. These are
MySQL JSON functions; SQLite (used in tests via `phpunit.xml`'s
`DB_CONNECTION=sqlite, DB_DATABASE=:memory:`) doesn't support them, so
the prepared statement throws at compile time.

**Needs:** Either (a) rewrite `scopeSearchTranslated()` to use Eloquent's
portable JSON helpers (`whereJsonContains`, `where('name->ar', 'like', ...)`)
that the framework translates per-driver, or (b) switch the test database
to MySQL. Option (a) is preferable — production uses MySQL but the same
queries should be portable.

**Workaround applied:** None. Documented for a focused refactor sprint.

---

## 2026-05-05 — Sprint 0 — `POST /api/v1/auth/register` route is missing

**Blocker:** `tests/Feature/RoleAssignmentTest::test_registered_user_has_player_spatie_role`
posts to `/api/v1/auth/register` and expects 201; gets 404.

**Why:** `routes/api.php` exposes only `auth/otp/{send,verify,resend}` and
`auth/google` under the v1 auth group. There is no register route. The
documented mobile flow at
`docs/api-handoff/mobile-integration-guide.md` does describe a
`POST /v1/auth/register` payload, so the route was either removed or
never wired.

**Needs:** Decision from Khaled / mobile team — does mobile actually need
phone+password registration, or is OTP the only allowed onboarding path?
If the former, Sprint 3 (Auth flows hardening) wires the route. If the
latter, the test should be removed or rewritten against `auth/otp/verify`.

**Workaround applied:** None. Test is left failing.
