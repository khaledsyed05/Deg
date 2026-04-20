# Sprint Plan

---

## Sprint 1 — Project Bootstrap

**Phase:** 1
**Objective:** Runnable Laravel 13 monorepo. All packages installed. All migrations created. Geography seeded. Health check green. CI passes.

**Included tasks:**
- T-001: Docker Compose dev environment
- T-002: Laravel 13 install + package installation
- T-003: All migrations (in correct order)
- T-004: Geography seeder (dr5hn)
- T-005: App Startup endpoint
- T-006: CI pipeline setup

**Excluded:**
- Any authentication
- Any domain logic
- Any API endpoints beyond startup

**Risks:**
- dr5hn SQL dump import takes too long → solution: chunk-insert via JSON instead
- Package version conflicts on Laravel 13 → pin versions in composer.json upfront

**Exit criteria:**
- `php artisan migrate:fresh --seed` completes < 3 min
- `GET /api/v1/app/startup` returns 200
- All service providers boot without error
- CI runs and passes on push

---

## Sprint 2A — Player Authentication

**Phase:** 2
**Objective:** Players can authenticate via OTP (SMS + WhatsApp) and Google Sign-In. Tokens issued. Logout clears FCM token.

**Included tasks:**
- T-007: OTP request endpoint (Syriatel + MTN + Baileys auto-detect)
- T-008: OTP verify + token issuance
- T-009: OTP resend with cooldown
- T-010: Google Sign-In via JWKS
- T-011: Account linking (Google + existing phone)
- T-012: Phone add flow (Google-only users)
- T-013: Logout endpoint (token delete + FCM clear)
- T-014: Rate limiting on OTP endpoints

**Excluded:**
- Dashboard auth (Sprint 2B)
- Any domain beyond auth

**Risks:**
- Baileys WhatsApp session not available in dev → always fallback to SMS in tests
- JWKS fetch could fail in test environment → mock HTTP client

**Exit criteria:**
- OTP flow: request → verify → Sanctum token issued
- OTP after 5 failed attempts: `OTP_LOCKED` returned, further attempts rejected
- Google Sign-In with valid mock JWT: user created, token issued
- Google Sign-In with phone matching existing OTP user: `link_required` returned
- Logout: `users.fcm_token = NULL` confirmed in DB
- OTP code never stored in plain text — only SHA-256 hash in `otp_challenges`

---

## Sprint 2B — Dashboard Auth & Permission System

**Phase:** 2
**Objective:** Dashboard users authenticate via email + password. Super Admin requires TOTP. All routes gated by Spatie permissions. Club scoping enforced.

**Included tasks:**
- T-015: Dashboard login (web guard, session)
- T-016: Custom TotpService (RFC 6238, PHP native)
- T-017: Super Admin 2FA setup and enforcement middleware
- T-018: Spatie roles + permissions seed
- T-019: Club scoping middleware (`EnsureUserBelongsToClub`)
- T-020: Dashboard logout (session destroy + FCM clear)

**Excluded:**
- Any Inertia dashboard pages (Phase 6)
- Player auth routes

**Risks:**
- TOTP clock skew in CI → use ±1 window and freeze time in tests

**Exit criteria:**
- Super Admin login without TOTP: redirected to 2FA page, not admitted
- Super Admin with valid TOTP: session flag set, admin routes accessible
- Club Admin accessing another club's endpoint: 403
- Route without `permission:` middleware: test catches it in coverage pass
- All seeded roles have expected permission sets

---

## Sprint 3A — Geography, Categories & System Config

**Phase:** 3
**Objective:** Admin can activate geographic regions. Category and content data is manageable. App startup is dynamic.

**Included tasks:**
- T-021: Country/State/City activation (Admin API)
- T-022: VenueCategories CRUD (Admin)
- T-023: SportCategories CRUD (Admin — Events tab only, no venue relation)
- T-024: ContentPages CRUD (Admin)
- T-025: AppPlatforms + AppEnvironments management (Admin)
- T-026: Mobile geography read endpoints (active only)

**Excluded:**
- Clubs and venues (Sprint 3B)
- Commission (Phase 5)

**Risks:**
- `name_ar` field for geography is manual — Admin may leave it blank; mobile must handle null gracefully

