# Acceptance Criteria

> All criteria are explicit and checkable. No vague language.

---

## AC-AUTH-01 — OTP Authentication

- `POST /api/v1/auth/otp/request {phone_number: "0991234567"}` → HTTP 200
- Response contains `verification_request_id` (UUID format), `expires_in_seconds` (integer), `resend_after_seconds` (integer)
- `otp_challenges` record created: `code_hash = hash('sha256', actual_code)` — NOT the code itself
- 4th request from same phone within 10 minutes → HTTP 429
- `GET /api/v1/auth/otp/request` (wrong method) → HTTP 405

---

## AC-AUTH-02 — OTP Verification

- Correct OTP submitted → HTTP 200, `access_token` present, `token_type: bearer`
- Wrong OTP → HTTP 422, `error.code = OTP_INVALID`, `otp_challenges.attempts_count` incremented by 1
- Expired challenge (past `expires_at`) → HTTP 422, `error.code = OTP_EXPIRED`
- 5th wrong attempt → HTTP 422, `error.code = OTP_MAX_ATTEMPTS`
- 6th attempt (after lockout) with correct code → HTTP 422, `error.code = OTP_LOCKED`
- New user: response `auth_outcome = registration`
- Existing user: response `auth_outcome = login`

---

## AC-AUTH-03 — Google Sign-In

- Valid Firebase token (mock) → HTTP 200, `access_token` returned, `social_identities` row created
- Firebase token with phone matching existing OTP user → HTTP 200, `{action: link_required, masked_phone}`
- Expired Firebase token → HTTP 401, `error.code = INVALID_FIREBASE_TOKEN`
- Tampered Firebase token signature → HTTP 401
- JWKS endpoint called maximum once per hour (cache verified by call count)

---

## AC-AUTH-04 — Dashboard Authentication

- Valid Super Admin email+password → session created, redirect to `/admin/auth/2fa`
- Without TOTP completion → `GET /admin/clubs` redirects to `/admin/auth/2fa`
- Valid TOTP code → session flag `2fa_verified = true`, `GET /admin/clubs` → 200
- Wrong TOTP code → stays on 2FA page, no session flag set
- Club Admin email+password → session created, redirect to `/club/dashboard`, no 2FA prompt
- Logout → session destroyed, `users.fcm_token = NULL`

---

## AC-AUTH-05 — Role Enforcement

- `club_admin` accessing `/api/admin/v1/clubs` → HTTP 403
- `club_admin` accessing their assigned club's endpoint → HTTP 200
- `club_admin` accessing a club NOT in their `club_user` pivot → HTTP 403
- `super_admin` accessing any club endpoint → HTTP 200 (no pivot restriction)
- Route without `permission:` middleware: fails permission coverage test

---

## AC-GEO-01 — Geography Activation

- Admin toggles Syria `is_active` to 1 → `GET /api/v1/countries` returns Syria
- Admin toggles Syria `is_active` to 0 → Syria absent from `/api/v1/countries`
- `GET /api/v1/states?country_iso2=SY` returns only Syrian states with `is_active = 1`
- `GET /api/v1/cities?state_id=X` returns only cities in that state with `is_active = 1`
- Syria `phone_code = +963` present in response

---

## AC-CATALOG-01 — Club Lifecycle

- Club at `pending_approval` → absent from `GET /api/v1/clubs`
- Club approved → `GET /api/v1/clubs?city_id=X` returns it (if in that city)
- Club suspended → absent from `GET /api/v1/clubs`
- Approval: `clubs.approved_by = auth()->id()`, `clubs.approved_at` is set
- Rejection with `rejection_reason`: `clubs.status = rejected`, `rejection_reason` stored
- Activity log: one record created per approval/rejection action

---

## AC-CATALOG-02 — Venue Pricing

- Venue open 08:00–22:00, duration 60: `GET .../slots?date=2026-05-09&duration=60` returns 14 slots
- Each slot: `{start_time, end_time, price, duration_minutes}` — all fields present
- Friday (2026-05-08): slots use `day_type=friday` tier price, not `weekday` or `weekend` tier
- Saturday (2026-05-09): slots use `day_type=weekend` tier
- Sunday (2026-05-10): slots use `day_type=weekday` tier
- Request for duration=90 when no 90-min tier exists: 0 slots returned (not an error)

