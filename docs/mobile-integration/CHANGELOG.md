# Mobile Integration — CHANGELOG

Running log of decisions taken during the 8-sprint Mobile Integration plan.

Format:

```
## YYYY-MM-DD — Sprint N — <title>
**Decision:** <what was decided>
**Rationale:** <why>
**Files affected:** <list>
```

---

## 2026-05-05 — Sprint 0 — Default branch resolution

**Decision:** Branch `feature/mobile-integration` is cut from `master`, not
`main`.
**Rationale:** Although the local repository has both `main` and `master`,
`origin/main` was deleted on the remote and `master` is the only branch that
exists on `origin`. `master` is also the active development branch (HEAD of
local work prior to this sprint). Branching from anywhere else would lose
in-flight commits.
**Files affected:** none (branch metadata only).

---

## 2026-05-05 — Sprint 0 — Pre-requisite document missing

**Decision:** Proceed with Sprint 0 using
`docs/api-handoff/mobile-integration-guide.md` as the de-facto contract for the
response envelope, role list, and Postman variables. Logged separately in
`BLOCKERS.md`.
**Rationale:** Khaled re-issued the Sprint 0 prompt twice after I flagged that
`BACKEND_REQUIREMENTS.md` is absent from the repo root. Per the Cardinal Rule
("do not consult; pick, document, proceed") the only consistent reading of the
re-issuance is "proceed without it". The handoff guide already documents the
envelope (`{success, data, meta?}` for success, `{success: false, message,
errors}` for errors) and matches the format the prompt's helpers describe, so
inference is low-risk.
**Files affected:** `tests/MobileIntegrationTest.php`,
`tests/Feature/MobileIntegrationBaseTest.php`,
`docs/mobile-integration/postman/local.postman_environment.json`.

---

## 2026-05-05 — Sprint 0 — Auth driver for `actingAsRole`

**Decision:** `actingAsRole` uses Sanctum (`actingAs($user, 'sanctum')`),
matching the existing `Tests\TestCase::actingAsPlayer/Admin/ClubManager`
helpers.
**Rationale:** `composer.json` declares `laravel/sanctum`; no JWT package is
installed. The existing test helpers all act-as via the `sanctum` guard.
**Files affected:** `tests/MobileIntegrationTest.php`.

---

## 2026-05-05 — Sprint 0 — Roles seeded by base TestCase

**Decision:** `actingAsRole` accepts the four roles named in the prompt
(`player`, `club_manager`, `club_staff`, `admin`) and relies on the existing
`RolesAndPermissionsSeeder` already invoked in `Tests\TestCase::setUp()`.
**Rationale:** Spatie Permission is installed, the seeder runs before every
test, and the existing helpers already rely on these role names.
**Files affected:** `tests/MobileIntegrationTest.php`.

---

## 2026-05-05 — Sprint 0 — Backlog quick-wins audit: items not present

**Decision:** All eight "Sprint-1 discovery" backlog items in the prompt's
§Backlog Quick Wins were verified absent from the current codebase. No
code-level fixes were applied; this entry documents the audit so the items
can be retired from future sprint backlogs unless the code regresses.
**Rationale:** The backlog references symbols that do not exist on this
branch — likely carried over from a prior or sibling codebase. Specific
findings:

- **`SellerController` hardcoded English** — no file in `app/` matches
  `*seller*` (case-insensitive). Nothing to translate.
- **Arabic typos `المفدخل` / `يفرجى`** — `grep -r` across `app/`,
  `resources/`, `lang/`, `database/` returns zero matches.
- **`ServiceCreateEndpointTest` vs `CategoryListResource`** — neither file
  exists. The only `*Service*Test.php` files belong to booking-availability
  services (`AvailabilityServiceTest`, `AvailableSlotsServiceTest`,
  `BookingCreationServiceTest`, `Notification/FcmServiceTest`,
  `PushNotificationServiceTest`) — none of them assert against a category
  resource.
- **`ServiceController` magic `id=1` filter** — no `ServiceController`
  exists in `app/Http/Controllers/` (or any subdirectory).
- **`Lang` trait dead code** — no `Lang` trait exists in `app/`. `grep -rn
  "trait Lang"` returns nothing; `grep -rn "use .*\\Lang;"` (non-Illuminate)
  returns nothing.
- **`ApiController:27` deprecated code** — `app/Http/Controllers/ApiController.php`
  does not exist. The base controller (`Controller.php`) is 10 lines and
  uses only `Illuminate\Foundation\Auth\Access\AuthorizesRequests`. The
  controller-side envelope helper is the `App\Http\Traits\ApiResponse`
  trait.
- **"Premature end of PHP" investigation** — `grep -r` across all log
  files in `storage/logs/` and across `app/` returns zero hits.
- **`JobCompanyLogoTest` `seller_logo_url`** — no `JobCompanyLogoTest.php`
  exists; `seller_logo_url` is not referenced anywhere in `app/` or
  `tests/`.

**Files affected:** none (audit only).

---

## 2026-05-05 — Sprint 1 — Phase A1: portable searchTranslated scope

**Decision:** Replaced `Venue::scopeSearchTranslated()`'s four
`whereRaw("JSON_UNQUOTE(JSON_EXTRACT(...))")` calls with the
equivalent Eloquent arrow-path syntax (`where('name->ar', 'LIKE',
$like)`).
**Rationale:** Eloquent translates `column->json.path` to MySQL's
`JSON_EXTRACT` and SQLite's `json_extract` automatically. The five
search tests in `VenueDealBrowseTest` and `FieldDealBrowseTest` now
pass on the SQLite test driver. Production behaviour is unchanged
because MySQL still resolves the same JSON columns.
**Files affected:** `app/Models/Venue.php`.

---

## 2026-05-05 — Sprint 1 — Phase A2: orphan register test removed

**Decision:** Deleted
`tests/Feature/RoleAssignmentTest::test_registered_user_has_player_spatie_role`.
The other tests in that class are kept.
**Rationale:** `BACKEND_REQUIREMENTS.md` documents an OTP-only mobile
auth flow (`auth/otp/{send,verify,resend}`) with profile completion
keyed on the `challenge_uuid` from `/auth/otp/verify`. There is no
`POST /auth/register` endpoint, and per the spec there shouldn't be.
If mobile decides later it needs phone+password, Sprint 3 will add
both the endpoint and a fresh test.
**Files affected:** `tests/Feature/RoleAssignmentTest.php`,
`docs/mobile-integration/BLOCKERS.md` (resolution note).

---

## 2026-05-05 — Sprint 1 — Phase A3: post-Phase-A baseline

**Decision:** Phase A baseline locked at 299 passing / 2 failing / 301
total. The 2 remaining failures are the Sprint-0 intentional envelope
probes in `MobileIntegrationBaseTest` (categories index + venues
index) — left red on purpose because they document where existing
controllers diverge from the spec's response envelope.
**Rationale:** Confirms Phase A's goal: every previously-failing test
that was a noise / inherited bug is now green; only the deliberate
verification probes remain as red signal.
**Files affected:** none (re-run only).
