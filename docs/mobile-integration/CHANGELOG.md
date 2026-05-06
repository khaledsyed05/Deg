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

## 2026-05-06 — Sprint 5 — Sports Profile aggregations

**Decision:** Build `GET /sports-profile/me` (composite of stats +
achievements + last 5 past bookings) and `GET
/sports-profile/weekly-activity` (12-week activity bar-chart
aggregation, oldest-first, zero-filled). Both delivered in one
sprint with caching on the weekly endpoint.
**Rationale:** Two endpoints, both aggregations over data that
already exists. The composite `me` endpoint is straight assembly;
the weekly endpoint trades 15-minute cache freshness for cheap
reads. Cache invalidation hooks into the existing `BookingObserver`
so booking changes wipe the user's cache immediately.
**Files affected:** new — `app/Http/Controllers/Api/V1/SportsProfileController.php`,
`app/Services/Profile/{PlayerStatsService,AchievementsService}.php`,
`app/Services/SportsProfile/WeeklyActivityService.php`,
`tests/Feature/SportsProfile/{Me,WeeklyActivity}Test.php`,
`docs/mobile-integration/sprint-5-discovery.md`. Modified —
`app/Http/Controllers/Api/V1/PlayerController.php` (delegate stats
and achievements to the new services), `app/Observers/BookingObserver.php`
(forget weekly-activity cache on created/updated/deleted),
`routes/api.php`,
`tests/Feature/MobileEnvelope/Phase09TeamsSportsProfileTest.php`.

---

## 2026-05-06 — Sprint 5 — PHP-side bucketing for weekly activity

**Decision:** Bucket weekly activity in PHP (`Carbon::startOfWeek()`)
rather than driver-aware SQL (`WEEK()` / `strftime('%W')`). The Sprint
2 driver-split pattern (`Venue::scopeNearby`) remains the upgrade
path if profiling later flags the simple PHP loop.
**Rationale:** At 12 weeks × low-double-digit rows per active user,
the SQL `GROUP BY` saving is negligible while the maintenance cost
of a driver-aware switch is non-zero. PHP-side bucketing is correct,
portable across MySQL/MariaDB/SQLite, and trivially easy to read.
**Files affected:** `app/Services/SportsProfile/WeeklyActivityService.php`.

---

## 2026-05-06 — Sprint 5 — Activity status filter

**Decision:** Statuses that count as "activity" for the weekly
chart are: `confirmed`, `completed`, `checked_in`, `scheduled`.
Statuses that DO NOT count: `cancelled`, `no_show`, `failed`,
`pending_payment`.
**Rationale:** A cancelled booking is not activity the user
performed; a no-show isn't either. `pending_payment` and `failed`
are pre-confirmation states. The four counted statuses cover both
"upcoming" and "happened" — the chart shows commitment + actual
participation.
**Files affected:** `app/Services/SportsProfile/WeeklyActivityService.php`
(ACTIVE_STATUSES constant), tests assert the filter.

---

## 2026-05-06 — Sprint 4 — Canonical auth flow (Phase 0)

**Decision:** Reject the `POST /auth/register` line item from the spec.
The canonical auth flow is OTP-only: send → verify → branch on
`is_new_user`. New users complete profile via `PUT /profile`. Backend
now exposes `data.is_new_user` on `/auth/otp/verify` and
`/auth/google` so mobile can branch correctly. `/auth/otp/send` now
accepts an optional `channel` ∈ {whatsapp, sms} param mobile uses to
override Baileys auto-detection.
**Rationale:** The spec line item is wrong terminology. Khaled
confirmed in the Sprint 4 prompt that there is no separate registration
endpoint by design. Two prior sprints had deferred this; closing
permanently before any feature code unblocks downstream sprints.
**Files affected:** `BACKEND_REQUIREMENTS.md`,
`app/Http/Controllers/Api/V1/AuthController.php`,
`app/Http/Requests/Api/V1/Auth/LoginOtpSendRequest.php`,
`app/Services/Auth/OtpService.php`,
`tests/Feature/MobileEnvelope/Phase01AuthTest.php`,
`docs/mobile-integration/decision-matrix.md`,
`docs/mobile-integration/api-paths-canonical.md`.

---

## 2026-05-06 — Sprint 4 — TeamPolicy + 6 new endpoints

**Decision:** Build PUT /teams/{id}, DELETE /teams/{id},
POST /teams/{id}/kick, POST /teams/{id}/transfer-captain,
POST /teams/{id}/invite, GET /teams/invite/{code}. Authorization via
a single `TeamPolicy` registered in `AuthServiceProvider`. All 6
endpoints sit under `auth:sanctum`. Hard delete (no soft-delete on
teams). Invites use codes, not direct user lookups; max 5 active
invites per team.
**Rationale:** Spec required these endpoints. Centralizing auth in
a Policy class (vs scattered inline controller checks) anchors the
authorization matrix in one tested module — every regression in any
endpoint is caught immediately.
**Files affected:** new — `app/Policies/TeamPolicy.php`,
`app/Models/TeamInvite.php`,
`database/migrations/2026_05_06_085318_create_team_invites_table.php`,
`app/Services/Team/{Delete,Kick,Transfer,GenerateInvite,UseInvite}…`,
`app/Http/Resources/Team/{TeamResource,TeamInviteResource}.php`,
`app/Http/Requests/Api/V1/Team/{Update,Kick,Transfer,GenerateInvite}…`,
`app/Exceptions/Team/{TeamFull,InviteExpired,InviteUsedUp,
CannotKickCaptain,CannotTransferToNonMember,TooManyInvites}…`,
`lang/{en,ar}/team.php`, `database/factories/{Team,TeamMember,
TeamInvite,VenueCategory}Factory.php`,
`tests/Feature/Team/*` (6 files, 46 endpoint tests),
`tests/Feature/Team/TeamPolicyTest.php` (28 policy tests). Modified —
`app/Models/{Team,TeamMember}.php`,
`app/Http/Controllers/Api/V1/TeamController.php`,
`app/Providers/AuthServiceProvider.php`, `routes/api.php`,
`tests/Feature/MobileEnvelope/Phase09TeamsSportsProfileTest.php`.

