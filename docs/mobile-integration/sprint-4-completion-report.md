# Sprint 4 — Completion Report (Teams Management)

**Date completed:** 2026-05-06
**Branch:** `feature/mobile-integration-sprint-4`
**Total commits:** 13 feature commits + 1 completion commit

## ✅ Phase 0: `/auth/register` Reconciliation

- Spec doc corrected to reflect OTP-only flow (`BACKEND_REQUIREMENTS.md`).
  The bogus `POST /auth/register` section is replaced with a note
  explaining the canonical flow + `is_new_user` branching.
- Backend was missing the `is_new_user` flag at `data.is_new_user`.
  Added to both `POST /auth/otp/verify` and `POST /auth/google` so
  mobile branches deterministically (true → prompt for name → `PUT
  /profile`; false → home).
- `POST /auth/otp/send` now accepts an optional `channel` param
  (`whatsapp` | `sms`); honored by `OtpService` when present, falls
  back to `BaileysService::detectChannel` when absent.
- Orphan `test_post_auth_register_returns_documented_response` removed.
  Two new tests added asserting `is_new_user` is true for fresh phones
  and false for existing users.
- Finding 6 closed permanently in `decision-matrix.md` and removed
  from `api-paths-canonical.md` "Missing routes" section.
- One small commit: `feat(sprint-4): canonical auth flow — is_new_user
  flag + WhatsApp/SMS channel`.

## ✅ Phase A: Discovery

- **Team model status:** existed (created in Phase 17/18 work). No
  schema changes needed against the `teams` table itself; all
  spec-required columns already present.
- **TeamMember status:** existed as a dedicated model (NOT a pivot)
  with role/status/timestamps. Two captaincy sources of truth
  identified: `teams.captain_id` (canonical) and
  `team_members.role='captain'` — Sprint 4 transfer-captain updates
  both atomically inside one DB transaction.
- **Authorization matrix:** 7 actions × 5 roles documented in
  `docs/mobile-integration/sprint-4-discovery.md`. The matrix anchors
  the policy tests in §B2.
- **Migrations needed:** one new table — `team_invites` — for invite
  codes. No `teams` migration.
- Discovery commit: `docs(sprint-4): teams discovery + authorization
  matrix`.

## ✅ Phase B: Implementation

### Endpoints live (6 new)

| Method + Path | Controller method |
|---|---|
| PUT /teams/{id} | TeamController@update |
| DELETE /teams/{id} | TeamController@destroy |
| POST /teams/{id}/kick | TeamController@kick |
| POST /teams/{id}/transfer-captain | TeamController@transferCaptain |
| POST /teams/{id}/invite | TeamController@generateInvite |
| GET /teams/invite/{code} | TeamController@useInvite |

### TeamPolicy: 6 methods, 28 tests

- `view(?User, Team)` — public split
- `update`, `delete`, `kick`, `invite` — captain-or-admin
- `transferCaptain` — captain-only (admins denied by design)
- `create` — any player

Registered in `AuthServiceProvider`. `TeamPolicyTest` exercises every
(action, role) cell of the authorization matrix.

### Custom exceptions: 6

- `TeamFullException` (422)
- `InviteExpiredException` (410 Gone)
- `InviteUsedUpException` (410 Gone)
- `CannotKickCaptainException` (422)
- `CannotTransferToNonMemberException` (422)
- `TooManyInvitesException` (422)

All extend Symfony's `HttpException` so `bootstrap/app.php`'s
`HttpExceptionInterface` handler renders them in the canonical envelope
without any custom clause. Lang keys in `lang/en/team.php` and
`lang/ar/team.php`.

### Idempotency primitive used in: none

`App\Support\Idempotency` is wallet-typed (returns
`WalletTransaction`). Refactoring it to be generic was out of Sprint 4
scope. Team mutations rely on unique indexes plus DB transactions:

- `team_members (team_id, user_id)` unique index — duplicate joins
  via use-invite are upserted (an existing `left`/`removed` row is
  re-activated rather than insert-failing).
- `team_invites.code` unique index — duplicate code generation is
  retried in a do-while loop.
