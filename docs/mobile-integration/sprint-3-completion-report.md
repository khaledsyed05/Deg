# Sprint 3 — Completion Report (Wallet API)

**Date completed:** 2026-05-05
**Branch:** feature/mobile-integration-sprint-3
**Total commits:** 10 (this report makes the 10th)

## ✅ Workstream A: Existing Endpoint Verification

- 4 endpoints (`GET /wallet/account`, `GET /wallet/transactions`,
  `GET /wallet/settings`, `PUT /wallet/settings`) verified at
  data-shape level via `assertJsonStructure` + `assertJson`.
- Divergences fixed by introducing two resources rather than
  renaming DB columns (preserves dashboard compatibility):
  - `WalletAccountResource` re-keys `locked → locked_amount` and
    `available → available_balance`, plus emits the documented
    `auto_topup` block sourced from `wallets.settings`.
  - `WalletTransactionResource` emits the spec's per-row shape
    (id, type, credit_type, amount, balance_after, reason,
    description, status, booking_id, idempotency_key,
    processed_at, created_at).
- `GET /wallet/account` and `GET/PUT /wallet/settings` were
  unrouted at sprint start (Sprint 1 envelope-only ✅ came from
  Sprint 2's canonical 404 wrap). New routes added.

## ✅ Workstream B: pay-booking Build

- Endpoint live at `POST /api/v1/wallet/pay-booking`.
- `App\Services\Wallet\PayBookingService::execute(...)` orchestrates
  the flow: `Idempotency::run($key, fn() => DB::transaction(...))`
  with `lockForUpdate()` on the wallet row.
- Idempotency primitive at `App\Support\Idempotency.php`
  (`Cache::lock` + `wallet_transactions.idempotency_key` unique
  index, both layered).
- 13 tests in `tests/Feature/Wallet/PayBookingTest.php`, all
  passing:
  - happy path
  - insufficient balance (422)
  - already-confirmed booking (409)
  - booking owned by another user (403)
  - nonexistent booking_id (422 validation)
  - idempotent retry (same row, debit once)
  - concurrent callers (one transaction)
  - locked balance excluded from available
  - amount mismatch (409)
  - user with no wallet (auto-creates, errors as insufficient)
  - DB transaction rollback integrity
  - audit-log row asserted
  - typed-exception contract
- Custom exceptions in `app/Exceptions/Wallet/` extend Symfony
  `HttpException` so the bootstrap envelope handler renders them
  as 422 / 409 without an extra clause.
- Audit logging: implemented inline via Spatie's
  `activity()->log('wallet.pay_booking')`. No deferral.

## ⚠️ Tasks Deferred

None at sprint scope.

- Auto-topup *execution* is the only deliberately-deferred item;
  configuration endpoints + storage + job stub are all in place.
  Sprint 8 wires the schedule entry.

## 🔴 Blockers Encountered

None new. The Sprint-1 BLOCKERS entry about the `/auth/register`
route is reaffirmed — Sprint 3 confirms the OTP-only flow per the
existing codebase, and the spec/code reconciliation is left for a
later auth-focused sprint.

## 📊 Test Suite Health

| Stage | Total | Passing | Failing |
|---|---|---|---|
| Inherited from Sprint 2 | 388 | 388 | 0 |
| After Sprint 3 (B1 migrations) | 388 | 388 | 0 |
| After Sprint 3 (B2 endpoints + resources) | 391 | 391 | 0 |
| After Sprint 3 (B3 idempotency) | 394 | 394 | 0 |
| After Sprint 3 (B6 pay-booking tests) | 404 | 404 | 0 |

Net: **+16 tests**, all passing. 0 failing throughout the
sprint.

## 📊 Phase 8 Verification (Wallet)

- ✅ Match (data-shape verified): **6 / 6** (was 5 envelope-only).
- New endpoint: `POST /wallet/pay-booking` (✅ end-to-end with 13
  dedicated tests).
- Headline integration verification count: **74 / 74** ✅ across
  all phases (was 73 / 73 after Sprint 2).

## 📁 Files Created

**Migrations:**
- `database/migrations/2026_05_05_174320_add_settings_to_wallets_table.php`
- `database/migrations/2026_05_05_174321_add_idempotency_key_to_wallet_transactions_table.php`
- `database/migrations/2026_05_05_174321_add_wallet_transaction_id_to_bookings_table.php`

**Resources / Requests / Services / Exceptions:**
- `app/Http/Resources/Wallet/WalletAccountResource.php`
- `app/Http/Resources/Wallet/WalletTransactionResource.php`
- `app/Http/Requests/Api/V1/Wallet/UpdateWalletSettingsRequest.php`
- `app/Http/Requests/Api/V1/Wallet/PayBookingRequest.php`
- `app/Services/Wallet/PayBookingService.php`
- `app/Support/Idempotency.php`
- `app/Exceptions/Wallet/InsufficientBalanceException.php`
- `app/Exceptions/Wallet/BookingNotPayableException.php`
- `app/Jobs/Wallet/CheckAutoTopupJob.php` (stub)

**Lang:**
- `lang/ar/wallet.php`
- `lang/en/wallet.php`

**Tests:**
- `tests/Unit/IdempotencyTest.php`
- `tests/Feature/Wallet/PayBookingTest.php`

**Docs:**
- `docs/mobile-integration/sprint-3-discovery.md`
- `docs/mobile-integration/sprint-3-completion-report.md` (this
  file)

## 📝 Files Modified

**Models / Factories:**
- `app/Models/Wallet.php` — added `settings` cast.
- `app/Models/WalletTransaction.php` — added `booking()` relation.
- `app/Models/Booking.php` — added `walletTransaction()` relation.
- `database/factories/BookingFactory.php` — added `pendingPayment()`
  state.

**Controller / routes:**
- `app/Http/Controllers/Api/V1/WalletController.php` — added
  `account`, `getSettings`, `updateSettings`, `payBooking` methods;
  `transactions` now uses `WalletTransactionResource`.
- `routes/api.php` — added `wallet/account`, `wallet/settings`
  (GET/PUT), `wallet/pay-booking`.

**Docs:**
- `docs/mobile-integration/CHANGELOG.md` — Sprint-3 summary entry.
- `docs/mobile-integration/verification-results.md` — Phase 8
  refreshed; counts 73 → 74.
- `docs/mobile-integration/decision-matrix.md` — wallet-related
  Findings marked Resolved in Sprint 3.
- `docs/mobile-integration/api-paths-canonical.md` — pay-booking
  added.

## 🎯 Recommendations for Sprint 4 (Teams Management)

1. **Reuse the Idempotency primitive.** Any team-mutating
   operation (`POST /teams/{id}/kick`,
   `POST /teams/{id}/transfer-captain`,
   `POST /teams/{id}/invite`) should accept an
   `idempotency_key` param and wrap the operation in
   `Idempotency::run(...)`. Mobile users on flaky networks retry;
   the primitive ensures one-and-only-one effect.
2. **Adopt the data-shape contract from day one.** Sprint 3's
   strengthened `assertJsonStructure` pattern is the bar — Sprint 4's
   Team tests should use `assertJsonStructure(['data' => [<spec keys>]])`
   from their first commit, not added later.
3. **Resource classes over inline arrays.** Every team-related
   GET should ship with a dedicated `App\Http\Resources\Team\*Resource`,
   per the Sprint 3 wallet pattern. It keeps the spec-canonical
   field names independent of DB columns.
4. **Custom HttpException subclasses for predictable errors.**
   Sprint 3's `InsufficientBalanceException` (422) and
   `BookingNotPayableException` (409) are the template. Sprint 4
   will likely need `TeamFullException`, `NotTeamCaptainException`,
   etc. — make them extend Symfony's `HttpException` so the
   bootstrap handler wraps them automatically.
5. **Audit-log every team mutation.** Spatie activitylog is
   already wired; one `activity()->log('team.kick')` line per
   destructive operation gives auditability for free.

## 🔥 Lessons from Sprint 3

1. **Discovery before code is worth it even when it feels
   redundant.** The `sprint-3-discovery.md` commit caught two
   route-existence assumptions (account, settings) that would
   otherwise have led to "test passes but spec diverges" surprises.
   30 minutes spent on discovery saved at least an hour of
   confusion in B2.
2. **Resources beat column renames.** Adding
   `WalletAccountResource` to re-key fields (`locked → locked_amount`)
   was strictly cheaper and safer than a column rename. Future
   sprints should default to this pattern whenever DB-vs-spec
   names diverge.
3. **`Idempotency::run` is small and worth the abstraction.**
   ~50 lines including the cache lock + DB lookup. Reusable for
   every wallet write and every retryable mutation in the
   upcoming sprints. The "build once, document once, reuse forever"
   case in action.
