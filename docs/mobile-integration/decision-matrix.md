# Mobile Integration — Decision Matrix

For each ⚠️/🔴 finding from `verification-results.md`, what's the
recommended action and who owns it?

## Conventions

- **Backend changes:** modify the Laravel controller, resource, or trait
  to match the spec.
- **Mobile adapts:** mobile team adjusts its DI / model deserialization
  to accept the current backend shape.
- **Shared:** both sides change.
- **Defer:** acknowledge the gap but don't act yet (e.g. depends on a
  later sprint, or a feature that hasn't shipped on either side).

## Findings

### Finding 1 — Resource collections bypass the envelope

- **Affected endpoints (6+):** `GET /categories`, `GET /venues/featured`,
  `GET /venues/search`, `GET /promotions/featured`, `GET /promotions`,
  `GET /wallet/transactions`, `GET /notifications`.
- **Severity:** 🔴 Major. Mobile cannot parse the envelope on collection
  endpoints because there is no `success` key — its `BaseResponse<T>`
  deserializer rejects the body up-front.
- **Recommended action:** **Backend changes — single global fix.**
  Override `Illuminate\Http\Resources\Json\JsonResource::wrap('data')`
  in `app/Providers/AppServiceProvider.php` AND register a response
  macro that wraps any paginated collection in
  `{success: true, message: null, data: <paginator-data>, errors: null,
  meta: <paginator-meta>}`. Each affected controller switches from
  `Resource::collection($paginator)` to
  `$this->paginatedSuccess(Resource::collection($paginator))` (helper
  added to `App\Http\Traits\ApiResponse`).
- **Effort:** Backend ~½ day for the helper + controller updates;
  ~1 day for regression-testing the dashboard which also consumes
  these endpoints. Mobile: 0.
- **Sprint:** 2 (Naming Alignment) is the natural home — paths are
  also being normalized in the same controllers.

### Finding 2 — `message` key omitted on direct `response()->json()` calls

- **Affected endpoints (~5):** `GET /profile`, `PUT /profile`,
  `GET /venues/{slug}`, `GET /venues/{slug}/reviews`,
  `GET /football/matches/{slug}` and similar bespoke responses.
- **Severity:** 🔴 Major (will become ⚠️ Minor after fix is
  formalised — currently mobile rejects responses missing `message`).
- **Recommended action:** **Backend changes — controllers should not
  bypass the `ApiResponse::success()` helper.** Replace the
  ad-hoc `return response()->json(['success' => true, 'data' => ...])`
  with `return $this->success($data)`. The trait already emits
  `message => null` by default.
- **Effort:** Backend ~½ day. Mobile: 0.
- **Sprint:** 2.

### Finding 3 — `data` key omitted on no-content success responses

- **Affected endpoints (~18):** every "delete", "mark-as-read",
  "register-for-event", "leave-team" etc. flow plus a swathe of
  `wallet/*` and `conversations/*` endpoints.
- **Severity:** 🔴 Major (envelope shape divergence).
- **Recommended action:** **Backend changes.** Update
  `ApiResponse::success(?array $data = null, ...)` to default `$data`
  to `null` rather than missing — and audit every controller that
  builds responses by hand to ensure `data` is at least `null`.
  Equivalent fix for `204 No Content` responses: convert to `200`
  with `{..., data: null}`.
- **Effort:** Backend ~1 day. Mobile: 0.
- **Sprint:** 2.

### Finding 4 — Two real 500s in production paths

- **Affected endpoints (2):** `POST /auth/logout`,
  `GET /venues/nearby`.
- **Severity:** 🔴 Major. These crash with uncaught exceptions when
  invoked through the test suite. Not envelope mismatches —
  underlying bugs.
- **Recommended action:** **Backend changes — debug and fix the
  exceptions.** `auth/logout` likely fails on Sanctum
  `currentAccessToken()->delete()` if the personal-access-tokens
  table isn't populated; `venues/nearby` probably fails on the raw
  haversine SQL when no rows match.
- **Effort:** Backend ~2-4h for both.
- **Sprint:** 3 (Auth flows hardening) for `auth/logout`; map fix
  can happen in Sprint 2 or 3 depending on capacity.

### Finding 5 — Geography paths diverge between spec and code

- **Affected endpoints (4):** `GET /cities`,
  `GET /cities/{id}/neighborhoods`, `GET /venues/clusters`,
  `GET /venues/by-bounds`.
- **Severity:** 🔴 Major (the routes the spec names do not exist;
  `geography/...` prefix is used instead).
- **Recommended action:** **Shared.** Backend should add
  spec-compliant aliases (or rename the existing ones); mobile
  should confirm whether it's already calling `/geography/...`
  successfully. If yes — backend just adds redirect aliases. If no
  — backend renames and the dashboard team also updates its
  consumers.