- `TransferCaptainService::transfer()` runs both `teams.captain_id`
  update and the two `team_members.role` rows in one
  `DB::transaction` so the captaincy sources never drift.

## 📊 Test Suite Health

- **Inherited from Sprint 3:** 404 passing / 0 failing
- **After Sprint 4:** **485 passing / 0 failing** (1642 assertions)
- **Net new tests added:** ~81

| Source | Count |
|---|---|
| Phase 0 auth tests (new) | +2 |
| Phase 0 orphan removed | −1 |
| TeamPolicyTest | 28 |
| UpdateTeamTest | 7 |
| DeleteTeamTest | 7 |
| KickMemberTest | 8 |
| TransferCaptainTest | 7 |
| GenerateInviteTest | 9 |
| UseInviteTest | 8 |
| Phase09Envelope (added) | 6 |
| **Subtotal** | **+81** |

`vendor/bin/pint --dirty --format agent` passes on every commit. The
project still has pre-existing pint failures in untouched files
(seeders, old controllers); those are out of Sprint 4 scope and are
ignored by the dirty filter.

## 📊 Phase 9 Verification (Teams)

- ✅ Match: **11 of 11**
  - `GET /teams`, `POST /teams`, `GET /teams/{id}` — Sprint 1 baseline
  - `PUT /teams/{id}/leave` — Sprint 1 / Phase 18 work
  - `PUT /teams/{id}`, `DELETE /teams/{id}`,
    `POST /teams/{id}/kick`,
    `POST /teams/{id}/transfer-captain`,
    `POST /teams/{id}/invite`,
    `GET /teams/invite/{code}` — Sprint 4 NEW

Total covered endpoints across all phases: **80** (was 74).

## 📁 Files Created

### Migrations
- `database/migrations/2026_05_06_085318_create_team_invites_table.php`

### Models
- `app/Models/TeamInvite.php`

### Policies
- `app/Policies/TeamPolicy.php`

### Exceptions
- `app/Exceptions/Team/TeamFullException.php`
- `app/Exceptions/Team/InviteExpiredException.php`
- `app/Exceptions/Team/InviteUsedUpException.php`
- `app/Exceptions/Team/CannotKickCaptainException.php`
- `app/Exceptions/Team/CannotTransferToNonMemberException.php`
- `app/Exceptions/Team/TooManyInvitesException.php`

### Services
- `app/Services/Team/DeleteTeamService.php`
- `app/Services/Team/KickMemberService.php`
- `app/Services/Team/TransferCaptainService.php`
- `app/Services/Team/GenerateInviteService.php`
- `app/Services/Team/UseInviteService.php`

### Resources
- `app/Http/Resources/Team/TeamResource.php`
- `app/Http/Resources/Team/TeamInviteResource.php`

### Form Requests
- `app/Http/Requests/Api/V1/Team/UpdateTeamRequest.php`
- `app/Http/Requests/Api/V1/Team/KickMemberRequest.php`
- `app/Http/Requests/Api/V1/Team/TransferCaptainRequest.php`
- `app/Http/Requests/Api/V1/Team/GenerateInviteRequest.php`

### Lang
- `lang/en/team.php`, `lang/ar/team.php`

### Factories
- `database/factories/TeamFactory.php`
- `database/factories/TeamMemberFactory.php`
- `database/factories/TeamInviteFactory.php`
- `database/factories/VenueCategoryFactory.php`

### Tests
- `tests/Feature/Team/TeamPolicyTest.php` (28 tests)
- `tests/Feature/Team/UpdateTeamTest.php` (7 tests)
- `tests/Feature/Team/DeleteTeamTest.php` (7 tests)
- `tests/Feature/Team/KickMemberTest.php` (8 tests)
- `tests/Feature/Team/TransferCaptainTest.php` (7 tests)
- `tests/Feature/Team/GenerateInviteTest.php` (9 tests)
- `tests/Feature/Team/UseInviteTest.php` (8 tests)

### Docs
- `docs/mobile-integration/sprint-4-discovery.md`
- `docs/mobile-integration/sprint-4-completion-report.md` (this file)

## 📝 Files Modified