**Exit criteria:**
- Admin activates Syria: `GET /api/v1/countries` returns Syria
- Admin activates Damascus state: `GET /api/v1/states?country_iso2=SY` returns Damascus
- Admin deactivates a city: mobile endpoint stops returning it
- Startup endpoint reads platform config from DB, not hardcode
- VenueCategory with `type=sports` vs `type=hall` returned correctly in list

---

## Sprint 3B — Clubs, Venues & Pricing

**Phase:** 3
**Objective:** Clubs go through approval lifecycle. Venues are configurable with pricing tiers. Mobile can browse. Media uploads work.

**Included tasks:**
- T-027: Club CRUD + approval/reject (Admin)
- T-028: Club profile edit (Club Admin, scoped)
- T-029: Venue CRUD within club (Club Admin + Data Entry)
- T-030: Opening hours management (spatie/opening-hours)
- T-031: VenuePricingTier CRUD with Syria calendar logic
- T-032: Media uploads — clubs + venues (spatie/medialibrary)
- T-033: Mobile club listing endpoint (filter, sort, search)
- T-034: Mobile club detail + venues list
- T-035: Mobile venue detail

**Excluded:**
- Slot availability (Phase 4)
- Reviews (Phase 6)

**Risks:**
- Haversine distance sort on large dataset → index `(latitude, longitude)` and test performance
- Opening hours JSON edge cases (overnight hours, closed days)

**Exit criteria:**
- Club at `pending_approval` not returned in mobile listing
- Club approved by Admin: `status=active`, appears in mobile listing filtered by city
- Venue pricing tier: Friday slot at 16:00 matches `day_type=friday` tier correctly
- Weekday (Sunday) slot does not match `day_type=weekend` tier
- Media upload: image stored, URL returned in API response
- `GET /api/v1/clubs?category_id=X&city_id=Y` returns only active clubs in that city with that category

---

## Sprint 4A — Slot Availability Engine

**Phase:** 4
**Objective:** Slots computed correctly from opening hours and pricing tiers. Reservations prevent concurrent booking. Expiry scheduler works.

**Included tasks:**
- T-036: `SlotAvailabilityService` — on-the-fly generation
- T-037: Syria calendar resolution in `TierMatchingService`
- T-038: Slot reservation create (10-min TTL, UNIQUE guard)
- T-039: `reservations:expire` scheduler command
- T-040: Manual bookings API (Club Admin — external + blocked)
- T-041: Manual bookings excluded from slot availability

**Excluded:**
- Real booking creation (requires payment — Phase 5)
- FCM (Phase 6)

**Risks:**
- Concurrent reservation: UNIQUE constraint must be relied upon at DB level, not just app level
- Edge case: slot at venue closing time (e.g., 23:00 slot if closing is 23:00) — must be excluded

**Exit criteria:**
- Venue open 08:00–22:00, duration 60 min: 14 slots returned for a weekday
- A booked slot (status=confirmed) does not appear in availability
- A manually blocked slot does not appear in availability
- An active reservation (reserved_until > NOW()) does not appear in availability
- Expired reservation (reserved_until < NOW()): slot reappears in availability
- Concurrent reservation test: hammer same slot with 10 simultaneous requests → exactly 1 succeeds
- Slot at or past midnight edge case handled without error

---

## Sprint 4B — Booking Lifecycle

**Phase:** 4
**Objective:** Bookings created correctly. Cancellation works with refund stub. Schedulers mark bookings complete. Club marks no-show.

**Included tasks:**
- T-042: Booking creation transaction (payment stub)
- T-043: Booking code generation (DQ + padded number)
- T-044: Cancellation check endpoint
- T-045: Cancellation execution (refund stub → wallet placeholder)
- T-046: `bookings:complete` scheduler command
- T-047: `bookings:remind` scheduler command (FCM stub)
- T-048: No-show: Club Admin marks manually
- T-049: Player booking history (upcoming + completed + cancelled)
- T-050: Saved venues CRUD
- T-051: `otp:cleanup` scheduler command

**Excluded:**
- Real payment (Phase 5)
- Real FCM (Phase 6)
- Deposit logic (Phase 5)