---

## AC-BOOKING-01 — Slot Reservation

- `POST /api/v1/payments/initiate` for available slot → `slot_reservations` record created, `reserved_until = NOW() + 10 min`
- Slot availability check during reservation window: slot absent from response
- Concurrent: 10 simultaneous initiate requests for same slot → exactly 1 succeeds, 9 return `SLOT_UNAVAILABLE`
- After 10 min expiry (time-faked): `reservations:expire` command → slot reappears in availability

---

## AC-BOOKING-02 — Booking Creation

- Payment success → booking created with `status = confirmed`
- `booking_code` matches regex `^DQ\d{6}$`
- `bookings.venue_price` = original tier price at time of booking
- `bookings.total_price = bookings.venue_price` (always — player pays venue price)
- `bookings.commission_amount = round(venue_price * 0.07)` (at 7%)
- `bookings.club_payout_amount = venue_price - commission_amount`
- `slot_reservations` record deleted after booking creation
- No booking created if payment fails

---

## AC-BOOKING-03 — Deposit Booking

- `payment_mode = deposit, deposit_amount = 105000` (30% of 350,000):
  - `bookings.deposit_amount = 105000`
  - `bookings.deposit_status = paid`
  - `bookings.remaining_amount = 245000`
  - `bookings.remaining_status = due_on_arrival`
  - `bookings.commission_amount = round(105000 * 0.07) = 7350`
  - `bookings.total_price = 350000`
  - `bookings.club_payout_amount = 105000 - 7350 = 97650`
- Club Admin confirms remaining: `PATCH .../remaining/confirm` → `remaining_status = confirmed`, `remaining_confirmed_at` set

---

## AC-BOOKING-04 — Cancellation

- Cancellation within 30 min of `starts_at`: HTTP 422, `error.code = CANCELLATION_NOT_ALLOWED`, zero DB changes
- Cancellation with `{confirmed: true}`:
  - `bookings.status = cancelled`
  - `bookings.cancelled_at` set
  - `bookings.cancelled_by = auth()->id()`
  - Wallet transaction created: `type = credit`, `reason = booking_refund`
  - `wallets.balance` increased by refund amount
  - `payments.status = refunded`
- Full payment cancellation: `refund_amount = total_price - cancellation_deduction`
- Deposit cancellation: `refund_amount = deposit_amount - cancellation_deduction`, `remaining_amount` unchanged
- `VenueAvailableNotificationJob` dispatched after cancellation

---

## AC-BOOKING-05 — Booking Lifecycle Schedulers

- `bookings:complete`: booking with `ends_at < NOW()` and `status = confirmed` → `status = completed`
- `bookings:complete`: booking with `ends_at < NOW()` and `status = cancelled` → unchanged
- `bookings:complete`: booking with `ends_at > NOW()` → unchanged
- `bookings:remind`: booking starting in 2h (±5 min) → `BookingReminderJob` dispatched, `reminder_2h_sent_at` set
- `bookings:remind`: same booking on second scheduler run → no duplicate reminder dispatched

---

## AC-PAYMENT-01 — MTN Cash Flow

- Initiate → `payments` record created `status = pending`, `flow_type = otp`
- `provider_meta` in DB is not readable as plain JSON (encrypted)
- Confirm with correct OTP → booking created, payment `status = completed`
- Confirm with wrong OTP → payment remains `pending` or `failed`, no booking
- SSL `verify: false` used in HTTP client (MTN self-signed cert)

---

## AC-PAYMENT-02 — Fatora/SamaPay Callback Idempotency

- Callback received with valid `transactionReference` → booking created, payment `completed`
- Same callback received again (replay) → HTTP 200 OK returned, zero additional bookings created
- `provider_transaction_id` UNIQUE check is the idempotency guard

---

## AC-PAYMENT-03 — Wallet

- `WalletService::debit($user, 50000)` when balance = 100000 → balance = 50000, transaction created
- `WalletService::debit($user, 150000)` when balance = 100000 → `INSUFFICIENT_WALLET_BALANCE`, balance unchanged
- Concurrent debit: 2 requests, each requesting 70000, balance = 100000 → exactly 1 succeeds
- Wallet balance never goes negative (CHECK constraint + application layer)
- Wallet refund: `wallet_transactions.type = credit`, `wallet_transactions.reason = booking_refund`

