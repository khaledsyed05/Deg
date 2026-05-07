# Sprint 3 — Wallet Discovery

Pre-implementation audit committed before any code changes (per the
Sprint 3 prompt's Phase A6).

## Wallet model (`app/Models/Wallet.php`)

- **Columns** (per `2024_01_01_000018_create_wallets_table.php` +
  `2026_04_24_180001_extend_wallets_for_credits_phase10.php`):
  - `id`, `user_id` (unique → users.id), `balance` (int, default 0,
    DB CHECK >=0 on MySQL), `currency` (varchar(3), default 'SYP'),
    `locked` (uint64, default 0), `total_earned`, `total_spent`,
    `total_topup` (uint64, default 0), `created_at`, `updated_at`.
- **Casts:** all amount-style fields are integers (SYP minor units —
  SYP has no fractional unit so the integer is the whole amount).
- **Relationships:** `belongsTo(User)`, `hasMany(WalletTransaction)`.
- **Custom methods:** `forUser($user)` (firstOrCreate),
  `getAvailableAttribute()` (= `balance − locked`),
  `credit(amount, CreditType, ...)` and `debit(...)` (each runs in
  `DB::transaction`, throws RuntimeException on insufficient balance,
  returns the created `WalletTransaction`).
  `lock(amount)` and `unlock(amount)` mutate `locked`.
- **Spec-mandated columns missing:** none mandatory.
  `BACKEND_REQUIREMENTS.md` Wallet entity (Appendix A) lists
  `auto_topup_enabled`, `auto_topup_threshold`, `auto_topup_amount`,
  `low_balance_alert` — these are **settings**, not wallet-row
  state, so they belong on a separate `wallet_settings` table or
  inline JSON column. We'll add a `settings` JSON column on the
  `wallets` table in Phase B1.
- **Naming divergence:** spec Wallet uses `locked_amount`
  (snake_case full word). Codebase uses `locked`. Decision: keep
  the database column as `locked` but expose `locked_amount` to
  mobile via the `WalletAccountResource` we'll write in Phase B2.

## WalletTransaction (`app/Models/WalletTransaction.php`)

- **Columns** (per `2024_01_01_000019_create_wallet_transactions_table.php`
  + `2026_04_24_180002_extend_wallet_transactions_for_credits_phase10.php`):
  - `id`, `wallet_id` (FK → wallets), `type` enum('credit','debit'),
    `credit_type` (varchar(32) — accepts `topup`, `promotional`,
    `referral`, `refund`, `transfer_in`, `transfer_out`, `booking`,
    `withdrawal`, `bonus`, plus `event_registration` from
    `App\Enums\CreditType`), `amount` (uint), `balance_after`
    (signed int), `reason` enum (legacy:
    `booking_refund|booking_payment|admin_adjustment|cancellation_deduction`),
    `reference_type`, `reference_id` (polymorphic),
    `note`, `description`, `metadata` (json), `status` (default
    'completed'), `expires_at`, `processed_at`, `created_at`,
    `created_by` (FK → users, null on delete).
- **No `updated_at`** — by design, append-only ledger.
- **Casts:** amounts as int, json/datetimes as expected.
- **Spec divergences:**
  - Spec lists `idempotency_key` as a transaction column —
    **missing**. Phase B1 will add it as nullable + unique index.
  - Spec lists `payment_method_id` and `booking_id` as direct FKs.
    Codebase uses polymorphic `reference_type` + `reference_id`,
    which already covers booking. We'll keep the polymorphic shape
    and translate it in the resource.

## Endpoint inventory

`routes/api.php` mounts only **the index endpoint at
`GET /api/v1/wallet/`** (line 374) plus the topup flow + transactions
+ transfer + redeem + expiring + withdraw. **There is no
`/wallet/account`, no `/wallet/settings`, no `/wallet/pay-booking`
route.** Sprint 1's MobileEnvelope test for `/wallet/account` was
green only because the canonical 404 envelope wraps unrouted paths
(Sprint 2 work).

| Endpoint (spec) | Currently routed at | Action (Phase) |
|---|---|---|
| `GET /wallet/account` | absent — `WalletController@index` lives at `GET /wallet/` and returns the right shape | Add canonical alias `Route::get('account', …@index)` (B2) |
| `GET /wallet/transactions` | exists; uses `$this->paginated()` from Sprint 2 | Strengthen test, audit resource shape (B2) |
| `GET /wallet/settings` | absent | Add controller method + route (C1) |
| `PUT /wallet/settings` | absent | Add controller method + route (C1) |
| `POST /wallet/pay-booking` | absent | Build (B4) |

### Existing endpoint return shapes (from controller code)

- `WalletController@index` → `{balance, locked, available,
  total_earned, total_spent, total_topup, currency}`. Spec wants
  `available_balance` (not `available`) and `locked_amount` (not
  `locked`). The `auto_topup` block is also missing. Phase B2 will
  add a `WalletAccountResource` that re-keys the existing model.
- `WalletController@transactions` → wraps a paginator via
  `$this->paginated()` with no resource class (raw Eloquent
  records). Phase B2 will add a `WalletTransactionResource` that
  emits the spec's documented shape.

## Booking ↔ Wallet integration

- **Booking states** (`App\Enums\BookingStatus`):
  `pending_payment | confirmed | scheduled | checked_in |
  cancelled | completed | no_show | failed`. `pay-booking` accepts
  bookings in `pending_payment`; transitions to `confirmed`.
- **`bookings` table** has no `wallet_transaction_id` column today.
  Phase B1 adds one (nullable FK → wallet_transactions, null on
  delete).
- **`BookingController@store`** does not branch on payment_method
  for wallet today; the wallet leg will live in the new
  `PayBookingService` (Phase B4) and is invoked by mobile
  *after* booking creation, so the `store` endpoint is unchanged.
- **Existing services to reuse:** `App\Services\Wallet\WalletService`
  (topup / transfer / withdraw), but `pay-booking` is novel enough
  to warrant its own `PayBookingService` rather than expanding the
  already-large WalletService.

## Idempotency infrastructure

- No reusable primitive exists. `WalletService::confirmTopup` has a
  one-off check (`if PaymentStatus::Completed → return early`) but
  it's not generic.
- `Cache::lock()` is available; we'll use it in `app/Support/Idempotency.php`
  (Phase B3) keyed on the user_id + idempotency_key.
- The unique index on `wallet_transactions.idempotency_key` (added
  in Phase B1) is the durable backstop — the cache lock prevents
  concurrent attempts; the unique index prevents duplicates even
  across cache eviction.

## Audit logging

- The codebase uses `spatie/laravel-activitylog` (`activity()->log(...)`),
  with the trait already wired into Booking (`logOnly([...])`).
  Phase B7 will use `activity()->causedBy($user)->performedOn($booking)
  ->withProperties([...])->event('wallet.pay_booking')->log(...)` —
  no new table/model required.

## Decisions taken at discovery time

1. **Add `account` and `settings` routes**, not rename `index` →
   `account`. The existing `GET /wallet/` route is consumed by the
   admin dashboard; renaming would break it. The new canonical
   alias is cheap.
2. **Keep DB column names (`locked`, `balance`); rename only at the
   resource boundary.** Mobile reads through resources; mobile
   never sees DB column names directly.
3. **Use polymorphic reference, not a dedicated `booking_id` FK on
   wallet_transactions.** The polymorphic morph already handles
   booking refs and isn't worth disrupting.
4. **Auto-topup execution job is scaffolded only, not implemented.**
   Sprint 8 (Hardening) owns the actual scheduler wiring.
5. **Custom exceptions extend `RuntimeException`** with explicit
   status codes, rendered through the existing
   `bootstrap/app.php` envelope handler — the canonical 422/409
   shape comes for free.

## What Phase B1 will add

- `wallets.settings` JSON column (auto_topup config).
- `bookings.wallet_transaction_id` nullable FK.
- `wallet_transactions.idempotency_key` nullable + unique index.
- Factories updated.

## What is **not** in scope this sprint

- Auto-topup execution (deferred to Sprint 8).
- `wallet/transfer` / `wallet/withdraw` / `wallet/redeem` endpoint
  re-verification beyond what already passes — the spec doesn't
  require them.
- Renaming the database column `locked` to `locked_amount` (would
  break dashboards and migrations history).