**Risks:**
- Booking code uniqueness under load → use DB UNIQUE + retry on collision
- `bookings:complete` running every 15 min: must not mark bookings that end in the future

**Exit criteria:**
- Booking created with correct `venue_price`, `commission_amount`, `total_price`, `club_payout_amount` snapshots
- Booking code is unique and matches format `DQ\d{6}`
- Cancellation within 30-min window: returns `CANCELLATION_NOT_ALLOWED`, no DB change
- Cancellation outside window: `status=cancelled`, wallet transaction created
- Full booking cancellation: refund = `total_price - cancellation_deduction`
- Deposit booking cancellation: refund = `deposit_amount - cancellation_deduction` (remaining untouched)
- Scheduler: booking with `ends_at < NOW()` and `status=confirmed` → marked `completed`
- Scheduler: booking 2h away → reminder job dispatched to queue (verified in `failed_jobs` not present, job exists in queue)
- No-show: Club Admin can mark a completed booking as `no_show` (only terminal states eligible)

---

## Sprint 5A — MTN Cash & Syriatel Cash

**Phase:** 5
**Objective:** Both OTP payment providers integrated end-to-end. Booking created on success. Reservation released on failure.

**Included tasks:**
- T-052: `PaymentGateway` interface + gateway registry
- T-053: `MtnCashGateway` — createInvoice, initiatePayment, confirmPayment
- T-054: `SyriatelCashGateway` — paymentRequest, paymentConfirmation, resendOTP
- T-055: Payment initiate endpoint (creates reservation + payment record)
- T-056: Payment confirm endpoint (OTP providers)
- T-057: Payment resend-OTP endpoint
- T-058: Payment success transaction (booking creation + reservation delete)
- T-059: Payment failure handling (reservation released after TTL)

**Excluded:**
- Fatora/SamaPay (Sprint 5B)
- Wallet (Sprint 5B)
- Deposit logic (Sprint 5C)

**Risks:**
- MTN SSL `verify: false` — must be in HTTP client config per gateway, not global
- Syriatel token cache 3 minutes — must use file cache, test cache key collision

**Exit criteria:**
- MTN: initiate returns `{payment_id, flow_type: otp, guid, operation_number}`
- MTN: confirm with correct OTP → booking created, reservation deleted, payment `completed`
- MTN: confirm with wrong OTP → payment `failed`, reservation kept until TTL
- Syriatel: initiate → confirm flow creates booking
- `provider_meta` stored encrypted in DB
- Payment initiate when completed payment already exists for booking: rejected
- Reservation expires: slot becomes available again (verified via availability endpoint)

---

## Sprint 5B — Fatora / SamaPay + Wallet

**Phase:** 5
**Objective:** WebView payment providers integrated with idempotent callback. Wallet operational with race-condition protection.

**Included tasks:**
- T-060: `FatoraGateway` — payload build + hosted URL
- T-061: `SamaPayGateway` — same as Fatora, different config
- T-062: Fatora/SamaPay callback endpoint (public, idempotent)
- T-063: Payment status polling endpoint
- T-064: `WalletService` — debit with `SELECT FOR UPDATE`
- T-065: Wallet payment flow (internal, instant)
- T-066: Wallet refund on cancellation (full payment scenario)
- T-067: Wallet view + transactions endpoint (player)

**Excluded:**
- Deposit wallet refund (Sprint 5C)
- Settlement (Sprint 5C)

**Risks:**
- BLOCKED-001: Fatora callback signature unknown → interim: re-query Fatora on receipt
- Concurrent wallet debits → must test with parallel requests

**Exit criteria:**
- Fatora: initiate returns `{payment_id, flow_type: webview, hosted_url}`
- Fatora callback replayed twice: second call returns OK without creating duplicate booking
- Wallet debit: balance decreases by exact amount
- Concurrent wallet debit: 2 requests for same user, balance = 100, each requests 70 → exactly one succeeds, one gets `INSUFFICIENT_WALLET_BALANCE`
- Wallet refund: credit transaction created, balance increases
- Wallet transactions endpoint: returns paginated list in reverse chronological order

---

## Sprint 5C — Commission, Deposit & Settlement

**Phase:** 5
**Objective:** Commission calculated and snapshotted correctly. Deposit model works. Settlement system operational.