---

## AC-PAYMENT-04 — Commission

- `CommissionService::calculate(350000, {type: percentage, value: 700})`:
  - `commission_amount = 24500`
  - `total_price = 350000`
  - `club_payout_amount = 325500`
- `CommissionService::calculate(350000, {type: fixed, value: 15000})`:
  - `commission_amount = 15000`
  - `total_price = 350000`
  - `club_payout_amount = 335000`
- `apply_as` does not exist in `commission_configs` table (verified via `DESCRIBE commission_configs`)

---

## AC-SETTLEMENT-01 — Settlement Creation

- Settlement preview: only `confirmed` + `completed` bookings NOT already in `settlement_items`
- Settlement creation: `settlement_items` created for each included booking
- Attempt to include same booking in two settlements: second settlement creation fails (UNIQUE violation caught, HTTP 422)
- `settlements.net_payable = SUM(club_payout_amount)` for all included bookings
- `settlements.total_commission = SUM(commission_amount)` for all included bookings
- Status transition: only `draft → pending → completed` (not backwards)
- `completed` transition requires `payment_reference` present

---

## AC-NOTIFICATION-01 — FCM

- Booking confirmed: `BookingConfirmedNotificationJob` dispatched to queue
- Job execution: FCM sent to player, to super_admin(s), to all club admins via `club_user` pivot
- User with `fcm_token = NULL`: FCM send skipped (no HTTP call made)
- Booking reminder: dispatched only once per type (2h / 1h) — `reminder_2h_sent_at` prevents duplicate

---

## AC-NOTIFICATION-02 — SMS

- Every confirmed booking: SMS confirmation dispatched to queue
- SMS message sent to `bookings.user.phone_number`
- Booking for user with `notifications_sms_enabled = false`: SMS still sent for confirmations (confirmations are not opt-out)

---

## AC-DEMAND-01 — Last-Minute Deals

- Club creates flash deal: `venue_flash_deals` record created, `status = active`
- `GET /api/v1/flash-deals?area_id=X` returns only `status = active` deals where `expires_at > NOW()`
- `flash-deals:expire` command: deals with `expires_at < NOW()` → `status = expired`
- Expired deal: absent from mobile active deals list
- FCM dispatched to players in same area who booked this venue category before

---

## AC-DEMAND-02 — Waitlist

- Player joins waitlist: `venue_waitlist` record created with `UNIQUE(venue_id, user_id, booking_date, start_time)`
- Same player joins same slot again: 422 (UNIQUE violation)
- Booking cancelled: `VenueAvailableNotificationJob` checks `venue_waitlist` first → FCM to first waiting player
- `waitlist:cleanup`: records with `expires_at < NOW()` deleted

---

## AC-REVIEW-01 — Reviews

- Player with no completed booking at club: `POST /api/v1/clubs/{id}/reviews` → HTTP 422 `INELIGIBLE_TO_REVIEW`
- Player with completed booking: review created, `reviews.booking_id` set
- Second review from same player for same club: HTTP 422 (UNIQUE violation)
- Rating = 6: HTTP 422 validation error
- Body provided with 2 chars: HTTP 200 (no minimum length)
- After review: `UpdateClubRatingJob` dispatched, `clubs.avg_rating` updated after job runs

---

## AC-TRACKING-01 — Behavioral Events

- `POST /api/v1/events/track {events: [...20 events]}` → HTTP 200, 20 records in `player_events`
- `POST /api/v1/events/track {events: [...21 events]}` → HTTP 422 (max 20 per request)
- Event with `user_id = null` (unauthenticated): stored with `anonymous_id` only
- `player_events` table: no UPDATE or DELETE operations performed (append-only)

---

## AC-HEALTH-01 — System Health

- `GET /api/v1/health` → HTTP 200, `{db: "ok", queue: "ok"}`
- DB unreachable: → `{db: "error"}`, HTTP 503
- No `dd()`, `dump()`, `var_dump()` in codebase (grep check)
- `php artisan migrate:fresh --seed` completes in < 3 minutes
- `php artisan test --parallel` exits with code 0 (all tests pass)
- `composer audit` reports no high-severity vulnerabilities