### Auth flow (Phase 0)
- `BACKEND_REQUIREMENTS.md` — corrected register/send/verify spec
- `app/Http/Controllers/Api/V1/AuthController.php` — `is_new_user`,
  `channel` plumbing, `message: null` envelope key
- `app/Http/Requests/Api/V1/Auth/LoginOtpSendRequest.php` — `channel`
  validation
- `app/Services/Auth/OtpService.php` — accept channel override
- `tests/Feature/MobileEnvelope/Phase01AuthTest.php` — drop orphan,
  add two `is_new_user` tests

### Team feature
- `app/Models/Team.php` — HasFactory + invites() relationship
- `app/Models/TeamMember.php` — HasFactory
- `app/Http/Controllers/Api/V1/TeamController.php` — six new methods,
  policy authorize() calls
- `app/Providers/AuthServiceProvider.php` — register TeamPolicy
- `routes/api.php` — wire 6 new routes
- `tests/Feature/MobileEnvelope/Phase09TeamsSportsProfileTest.php` —
  +6 envelope tests

### Mobile-integration docs
- `docs/mobile-integration/decision-matrix.md` — Sprint 4 block, Finding 6 resolved
- `docs/mobile-integration/api-paths-canonical.md` — new Phase 9
  section, missing-routes block updated
- `docs/mobile-integration/verification-results.md` — Phase 9 expands
  to 11; total moves from 74 → 80
- `docs/mobile-integration/CHANGELOG.md` — four new Sprint 4 entries

## 🎯 Recommendations for Sprint 5 (Sports Profile)

1. **Mirror the Sprint 4 policy pattern.** A `SportsProfilePolicy`
   centralizes authorization (own-profile vs others, public vs
   private). Don't scatter inline `auth()->id() === $profile->user_id`
   checks in the controller.
2. **Use the new factory pattern.** Sprint 4 introduced
   `VenueCategoryFactory`, `TeamFactory`, `TeamMemberFactory`. Sprint
   5 should add `SportsProfileFactory` with states up-front so tests
   stop hand-rolling DB rows.
3. **Use `LazilyRefreshDatabase`** in new feature tests — Sprint 4
   tests use it; full-`RefreshDatabase` over the whole 485-test
   suite would explode CI time.
4. **The auth flow is final** — `is_new_user` is now the sole new-user
   signal. Sprint 5 should NOT introduce a parallel mechanism. If
   sports-profile work needs a new-vs-existing split, gate on
   `is_new_user` from the auth response.
5. **`App\Support\Idempotency` refactor** is a candidate side-quest:
   make it generic over a model class so team / sports-profile /
   future writes can reuse it. Not blocking Sprint 5.

## 🔥 Lessons from Sprint 4

1. **Authorization tests are cheap; don't skip cells.** The 28-test
   policy file caught two bugs during development (an early version
   of `transferCaptain` used `||` with admin role; the explicit
   "admin denied" test broke and forced the design discussion).
   The matrix is the spec; tests are the contract.
2. **Two sources of truth for captaincy is a smell** but a
   compounding-cost-to-fix one. Sprint 4 chose to maintain both
   (`teams.captain_id` + `team_members.role='captain'`) atomically
   in `TransferCaptainService` rather than refactor the data model
   mid-sprint. Future work could de-duplicate by deriving
   `is_captain` from one source — either column. Captured as a
   known smell, not a Sprint 4 task.
3. **Eloquent enum casts** mean `$model->where(...)->value('role')`
   returns the enum, not the string. The transfer-captain test had
   to drop to raw `DB::table('team_members')->value('role')` for
   string comparison. Easy to miss; cost ~3 minutes during
   debugging.
4. **The `actingAsRole('player')` helper from MobileIntegrationTest
   doesn't bind a Team factory** because no test before Sprint 4
   needed one. Tests that needed a captain+team built the team
   manually with the new factory after acting-as. This is fine; not
   every helper needs to know about every fixture.
5. **Spec corrections need a permanent home.** Finding 6 sat
   "deferred" through Sprints 2 and 3 because there was no clear
   single owner. Phase 0 of Sprint 4 closed it in 30 minutes once
   the Cardinal Rule made it the obvious right call.