**Included tasks:**
- T-068: `CommissionService::resolveConfig()` + `calculate()`
- T-069: Commission snapshot on booking creation
- T-070: Deposit flow: `payment_mode=deposit` validation + `deposit_amount` stored
- T-071: Commission on `deposit_amount` only for deposit bookings
- T-072: Club confirms remaining cash receipt endpoint
- T-073: Settlement preview endpoint (Admin)
- T-074: Settlement creation transaction (Admin)
- T-075: Settlement status transitions (draft → pending → completed)
- T-076: `wallet:reconcile` artisan command

**Excluded:**
- Per-club or per-venue commission (Phase 2)
- Automated payout

**Risks:**
- Settlement includes bookings from cancelled reservations accidentally → UNIQUE(settlement_id, booking_id) prevents but preview query must also filter correctly

**Exit criteria:**
- Commission 7% on 350,000 SYP: `commission_amount = 24,500`, `club_payout_amount = 325,500`, `total_price = 350,000`
- Commission 7% on deposit of 105,000 SYP (30% of 350,000): `commission_amount = 7,350`
- Deposit booking: `remaining_amount = 245,000`, `remaining_status = due_on_arrival`
- Club confirms receipt: `remaining_status = confirmed`
- Settlement preview: only `confirmed` + `completed` bookings not yet in any `settlement_items`
- Settlement creation: `settlement_items` created for each included booking
- Booking in two settlements: second settlement creation fails (UNIQUE constraint error caught)
- `wallet:reconcile`: reports mismatch if `wallets.balance != SUM(wallet_transactions.amount WHERE type=credit) - SUM(... WHERE type=debit)`

---

## Sprint 6A — Super Admin Inertia Dashboard

**Phase:** 6
**Objective:** Super Admin can manage the full system from a web UI. All admin API routes have corresponding Inertia pages.

**Included tasks:**
- T-077: Inertia + Vue 3 + Tailwind + shadcn-vue scaffold (admin app)
- T-078: Admin login + 2FA pages
- T-079: Geography management pages (activate countries/states/cities)
- T-080: Club management pages (list, detail, approve/reject, edit)
- T-081: Venue management pages
- T-082: Booking management pages (list, detail, cancel, status override)
- T-083: Commission config page
- T-084: Settlement pages (preview, create, status transitions)
- T-085: Revenue report page (gross / commission / net by period)
- T-086: User management page (players + dashboard users)

**Excluded:**
- Behavioral analytics dashboard (Sprint 6C)
- Club Dashboard (Sprint 6B)

**Risks:**
- shadcn-vue component availability for all needed UI patterns → verify table + form + date picker components before starting

**Exit criteria:**
- Admin can log in, complete 2FA, land on dashboard
- Admin can approve a club: status changes to `active`, reflected in mobile API
- Admin can generate a settlement: `settlement_items` created, preview shows correct totals
- Admin can set commission: 7% global stored and resolved on next booking
- Activity log entry created for every admin mutation (verified via activitylog table)

---

## Sprint 6B — Club Dashboard + Real Notifications

**Phase:** 6
**Objective:** Club Dashboard functional for Club Admin. Real FCM and SMS notifications fire for all booking events.

**Included tasks:**
- T-087: Club Dashboard Inertia scaffold (separate app, `/club/*`)
- T-088: Progressive onboarding (3 sections visible by default, rest gated on `onboarding_step`)
- T-089: Club venue management pages
- T-090: Booking calendar view (by date)
- T-091: Manual booking creation UI (external + blocked)
- T-092: Remaining amount confirmation UI
- T-093: Flash Deals creation UI
- T-094: Custom `FcmService` (HTTP v1, no package, service account JSON)
- T-095: `BookingConfirmedNotificationJob` — FCM to player + admin + club admins
- T-096: `BookingCancelledNotificationJob`
- T-097: `VenueAvailableNotificationJob` (checks waitlist first)
- T-098: `BookingReminderJob` (2h + 1h)
- T-099: SMS confirmation via `SmsOtpService` (Syriatel/MTN) for every confirmed booking

**Excluded:**
- Demand features (Sprint 6C)
- Behavioral analytics

