# Phases Overview

## Phase Map

```
Phase 1 ── Bootstrap & Project Baseline        (1 sprint)
Phase 2 ── Auth, Users & Roles                 (2 sprints)
Phase 3 ── Core Domain & Schema                (2 sprints)
Phase 4 ── Booking & Availability Engine       (2 sprints)
Phase 5 ── Payments, Wallet & Financial Logic  (3 sprints)
Phase 6 ── Dashboards, Notifications & Ops     (3 sprints)
Phase 7 ── Hardening & Launch Readiness        (1 sprint)
```

Total: 7 phases / 14 sprints.

---

## Phase 1 — Bootstrap & Project Baseline

**Goal:** A running Laravel 13 application that compiles, connects to DB, runs migrations, seeds reference data, and passes a health check. Nothing domain-specific yet.

**Sprints:** 1

**Delivers:**
- Docker Compose dev environment (PHP 8.3, MySQL 8, queue worker, scheduler)
- Laravel 13 installed with all locked packages
- All migrations created and running cleanly in order
- dr5hn geography data seeded (countries, states, cities)
- `app/startup` endpoint returning valid response
- CI pipeline running tests on push
- `.env.example` complete

**Does NOT deliver:**
- Any authentication
- Any domain models beyond migrations
- Any API endpoints beyond startup

**Exit gate:**
- `php artisan migrate:fresh --seed` completes without error
- `GET /api/v1/app/startup` returns 200 with valid JSON
- All packages installed and service providers boot without error

---

## Phase 2 — Auth, Users & Roles

**Goal:** Every actor can authenticate. Sanctum tokens for players. Session for dashboard. TOTP for Super Admin. Spatie roles seeded and enforced on all routes.

**Sprints:** 2

**Sprint 2A — Player Auth:**
- OTP request/verify/resend (SMS + WhatsApp auto-detect)
- Google Sign-In via JWKS
- Account linking (Google + phone)
- Sanctum token issuance and logout (+ FCM token clear)
- Phone add flow for Google-only users
- All player auth error codes

**Sprint 2B — Dashboard Auth & Roles:**
- Email + password login for dashboard users
- Custom TotpService (RFC 6238)
- Super Admin 2FA setup + enforcement middleware
- Spatie roles: `super_admin`, `club_admin`, `club_owner`, `club_data_entry`
- Spatie permissions seeded and applied to all routes
- `club_user` pivot — club scoping middleware

**Does NOT deliver:**
- Any dashboard UI (Inertia pages come in Phase 6)
- Any domain beyond user/auth

**Exit gate:**
- Player can OTP-auth and receive Sanctum token
- Player can Google Sign-In and receive token
- Account linking flow works end-to-end
- Super Admin cannot access `/admin/*` without valid TOTP session flag
- Club Admin cannot access other club's data (enforced by middleware)
- All Spatie permission checks return 403 for unauthorized roles

---

## Phase 3 — Core Domain & Schema

**Goal:** All catalog entities exist, are manageable via Admin API, and are queryable by mobile. Geography is activated. Clubs and venues go through approval lifecycle.

**Sprints:** 2

**Sprint 3A — Geography & Categories:**
- Country/State/City activation endpoints (Admin)
- VenueCategories CRUD (Admin)
- SportCategories CRUD (Admin — Events tab only)
- ContentPages CRUD (Admin)
- AppPlatforms + AppEnvironments management

**Sprint 3B — Clubs & Venues:**
- Club CRUD + approval/reject lifecycle (Admin)
- Club profile edit (Club Admin)
- Venue CRUD within club (Club Admin + Data Entry)
- Opening hours (spatie/opening-hours JSON)
- VenuePricingTiers — CRUD with day_type + time range + duration + price
- Media uploads for clubs and venues (spatie/medialibrary)
- Mobile read endpoints: browse clubs, venue detail, categories list

**Does NOT deliver:**
- Slot availability or booking
- Any payment logic
- Commission or settlement