- **Effort:** Backend ~½ day if the existing routes can be aliased
  rather than renamed. Mobile: 0 if aliases.
- **Sprint:** 2.

### Finding 6 — `/auth/register` route does not exist

- **Affected endpoints (1):** `POST /auth/register`.
- **Severity:** 🔴 Major (spec lists it as P0).
- **Recommended action:** **Defer / Mobile adapts.** The spec at
  Phase 1 mandates a register endpoint, but per the Sprint-0 BLOCKERS
  resolution and the OTP-only flow described elsewhere in the spec
  itself, mobile may be able to onboard a new user via
  `/auth/otp/verify` returning `requires_registration: true` plus a
  follow-up `auth/otp/verify` call carrying profile data. Sprint 3
  (Auth flows hardening) decides whether to wire `/auth/register` or
  formalize the OTP-only path; both arms of the spec must agree.
- **Effort:** Backend: depends on decision. Mobile: 0–½ day to update
  registration UI flow.
- **Sprint:** 3.

### Finding 7 — Chat endpoints don't exist

- **Affected endpoints (4):** `GET /conversations`,
  `GET /conversations/{id}`, `GET /conversations/{id}/messages`,
  `GET /chat/unread-summary`.
- **Severity:** 🔴 Major. Per the spec's coverage table the entire
  Phase 10 chat suite is a gap. The 4 we tested return Laravel's
  default 404 wrapped in some other shape (no envelope).
- **Recommended action:** **Defer.** Sprint 7 owns the full chat /
  Pusher implementation.
- **Effort:** Sprint 7 budget.
- **Sprint:** 7.

## Roll-up by category

| Action | Findings | Endpoints affected |
|---|---|---|
| Backend changes (Sprint 2) | 1, 2, 3, 5 | ~32 |
| Backend bug-fix | 4 | 2 |
| Defer to Sprint 3 (auth) | 6 | 1 |
| Defer to Sprint 7 (chat) | 7 | 4 |
| **Total** | **7** | **~39 distinct findings across 37 🔴 tests** |

---

## Sprint 2 status (2026-05-05)

- **Finding 1 — Resource collections bypass envelope.** **Resolved in
  Sprint 2.** ApiResponse::paginated() and Response::paginatedEnvelope
  macro added; 6+ endpoints migrated.
- **Finding 2 — `message` key omitted on bespoke responses.**
  **Resolved in Sprint 2.** Bypassing controllers migrated to
  `$this->success(...)`; the strengthened trait emits `message: null`
  by default.
- **Finding 3 — `data` key omitted on no-content responses.**
  **Resolved in Sprint 2.** `noContent()` helper added; bootstrap
  exception handler and `EnsureJsonErrorShape` middleware updated to
  emit `data: null` on every error envelope.
- **Finding 4 — Two real 500s.** **Resolved in Sprint 2.**
  `/auth/logout` (Sanctum TransientToken) and `/venues/nearby`
  (driver-aware bounding-box) both fixed with regression tests.
- **Finding 5 — Geography path naming.** **Resolved in Sprint 2.**
  Canonical `/cities`, `/cities/{id}`, `/cities/{id}/neighborhoods`,
  `/venues/clusters` registered; `/api/v1/geography/...` aliases
  retained for one sprint.
- **Finding 6 — `/auth/register` route missing.** **Resolved in
  Sprint 4 Phase 0.** Spec corrected: there is no separate
  registration endpoint by design. New-user profile completion
  uses `PUT /profile` after OTP verification. Backend now exposes
  an `is_new_user` boolean at `data.is_new_user` on
  `POST /auth/otp/verify` and `POST /auth/google` so mobile can
  branch correctly. The orphan `test_post_auth_register_*` test
  was removed; two new tests assert the `is_new_user` flag for
  new vs. existing users. Spec now also documents the `channel`
  parameter on `POST /auth/otp/send`.
- **Finding 7 — Chat endpoints don't exist.** **Deferred to Sprint
  7.** Until then the canonical 404 envelope applies (verified by
  the Phase 10 probe tests).

---

## Sprint 3 status (2026-05-05)

- **Wallet-related findings (subset of Finding 1, 2, 3):**
  **Resolved.** The four spec-named wallet endpoints (account,
  transactions, get/put settings) now return the spec's data shape
  via `WalletAccountResource` and `WalletTransactionResource`.
  `POST /wallet/pay-booking` is new this sprint — atomic + idempotent
  with 13 tests covering balance, ownership, double-charge,
  amount mismatch, locked balance, and DB transaction integrity.
- **New patterns surfaced for future sprints:** the
  `App\Support\Idempotency` primitive should be reused for any
  team-mutating operation (kick / transfer-captain) and any
  payment retry. Documented in the Sprint 4 recommendations
  section of the Sprint 3 completion report.