**Risks:**
- FCM service account JSON must be in `storage/` not committed → `.gitignore` check
- SMS confirmation fires synchronously or via queue? → queue (job) to avoid payment flow latency

**Exit criteria:**
- Club Admin logs in, sees only 3 sections initially
- Club Admin creates venue: visible in mobile browse immediately
- FCM notification: player receives push after booking confirmation (verified via FCM mock/log)
- SMS: confirmation message dispatched to queue after booking confirmed
- `VenueAvailableNotificationJob`: checks `venue_waitlist` first, sends to waitlisted user if present

---

## Sprint 6C — Demand Features, Events, Reviews & Tracking

**Phase:** 6
**Objective:** Last-Minute Deals live. Waitlist live. Reviews live. Events tab live. Behavioral tracking recording. Admin analytics endpoints responding.

**Included tasks:**
- T-100: Flash Deals API (Club creates, mobile lists active deals)
- T-101: Flash Deal FCM dispatch to interested players
- T-102: `flash-deals:expire` scheduler (every 5 min)
- T-103: Waitlist join endpoint (player)
- T-104: Waitlist FCM on slot opening (from VenueAvailableNotificationJob)
- T-105: `waitlist:cleanup` scheduler (daily)
- T-106: Review creation + eligibility check
- T-107: Review list (public)
- T-108: Review moderation (Admin)
- T-109: `ReviewObserver` → `UpdateClubRatingJob`
- T-110: Competitions CRUD (Admin) + mobile list
- T-111: Football matches proxy endpoint + 5-min file cache
- T-112: `player_events` batch tracking endpoint
- T-113: Admin analytics endpoints (funnel drop-off, D7/D30 retention)
- T-114: `spatie/laravel-backup` scheduled at 2am

**Excluded:**
- Full analytics UI (endpoints only)
- Per-player journey views

**Risks:**
- Football API external dependency → always cache, return stale on failure
- `player_events` table grows fast → index correctly from the start

**Exit criteria:**
- Flash Deal created by Club: appears in `GET /api/v1/flash-deals?area_id=X`
- Flash Deal expired: no longer in active list
- Waitlist: player joins → cancellation of that slot → player receives FCM
- Review: player with no completed booking at club → 422 with clear error
- Review: player with completed booking → review created, `clubs.avg_rating` updated within job execution
- Batch tracking: 20 events in one request → all 20 stored in `player_events`
- Football proxy: response cached, second call within 5 min does not hit external API
- Admin funnel endpoint: returns drop-off counts per funnel step

---

## Sprint 7 — Hardening & Launch Readiness

**Phase:** 7
**Objective:** Test coverage adequate. Security reviewed. Performance acceptable. All blockers resolved or documented. Go/no-go criteria met.

**Included tasks:**
- T-115: Feature test suite — auth flows (OTP, Google, linking)
- T-116: Feature test suite — booking lifecycle (create, cancel, complete)
- T-117: Feature test suite — payment flows (MTN, Syriatel, Fatora, Wallet)
- T-118: Feature test suite — commission + deposit + settlement
- T-119: Unit tests — `CommissionService`, `SlotAvailabilityService`, `TotpService`, `WalletService`
- T-120: Permission coverage test (every route has permission middleware)
- T-121: Rate limiting verification (OTP lockout)
- T-122: Concurrent load test — slot reservation double-booking
- T-123: Scheduler commands unit tests (dry-run assertions)
- T-124: BLOCKED-001 resolution (Fatora callback security)
- T-125: `.env.production.example` + deployment checklist
- T-126: Health check endpoint with DB + queue status

**Exit criteria (go/no-go gate):**
- All feature tests green (`php artisan test --parallel`)
- Zero routes without `permission:` middleware (verified by route coverage test)
- OTP endpoint: 5 wrong attempts → `OTP_LOCKED`, 6th attempt rejected
- Concurrent slot reservation: 20 parallel requests for same slot → exactly 1 booking created
- `wallet:reconcile` command: on clean DB returns "no mismatches"
- `GET /api/v1/health` returns `{"db": "ok", "queue": "ok"}` 200
- `php artisan migrate:fresh --seed` < 3 minutes
- No `dd()`, `dump()`, `var_dump()` in codebase (`grep` check passes)
- `composer audit` returns no high-severity vulnerabilities