**Exit gate:**
- Club can be created → approved → active → visible on mobile list endpoint
- Venue pricing tier resolves correctly for Syria weekday/weekend/friday calendar
- Mobile can browse clubs filtered by city_id + category_id without auth
- Admin can activate/deactivate countries/states/cities
- Media uploads store correctly in `storage/app/public`

---

## Phase 4 — Booking & Availability Engine

**Goal:** The core booking lifecycle works end-to-end. Slots computed correctly. Reservations prevent double-booking. Bookings created after payment signal (payment integration comes Phase 5 — this phase uses a stub).

**Sprints:** 2

**Sprint 4A — Slot Engine & Reservation:**
- `SlotAvailabilityService` — on-the-fly slot generation
- Syria calendar logic (weekday/weekend/friday)
- Pricing tier resolution per slot
- `slot_reservations` — create with 10-min TTL, UNIQUE guard
- `reservations:expire` scheduler command
- Manual bookings (external + blocked) via Club Dashboard API

**Sprint 4B — Booking Lifecycle:**
- Booking creation (post-payment stub — real payment in Phase 5)
- Booking code generation (DQ + zero-padded)
- Cancellation check endpoint
- Cancellation with wallet refund stub
- `bookings:complete` scheduler (marks past confirmed bookings as completed)
- `bookings:remind` scheduler (2h + 1h reminders — FCM stub)
- No-show: Club Admin marks manually via API
- Player booking history endpoints
- Saved venues CRUD

**Does NOT deliver:**
- Real payment processing (stub returns success)
- Real FCM dispatch (stubbed in Phase 4, real in Phase 6)
- Deposit flow (added in Phase 5)

**Exit gate:**
- Slot availability returns correct slots with prices for any date
- Slots in the past are not returned
- Booked slots are excluded from availability
- Reserved slots (within 10 min TTL) are excluded
- Two concurrent reservation attempts for same slot: first succeeds, second gets `SLOT_UNAVAILABLE`
- Booking is created only after payment confirmation (stub)
- Cancellation within 30-min window returns `CANCELLATION_NOT_ALLOWED`
- Scheduler marks confirmed past bookings as completed

---

## Phase 5 — Payments, Wallet & Financial Logic

**Goal:** All four payment providers integrated. Wallet working. Deposit model working. Commission calculated and snapshotted. Settlement system functional.

**Sprints:** 3

**Sprint 5A — MTN Cash & Syriatel Cash (OTP providers):**
- `PaymentGateway` interface
- `MtnCashGateway` — 3-step OTP flow
- `SyriatelCashGateway` — 2-step OTP flow
- Payment initiate/confirm/resend-otp endpoints
- `provider_meta` encrypted storage
- Slot reservation→booking transaction on payment success

**Sprint 5B — Fatora/SamaPay (WebView) + Wallet:**
- `FatoraGateway` — hosted page URL generation + callback handler
- `SamaPayGateway` — same as Fatora with different config
- Callback idempotency (provider_transaction_id check)
- Payment status polling endpoint
- Internal Wallet — `WalletService` with `SELECT FOR UPDATE`
- Wallet debit payment flow
- Wallet refund on cancellation (full + deposit scenarios)

**Sprint 5C — Commission, Deposit & Settlement:**
- `CommissionService::resolveConfig()` + `calculate()` — global scope only
- Deposit model: `payment_mode: full|deposit`, `deposit_amount` validation
- Commission calculated on `deposit_amount` for deposit bookings
- Club confirms remaining cash receipt endpoint
- `settlements` + `settlement_items` creation by Admin
- Settlement status transitions (draft → pending → completed)
- Settlement preview endpoint
- `wallet:reconcile` artisan command

**Does NOT deliver:**
- Per-club or per-venue commission config (Phase 2 feature)
- Automated payout (manual only)

**Exit gate:**
- MTN Cash: full 3-step flow completes and creates confirmed booking
- Syriatel Cash: full 2-step flow completes and creates confirmed booking
- Fatora callback (with replay) processed idempotently
- Wallet debit fails with `INSUFFICIENT_WALLET_BALANCE` if balance < amount
- Two concurrent wallet debits for same user: only one succeeds
- Cancellation of paid booking: correct refund amount goes to wallet
- Deposit booking: commission on deposit_amount only, not total_price
- Settlement preview shows correct net_payable
- Settlement_items UNIQUE prevents double-counting booking in two settlements