---

## 2026-05-06 — Sprint 4 — Transfer-captain is captain-only

**Decision:** Even users with the `admin` role cannot use
`POST /teams/{id}/transfer-captain`. Only the team's current captain
can initiate the transfer.
**Rationale:** Captaincy is an end-user social construct, not a
moderation primitive. If admins ever need to forcibly hand off
ownership, the Filament dashboard is the right surface — exposing
that on the mobile API would be a footgun. Mirrors the pattern
already used in `ClubPolicy::manageStaff` (owner-only).
**Files affected:** `app/Policies/TeamPolicy.php` (transferCaptain
method), `tests/Feature/Team/TeamPolicyTest.php` (explicit denial
test), `tests/Feature/Team/TransferCaptainTest.php` (HTTP-level
denial test).

---

## 2026-05-06 — Sprint 4 — Idempotency primitive not used for teams

**Decision:** Team-mutating endpoints (kick / transfer / invite /
use-invite) do not use `App\Support\Idempotency::run()`. They rely
on unique indexes (`team_members(team_id, user_id)`,
`team_invites.code`) plus DB transactions for atomicity.
**Rationale:** `App\Support\Idempotency` returns a
`WalletTransaction` and is wallet-typed. Refactoring it to be
generic is its own task, not part of Sprint 4. The Sprint 4 prompt
suggested using it, but the type signature would have required
re-typing the helper across the wallet codebase — out of scope.
**Files affected:** none (decision recorded only).

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

---

## 2026-05-05 — Sprint 2 — Phase A1: envelope-helper audit

**Decision:** 55 controller files already `use App\Http\Traits\ApiResponse`;
54 controller files return JSON via `response()->json([...])` or raw
`Resource::collection(...)`. Phase B targets the latter cohort, but
will also revisit the former because some users of the trait still
combine it with bespoke `response()->json` calls in adjacent methods.
**Rationale:** Establishes the size of the work.
**Files affected:** none (audit only).

---

## 2026-05-05 — Sprint 2 — Summary

**Decision:** Sprint 2 (Contract Conformance) closed with 73/73
MobileEnvelope tests green and the full PHPUnit suite at 388/388
green. The work concentrated on three patterns surfaced in Sprint 1:
(1) collection responses missing the envelope, (2) `message` /
`data` keys omitted on bespoke responses, (3) error responses
omitting `data: null`. All three are fixed at the trait /
exception-handler / middleware level — no per-endpoint patching
needed for future controllers.

**Rationale:** Centralising envelope semantics in
`App\Http\Traits\ApiResponse`, `bootstrap/app.php` exception
handlers, and `EnsureJsonErrorShape` middleware means new endpoints
written in Sprint 3+ get the canonical envelope for free if they
either use the trait or let exceptions propagate.

**Files affected:** `app/Http/Traits/ApiResponse.php`,
`app/Providers/AppServiceProvider.php`, `bootstrap/app.php`,
`app/Http/Middleware/EnsureJsonErrorShape.php`, eight
`app/Http/Controllers/Api/V1/*` controllers, `app/Models/Venue.php`,
`routes/api.php`, six new test files under `tests/`.

---

## 2026-05-05 — Sprint 3 — Wallet API summary

**Decision:** Sprint 3 (Wallet API) closed with the 4 spec-named
wallet endpoints data-shape verified, the new
`POST /wallet/pay-booking` live and atomic + idempotent, and a
reusable `App\Support\Idempotency` primitive available for future
sprints. Auto-topup execution is intentionally deferred to
Sprint 8.

**Rationale:** Wallet operations are the only place in the integration
plan where a missed test case has a direct path to financial loss.
The 13-test suite in `tests/Feature/Wallet/PayBookingTest.php` is
the gold standard the prompt asked for.

**Files affected:** 3 new migrations, 2 new resources
(WalletAccountResource, WalletTransactionResource), 2 new exceptions
(InsufficientBalance, BookingNotPayable), 1 new service
(PayBookingService), 1 new request (PayBookingRequest, plus
UpdateWalletSettingsRequest), 1 new support class
(`App\Support\Idempotency`), 1 stub job (CheckAutoTopupJob),
3 new lang files, controller + routes updates, BookingFactory state
extension, and the four mobile-integration docs.

---

## 2026-05-05 — Sprint 2 — Phase A2: non-mobile API consumers

**Decision:** Mobile envelope changes can break freely — no
backward-compatibility shim is needed.
**Rationale:** `routes/web.php` only routes the admin dashboard (no
shared API consumption). `grep -rn "/api/v1" resources/` returns no
hits. The Inertia + Blade dashboard talks to its own admin
controllers (different paths). The only consumer of `/api/v1/*` is
the mobile app, which is still mock-first per `BACKEND_REQUIREMENTS.md`
Phase 9 — so contract changes are absorbed by mobile re-mocking.
**Files affected:** none (audit only).