---

## Phase 6 — Dashboards, Notifications & Operations

**Goal:** Both Inertia dashboards functional. FCM notifications live. Events tab working. Last-Minute Deals and Waitlist active. Behavioral tracking recording. Reviews live.

**Sprints:** 3

**Sprint 6A — Super Admin Dashboard (Inertia):**
- Inertia + Vue 3 + Tailwind + shadcn-vue setup
- Auth pages (login, 2FA)
- Geography management pages
- Club management + approval queue
- Venue management
- Booking list + detail + override
- Commission config page
- Settlement management pages
- Revenue reports (basic)
- User management

**Sprint 6B — Club Dashboard + Notifications:**
- Club Dashboard Inertia setup (separate app)
- Progressive onboarding (3 sections unlocked, rest gated)
- Venue management pages
- Calendar view (bookings by date)
- Manual booking creation
- Remaining amount confirmation UI
- Flash Deals creation UI
- Real FCM dispatch: `FcmService` custom (HTTP v1, no package)
- `BookingConfirmedNotificationJob` — player + admin + club FCM
- `BookingCancelledNotificationJob`
- `VenueAvailableNotificationJob` (cancellation + waitlist)
- `BookingReminderJob` (2h + 1h)
- SMS confirmation via Syriatel/MTN for every confirmed booking

**Sprint 6C — Demand Features, Events, Reviews & Tracking:**
- Last-Minute Deals: Club creates, FCM dispatched to interested players
- Waitlist: join, alert on cancellation/slot opening
- Reviews: create, eligibility check, admin moderation
- Events tab: competitions CRUD, football matches proxy + 5-min cache
- `player_events` batch tracking endpoint
- Admin behavioral analytics endpoints (basic funnels, D7/D30 retention)
- `spatie/laravel-activitylog` registered on all models
- `spatie/laravel-backup` scheduled

**Does NOT deliver:**
- Full analytics dashboard UI (endpoints only in Phase 6)
- Per-player journey views (Phase 2 feature)

**Exit gate:**
- Super Admin can approve a club and it appears on mobile
- Club Admin can create a venue with pricing and it is bookable
- FCM notification reaches player after booking confirmation
- SMS confirmation fires for every confirmed booking
- Last-Minute Deal FCM dispatched to players in same area
- Waitlist FCM dispatched on slot opening (from cancellation or Flash Deal)
- Review creation blocked if no completed booking at that club
- `player_events` batch endpoint stores events and returns 200

---

## Phase 7 — Hardening & Launch Readiness

**Goal:** Test coverage adequate. Performance acceptable. Security reviewed. Scheduler verified. Blocked items resolved or documented. Go/no-go gate passed.

**Sprints:** 1

**Delivers:**
- Feature test coverage for all critical flows (auth, booking, payment, cancellation, settlement)
- Unit tests for `CommissionService`, `SlotAvailabilityService`, `TotpService`, `WalletService`
- Rate limiting verified on OTP endpoints
- `wallet:reconcile` command tested
- All scheduler commands tested
- API response shapes verified against spec envelope
- BLOCKED-001 (Fatora callback security) resolved or mitigated
- BLOCKED-003 (no-show) confirmed as manual Club Admin action
- BLOCKED-004 (Baileys session) operationally documented
- Load tested: slot availability endpoint under concurrent requests
- Failed jobs monitoring documented
- `.env.production.example` finalized

**Exit gate:**
- All feature tests green
- No `dd()` or `dump()` in codebase
- All routes have permission middleware
- OTP endpoint rejects after 5 attempts (returns `OTP_LOCKED`)
- Double-booking attempt under concurrent load: zero successful duplicates
- `php artisan migrate:fresh --seed` takes < 3 minutes
- Health check endpoint returns 200 with DB + queue status
