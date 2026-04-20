# Task Breakdown

> Every task: ID, title, why, affected files/modules, tests, acceptance criteria, DoD, risk note.

---

## Phase 1 — Sprint 1: Bootstrap

---

### T-001 — Docker Compose Dev Environment

**Why:** Every developer needs identical, reproducible environment. No "works on my machine".

**Scope:**
- `docker-compose.yml`: PHP 8.3-fpm, MySQL 8.0, queue worker, scheduler
- `Dockerfile` for PHP image with required extensions
- `docker-compose.override.yml` for local dev (volume mounts, port exposure)
- `.dockerignore`

**Dependencies:** None.

**Files affected:**
- `docker-compose.yml`, `Dockerfile`, `.dockerignore`

**Tests required:** None (infrastructure). Verify: `docker compose up -d` → all containers healthy.

**Acceptance criteria:**
- `docker compose up -d` completes without error
- `docker compose exec app php artisan --version` returns Laravel 13.x
- MySQL container accessible on port 3306
- Queue worker container running (`docker compose ps` shows `running`)

**Done definition:** Developer clone → `docker compose up -d` → working app in < 5 minutes.

---

### T-002 — Laravel 13 Install + Package Installation

**Why:** All locked packages must be installed and service providers booted before any work begins.

**Scope:**
- `composer create-project laravel/laravel` or `laravel new` with Laravel 13
- Install all locked packages:
  - `laravel/sanctum`
  - `spatie/laravel-permission`
  - `spatie/laravel-activitylog`
  - `spatie/laravel-medialibrary`
  - `spatie/laravel-translatable`
  - `spatie/laravel-settings`
  - `spatie/opening-hours`
  - `spatie/laravel-backup`
  - `spatie/laravel-sluggable`
  - `inertiajs/inertia-laravel`
  - `tightenco/ziggy`
- `npm install`: Vue 3, Tailwind CSS 4, shadcn-vue, Vite
- Publish vendor configs
- Configure guards: `api` (Sanctum) + `web` (session)

**Dependencies:** T-001

**Files affected:**
- `composer.json`, `package.json`, `config/auth.php`, `config/permission.php`
- `config/media-library.php`, `config/activitylog.php`, `bootstrap/providers.php`

**Tests required:**
- `php artisan package:discover` — no errors
- `php artisan config:cache` — no errors

**Acceptance criteria:**
- `composer install` completes with no errors
- `php artisan tinker` → `app()->make(\Spatie\Permission\PermissionRegistrar::class)` returns object
- `config('auth.guards.api.driver')` returns `'sanctum'`
- `config('auth.guards.web.driver')` returns `'session'`

**Done definition:** All packages installed, zero boot errors, guards configured correctly.

---

### T-003 — All Migrations (Correct Order)

**Why:** Schema must be created in dependency order. All tables, indexes, and foreign keys from `database_schema.md`.

**Scope — migration order:**
```
0001 countries
0002 states
0003 cities
0004 users
0005 social_identities
0006 otp_challenges
0007 venue_categories
0008 sport_categories
0009 clubs
0010 club_user
0011 venues
0012 venue_pricing_tiers
0013 commission_configs
0014 bookings              (includes deposit fields)
0015 slot_reservations
0016 payments
0017 payment_methods
0018 wallets
0019 wallet_transactions
0020 reviews
0021 saved_venues
0022 settlements
0023 settlement_items
0024 app_platforms
0025 app_environments
0026 content_pages
0027 competitions
0028 venue_flash_deals
0029 venue_waitlist
0030 player_events
0031 jobs (queue)
0032 failed_jobs
0033 Sanctum personal_access_tokens
0034 Spatie permissions tables
0035 Activity log table
0036 Settings table (spatie/laravel-settings)
```

**Dependencies:** T-002

**Files affected:**
- `database/migrations/` — 36 migration files

**Tests required:**
- `php artisan migrate:fresh` — no errors
- `php artisan migrate:status` — all migrations `Ran`

**Acceptance criteria:**
- `migrate:fresh` completes without foreign key errors
- `SHOW TABLES` in MySQL shows all 36 expected tables
- `bookings` table has columns: `deposit_amount`, `deposit_status`, `remaining_amount`, `remaining_status`, `remaining_confirmed_at`, `remaining_confirmed_by`
- `commission_configs` table does NOT have `apply_as` column
- `bookings.commission_type` is `ENUM('fixed','percentage')` not `ENUM('added','deducted')`

**Done definition:** `migrate:fresh` clean. All schema matches `database_schema.md` exactly.

**Risk:** Migration order errors (FK before parent) → run `migrate:fresh` in CI on every PR.

---

### T-004 — Geography Seeder (dr5hn)

**Why:** 153K+ cities must be seeded once. Admin activates what they need. No manual city entry.

**Scope:**
- `WorldGeographySeeder` class
- Download `countries.json` + `states.json` + `cities.json` from dr5hn GitHub releases
- Store JSON files in `database/data/` (gitignored for large files, or reference download URL in README)
- Chunk-insert cities in batches of 500 to avoid memory issues
- `upsert` strategy: insert-or-update by `id` from source
- Post-seed: activate Syria (`iso2=SY`) automatically — `is_active = 1`
- Syria's states all activated by default

**Dependencies:** T-003

**Files affected:**
- `database/seeders/WorldGeographySeeder.php`
- `database/data/` (JSON files or download script)

**Tests required:**
- After seeding: `Country::where('iso2', 'SY')->first()->is_active === 1`
- After seeding: `State::where('country_id', $syriaId)->count() >= 14`
- After seeding: `City::count() > 100000`

**Acceptance criteria:**
- Seeder completes without memory error
- Syria country record: `phone_code = '+963'`, `is_active = 1`
- At least 14 Syrian states (محافظات) seeded
- At least 1000 Syrian cities seeded
- All non-Syria countries have `is_active = 0`

**Done definition:** Seeder runs, Syria active, data queryable.

**Risk:** Large JSON files (cities.json ~15MB) → chunk insert in batches of 500.

---

### T-005 — App Startup Endpoint

**Why:** Mobile app calls this on every launch. Returns platform config, update status, maintenance mode.

**Scope:**
- `POST /api/v1/app/startup`
- Request: `{platform_key, app_version}`
- Response: `{base_url, update: {is_required, is_optional, latest_version, download_url}, app_settings: {app_name, maintenance_mode, maintenance_message}}`
- Version comparison logic: semantic version compare
- Download URL: `store_url` or `direct_apk_url` if `direct_apk_enabled=true`
- Reads from `app_platforms` + `app_environments` tables

**Dependencies:** T-003

**Files affected:**
- `app/Http/Controllers/Api/V1/AppStartupController.php`
- `app/Services/AppStartupService.php`
- `routes/api.php`

**Tests required:**
- Unit test: version comparison logic (current < minimum → required, < latest → optional, = latest → both false)
- Feature test: POST with `platform_key=android`, `app_version=1.0.0` → 200 with correct structure
- Feature test: unknown platform_key → 404

**Acceptance criteria:**
- `POST /api/v1/app/startup {platform_key: "android", app_version: "1.0.0"}` returns 200
- Response has `success: true`, `data.update`, `data.app_settings`
- `maintenance_mode: true` in settings → response has `app_settings.maintenance_mode: true`
- Unknown platform_key → `{success: false, error: {code: "PLATFORM_NOT_FOUND"}}`

**Done definition:** Endpoint live, version logic correct, tests green.

---

### T-006 — CI Pipeline Setup

**Why:** Every PR must be verified automatically. Prevents regressions.

**Scope:**
- GitHub Actions (or equivalent) workflow file
- Jobs: `composer install`, `php artisan migrate:fresh`, `php artisan test`
- `.env.testing` with in-memory SQLite or test MySQL
- Cache composer dependencies between runs

**Dependencies:** T-001, T-002

**Files affected:**
- `.github/workflows/ci.yml`, `.env.testing`, `phpunit.xml`

**Tests required:** CI itself is the verification.

**Acceptance criteria:**
- Push to any branch triggers CI
- CI runs `php artisan test` and reports results
- CI fails on any test failure
- CI completes in < 5 minutes

**Done definition:** CI green on clean repo.

---

## Phase 2 — Sprint 2A: Player Auth

---

### T-007 — OTP Request Endpoint

**Why:** Entry point for all player authentication. Must detect WhatsApp availability and send OTP.

**Scope:**
- `POST /api/v1/auth/otp/request {phone_number}`
- Phone normalization: strip leading 0, prepend +963
- Baileys availability check (HTTP call to Baileys Node service) — if unavailable or timeout → SMS automatically
- If WhatsApp available: send via Baileys, set `channel='whatsapp'`
- If not: send via Syriatel or MTN (per settings), set `channel='sms'`
- Create `otp_challenges` record: `uuid`, `code_hash` (SHA-256), `expires_at` (NOW + 120s from settings), `channel`
- Rate limiting: per phone (max 3 requests/10 min), per IP (configurable)
- Response: `{verification_request_id: uuid, channel_used: sms|whatsapp, expires_in_seconds, resend_after_seconds}`

**Dependencies:** T-003, T-005 (settings infrastructure)

**Files affected:**
- `app/Http/Controllers/Api/V1/Auth/OtpController.php`
- `app/Services/Auth/OtpService.php`
- `app/Services/Sms/SmsOtpService.php` (interface)
- `app/Services/Sms/SyriatelOtpDriver.php`
- `app/Services/Sms/MtnOtpDriver.php`
- `app/Services/WhatsApp/BaileysService.php`

**Tests required:**
- Feature test: valid Syrian phone → 200, uuid returned
- Feature test: OTP code stored as SHA-256 hash (never plain text)
- Feature test: 4th request within 10 min → 429
- Unit test: phone normalization (0991234567 → +963991234567)
- Unit test: Baileys unavailable → SMS fallback

**Acceptance criteria:**
- `POST /api/v1/auth/otp/request {phone_number: "0991234567"}` → 200
- `otp_challenges` record created with `code_hash != otp_code` (it's a hash)
- `code_hash = hash('sha256', otp_code)`
- 4th request from same phone in 10 min → 429
- Baileys service returning error → channel falls back to SMS without error to client

**Done definition:** OTP sent, hash stored, rate limiting enforced.

---

### T-008 — OTP Verify + Token Issuance

**Why:** Converts a valid OTP into a Sanctum bearer token. Handles new vs returning user.

**Scope:**
- `POST /api/v1/auth/otp/verify {verification_request_id, otp_code}`
- Hash submitted code, compare to `code_hash`
- Check: not expired, not consumed, attempts < max (5)
- On match: mark `consumed_at`, create/find user by phone, issue Sanctum token (30-day TTL)
- On fail: increment `attempts_count`, return `OTP_INVALID`
- On max attempts: return `OTP_MAX_ATTEMPTS`, mark challenge locked
- Response: `{access_token, token_type: bearer, auth_outcome: login|registration, next_step: home|profile_completion}`
- New user: `account_status = pending_profile_completion`
- Returning user: `account_status = active` (if was active)

**Dependencies:** T-007

**Files affected:**
- `app/Http/Controllers/Api/V1/Auth/OtpController.php`
- `app/Services/Auth/OtpService.php`
- `app/Models/User.php`

**Tests required:**
- Feature test: correct OTP → 200, token returned
- Feature test: wrong OTP → `OTP_INVALID`, attempts_count incremented
- Feature test: expired OTP → `OTP_EXPIRED`
- Feature test: 5 wrong attempts → `OTP_MAX_ATTEMPTS` then `OTP_LOCKED`
- Feature test: new phone → `auth_outcome: registration`
- Feature test: existing phone → `auth_outcome: login`

**Acceptance criteria:**
- Correct OTP → token issued, `consumed_at` set in DB
- Expired challenge (created > 120s ago) → `OTP_EXPIRED`, no token
- 6th attempt after lockout → `OTP_LOCKED` even with correct code
- New user token: `users.account_status = pending_profile_completion`
- Existing user token: `users.last_login_at` updated

**Done definition:** Full OTP verify flow tested, token issuance correct.

---

### T-009 — OTP Resend

**Why:** User may not receive OTP on first attempt. Resend with cooldown.

**Scope:**
- `POST /api/v1/auth/otp/resend {verification_request_id}`
- Check: challenge not consumed, not expired, resend_count < max (3)
- Cooldown: `resend_count > 0` → check `updated_at > NOW() - 60s` → if too soon → 429
- Generate new code, update `code_hash`, increment `resend_count`
- Resend via same channel as original

**Dependencies:** T-007, T-008

**Files affected:**
- `app/Http/Controllers/Api/V1/Auth/OtpController.php`
- `app/Services/Auth/OtpService.php`

**Tests required:**
- Feature test: resend within 60s → 429
- Feature test: resend after 60s → 200, new code sent
- Feature test: 4th resend → rejected

**Acceptance criteria:**
- Resend within 60s: 429 with `resend_after_seconds` in response
- Resend after 60s: 200, new hash in `otp_challenges`, `resend_count` incremented
- 4th resend: 422 with `OTP_MAX_RESEND` code

**Done definition:** Resend works, cooldown enforced, max resend enforced.

---

### T-010 — Google Sign-In via JWKS

**Why:** Players can authenticate via Google. Backend verifies Firebase JWT without kreait.

**Scope:**
- `POST /api/v1/auth/google {firebase_token}`
- `FirebaseAuthService::verify($token)`:
  - Fetch Google JWKS from `https://www.googleapis.com/service_accounts/v1/jwk/securetoken@system.gserviceaccount.com`
  - Cache JWKS for 1 hour (file cache)
  - Verify JWT signature, expiry, `aud` = Firebase project ID
  - Extract: `sub` (firebase_uid), `email`, `name`, `phone_number`
- Lookup `social_identities` by `(provider='google', provider_uid=sub)`
- If phone_number matches existing user → return `{action: link_required, masked_phone}`
- Else: upsert user, create/update `social_identities` record, issue Sanctum token

**Dependencies:** T-008

**Files affected:**
- `app/Services/Auth/FirebaseAuthService.php`
- `app/Http/Controllers/Api/V1/Auth/GoogleAuthController.php`
- `app/Models/SocialIdentity.php`

**Tests required:**
- Unit test: `FirebaseAuthService` with valid mock JWT → correct claims extracted
- Unit test: expired JWT → exception thrown
- Feature test: new Google user → user created, token issued
- Feature test: Google user with phone matching existing OTP user → `link_required` returned

**Acceptance criteria:**
- Valid Firebase token (mock) → user created with `firebase_uid`, token returned
- Invalid token signature → 401 `INVALID_FIREBASE_TOKEN`
- Expired token → 401 `INVALID_FIREBASE_TOKEN`
- Phone conflict: response `{action: link_required, masked_phone: "+963***XXXX"}`

**Done definition:** Google auth works. JWKS verified. No kreait dependency.

---

### T-011 — Account Linking

**Why:** Google user with same phone as existing OTP user must be merged, not duplicated.

**Scope:**
- `POST /api/v1/auth/link {verification_request_id, otp_code}`
- Verify OTP for the conflicting phone
- Merge: add `social_identities` row to the existing OTP user
- Do NOT create a new user. Phone account data (bookings, wallet) preserved.
- Issue Sanctum token for the existing (now merged) user

**Dependencies:** T-008, T-010

**Files affected:**
- `app/Http/Controllers/Api/V1/Auth/AccountLinkController.php`
- `app/Services/Auth/AccountLinkingService.php`

**Tests required:**
- Feature test: OTP verify → social_identity created for existing user, no new user created
- Feature test: after linking, Google token → same user returned

**Acceptance criteria:**
- After linking: `users.count()` unchanged (no new user)
- `social_identities` row added to existing user
- Token issued for existing user (correct `id`, existing `wallet`, existing `bookings`)
- Second link attempt with same Google account → 409 `ACCOUNT_ALREADY_LINKED`

**Done definition:** Linking preserves existing data, no duplicates.

---

### T-012 — Phone Add Flow (Google-Only Users)

**Why:** Google users without phone cannot book. They must add a phone before their first booking attempt.

**Scope:**
- `POST /api/v1/me/phone/request {phone_number}` → OTP sent
- `POST /api/v1/me/phone/verify {verification_request_id, otp_code}` → phone saved
- Check: phone not already taken by another user
- On success: `users.phone_number` set, `users.phone_verified_at` set

**Dependencies:** T-007, T-008

**Files affected:**
- `app/Http/Controllers/Api/V1/Profile/PhoneController.php`

**Tests required:**
- Feature test: add phone → phone_verified_at set, phone_number saved
- Feature test: phone already belonging to another user → `PHONE_ALREADY_EXISTS`

**Acceptance criteria:**
- After verify: `users.phone_number` matches submitted number (normalized)
- `users.phone_verified_at` is not null
- Booking now allowed (no `PHONE_REQUIRED`)
- Duplicate phone → 422 `PHONE_ALREADY_EXISTS`

**Done definition:** Google users can add phone and then book.

---

### T-013 — Logout

**Why:** Logout must revoke token AND clear FCM token to prevent ghost notifications.

**Scope:**
- `POST /api/v1/auth/logout`
- Delete current Sanctum token
- Set `users.fcm_token = NULL`

**Dependencies:** T-008

**Files affected:**
- `app/Http/Controllers/Api/V1/Auth/AuthController.php`

**Tests required:**
- Feature test: logout → token deleted from `personal_access_tokens`
- Feature test: logout → `users.fcm_token = NULL`
- Feature test: use deleted token → 401

**Acceptance criteria:**
- After logout: `Authorization: Bearer {old_token}` → 401
- `users.fcm_token = NULL` in DB after logout
- Second logout with same token → 401 (token already gone)

**Done definition:** Token revoked, FCM cleared.

---

### T-014 — OTP Rate Limiting

**Why:** OTP endpoints are brute-force targets. Must be rate-limited independently of framework defaults.

**Scope:**
- Per-phone: max 3 OTP requests per 10 minutes (configurable via settings)
- Per-IP: max 10 OTP requests per 10 minutes
- Custom `OtpRateLimiter` using Laravel's `RateLimiter` facade
- Configurable via `spatie/laravel-settings`

**Dependencies:** T-007

**Files affected:**
- `app/Http/Middleware/OtpRateLimiter.php` (or `app/Services/Auth/OtpService.php` integrated)
- `app/Settings/OtpSettings.php`

**Tests required:**
- Feature test: 4th request from same phone → 429
- Feature test: requests from 11 IPs — 10 pass, 11th is rate-limited (separate phone numbers)

**Acceptance criteria:**
- 4th OTP request from same phone in 10 min → 429 with `Retry-After` header
- Rate limits configurable without code change (via settings table)

**Done definition:** Rate limiting enforced on OTP request endpoint.

---

## Phase 2 — Sprint 2B: Dashboard Auth & Roles

---

### T-015 — Dashboard Login

**Why:** Club staff and Admin must authenticate via email + password through the web guard.

**Scope:**
- `POST /dashboard/login {email, password}` (Inertia form action)
- Standard Laravel `Auth::attempt()` on `web` guard
- On success: redirect to `/admin` or `/club` based on role
- On fail: back with error
- Session-based auth (no Sanctum for dashboard)
- Failed login throttle: 5 attempts per minute

**Dependencies:** T-003, T-018 (roles)

**Files affected:**
- `app/Http/Controllers/Dashboard/Auth/LoginController.php`
- `routes/web.php`

**Tests required:**
- Feature test: correct credentials → session created, redirect to correct dashboard
- Feature test: wrong password → back with error, no session
- Feature test: 6th attempt in 1 min → 429

**Acceptance criteria:**
- Valid Super Admin credentials → session, redirect to `/admin/dashboard`
- Valid Club Admin credentials → session, redirect to `/club/dashboard`
- Invalid credentials → no session, error message
- 6th attempt: 429 `Too Many Login Attempts`

**Done definition:** Dashboard login works for all dashboard roles.

---

### T-016 — Custom TotpService

**Why:** Super Admin requires TOTP 2FA. Must use PHP native (no package). RFC 6238.

**Scope:**
- `app/Services/Auth/TotpService.php`
- `generateSecret()`: `random_bytes(20)` → Base32 encoded
- `generateQrCodeUri($secret, $email)`: `otpauth://totp/{label}?secret={secret}&issuer={app}`
- `verify($secret, $code, $window=1)`: check current ± 1 time windows (30s steps)
- Base32 encode/decode: written manually (RFC 4648)
- `users.google2fa_secret` stored with Laravel `encrypted` cast

**Dependencies:** T-003

**Files affected:**
- `app/Services/Auth/TotpService.php`

**Tests required:**
- Unit test: `generateSecret()` returns 32-char Base32 string
- Unit test: `verify()` with known secret + current TOTP code → true
- Unit test: `verify()` with code from 90s ago → false (outside ±1 window)
- Unit test: `verify()` with code from adjacent window (−30s, +30s) → true

**Acceptance criteria:**
- Generated secret passes `preg_match('/^[A-Z2-7]{32}$/', $secret)`
- Verify with current-time code → true
- Verify with code 90 seconds old → false
- Verify with code 30 seconds old → true (within ±1 window)

**Done definition:** TotpService unit tests green. No external TOTP package in composer.json.

---

### T-017 — Super Admin 2FA Enforcement

**Why:** Super Admin must complete TOTP after password before accessing any admin route.

**Scope:**
- `POST /admin/auth/2fa/verify {code}` → verifies TOTP, sets session flag `2fa_verified=true`
- Middleware `EnsureTwoFactorAuthenticated`: checks session flag on all `/admin/*` routes
- 2FA setup flow: Super Admin scans QR, enters code to confirm, `google2fa_enabled_at` set
- Login without 2FA setup: redirect to setup page before any admin access

**Dependencies:** T-015, T-016

**Files affected:**
- `app/Http/Middleware/EnsureTwoFactorAuthenticated.php`
- `app/Http/Controllers/Dashboard/Admin/Auth/TwoFactorController.php`
- `routes/web.php` (admin group middleware stack)

**Tests required:**
- Feature test: Super Admin logs in but skips 2FA page → all `/admin/*` routes redirect to `/admin/auth/2fa`
- Feature test: wrong TOTP code → stays on 2FA page
- Feature test: correct TOTP code → `2fa_verified` session flag set, admin access granted

**Acceptance criteria:**
- Without `2fa_verified` session flag: `GET /admin/clubs` → redirect to `/admin/auth/2fa`
- After correct TOTP: `GET /admin/clubs` → 200
- 2FA session flag cleared on logout
- Club Admin (not Super Admin) is not subject to 2FA middleware

**Done definition:** Super Admin cannot reach any admin page without completing 2FA.

---

### T-018 — Spatie Roles & Permissions Seed

**Why:** All routes need permissions. Roles need correct permission sets from day one.

**Scope:**
- `database/seeders/RolesAndPermissionsSeeder.php`
- Roles: `super_admin`, `club_admin`, `club_owner`, `club_data_entry`
- Permission naming: `{guard}.{resource}.{action}` e.g. `admin.clubs.view`
- Admin permissions (all assigned to `super_admin`):
  `admin.clubs.view|create|edit|approve|suspend`
  `admin.venues.view|edit|suspend`
  `admin.bookings.view|manage`
  `admin.users.view|manage`
  `admin.commission.manage`
  `admin.settlements.manage`
  `admin.reports.view`
  `admin.settings.manage`
  `admin.roles.manage`
  `admin.geography.manage`
- Club permissions: `club.bookings.view|create_manual|cancel`, `club.venues.view|manage`, `club.pricing.manage`, `club.reports.view`, `club.settlements.view`, `club.staff.manage`, `club.remaining.confirm`, `club.flash_deals.manage`
- Guard: permissions for admin routes use `web` guard

**Dependencies:** T-003, T-002 (Spatie installed)

**Files affected:**
- `database/seeders/RolesAndPermissionsSeeder.php`
- `database/seeders/DatabaseSeeder.php`

**Tests required:**
- Feature test: `super_admin` role has all admin permissions
- Feature test: `club_admin` has no `admin.*` permissions
- Feature test: `club_data_entry` has no `club.reports.view` permission

**Acceptance criteria:**
- `super_admin` role: `hasPermissionTo('admin.clubs.approve')` → true
- `club_admin` role: `hasPermissionTo('admin.clubs.approve')` → false
- `club_owner` role: only `club.reports.view` + `club.settlements.view` → true; `club.venues.manage` → false
- `club_data_entry` role: `club.venues.manage` → true; `club.reports.view` → false

**Done definition:** All roles seeded with correct permissions. Used by route middleware.

---

### T-019 — Club Scoping Middleware

**Why:** Club staff must only access data belonging to their assigned clubs.

**Scope:**
- `app/Http/Middleware/EnsureUserBelongsToClub.php`
- Extracts `club_id` from route parameter
- Checks `club_user` pivot: `WHERE user_id = auth()->id() AND club_id = $clubId`
- If not found → 403
- Injected into all `/club/*` routes that have a `{club}` parameter
- `auth()->user()->clubs()` BelongsToMany relationship on User model

**Dependencies:** T-018

**Files affected:**
- `app/Http/Middleware/EnsureUserBelongsToClub.php`
- `app/Models/User.php` (clubs relationship)
- `routes/web.php` (club route group)

**Tests required:**
- Feature test: Club Admin accessing their own club → 200
- Feature test: Club Admin accessing another club → 403
- Feature test: Super Admin accessing any club (no pivot required) → 200

**Acceptance criteria:**
- Club Admin with `club_user(club_id=1, user_id=X)` → can access `/club/1/*`
- Same Club Admin → cannot access `/club/2/*` (403)
- Super Admin → can access any club regardless of pivot

**Done definition:** Scoping enforced at middleware level, not just controller.

---

### T-020 — Dashboard Logout

**Why:** Logout clears session AND FCM token. Same requirement as player logout.

**Scope:**
- `POST /dashboard/logout`
- `Auth::logout()` on web guard
- Session invalidate + regenerate token
- `users.fcm_token = NULL`

**Dependencies:** T-015

**Files affected:**
- `app/Http/Controllers/Dashboard/Auth/LoginController.php`

**Tests required:**
- Feature test: after logout, session-authenticated route returns redirect to login
- Feature test: `users.fcm_token = NULL` after logout

**Acceptance criteria:**
- After logout: `GET /admin/dashboard` → redirect to login
- `users.fcm_token = NULL` in DB

**Done definition:** Logout clears both session and FCM token.

---

## Phase 3 — Sprint 3A: Geography, Categories & Config

---

### T-021 — Geography Activation (Admin API)

**Why:** Admin controls which countries/states/cities are visible to mobile users.

**Scope:**
- `PATCH /api/admin/v1/countries/{id}/toggle` — activate/deactivate
- `PATCH /api/admin/v1/states/{id}/toggle`
- `PATCH /api/admin/v1/cities/{id}/toggle`
- `PUT /api/admin/v1/countries/{id}` — update `name_ar` only (no other fields)
- List endpoints with pagination for Admin Dashboard

**Dependencies:** T-004, T-018

**Files affected:**
- `app/Http/Controllers/Api/Admin/V1/GeographyController.php`
- `app/Models/Country.php`, `State.php`, `City.php`

**Tests required:**
- Feature test: toggle country → `is_active` flips
- Feature test: activated country appears in mobile endpoint, deactivated does not

**Acceptance criteria:**
- `PATCH /api/admin/v1/countries/213/toggle` → `is_active` toggles
- Mobile `GET /api/v1/countries` only returns `is_active=1` records
- `PUT` can only change `name_ar`, not `iso2` or `phone_code`

**Done definition:** Admin controls geography. Mobile sees only active data.

---

### T-022 — VenueCategories CRUD (Admin)

**Why:** Admin manages what category types exist. No hardcoded categories.

**Scope:**
- `GET|POST|PUT|DELETE /api/admin/v1/venue-categories`
- Fields: `name (JSON)`, `slug (auto)`, `type (enum)`, `is_active`, `order_column`
- Media: `icon`, `cover` via spatie/medialibrary
- Mobile read: `GET /api/v1/categories` — active, ordered

**Dependencies:** T-018, T-002 (medialibrary)

**Files affected:**
- `app/Http/Controllers/Api/Admin/V1/VenueCategoryController.php`
- `app/Models/VenueCategory.php`

**Tests required:**
- Feature test: create category → appears in mobile list
- Feature test: deactivate category → absent from mobile list
- Feature test: duplicate slug → validation error

**Acceptance criteria:**
- Created category with `name.ar="ملاعب كرة القدم"` → slug auto-generated from `name.en`
- Mobile list: sorted by `order_column ASC`
- Deleted category with venues assigned → 422 (venues must be reassigned first)

**Done definition:** Category CRUD works, mobile consumes correctly.

---

### T-025 — App Platforms & Environments Management

**Why:** Admin controls version gating and base URLs dynamically from Dashboard.

**Scope:**
- `GET|POST|PUT /api/admin/v1/app-platforms`
- `GET|POST|PUT /api/admin/v1/app-platforms/{id}/environments`
- Only one active environment per platform enforced

**Dependencies:** T-005, T-018

**Files affected:**
- `app/Http/Controllers/Api/Admin/V1/AppPlatformController.php`
- `app/Models/AppPlatform.php`, `AppEnvironment.php`

**Tests required:**
- Feature test: set two environments active for same platform → second activation deactivates first

**Acceptance criteria:**
- Activating environment B deactivates environment A for same platform
- Startup endpoint picks up new version within one request (no stale cache)

**Done definition:** Platform/environment management functional.

---

### T-027 — Club CRUD + Approval Lifecycle

**Why:** Clubs must go through approval before appearing on mobile.

**Scope:**
- `POST /api/admin/v1/clubs` — Admin creates club
- `GET /api/admin/v1/clubs` — list with filters (status, city)
- `PATCH /api/admin/v1/clubs/{id}/approve` → `status=active`, `approved_at`, `approved_by`
- `PATCH /api/admin/v1/clubs/{id}/reject {rejection_reason}` → `status=rejected`
- `PATCH /api/admin/v1/clubs/{id}/suspend` → `status=suspended`
- Club slug auto-generated (spatie/laravel-sluggable)
- Mobile: `GET /api/v1/clubs` — only `status=active` clubs

**Dependencies:** T-021, T-022, T-018

**Files affected:**
- `app/Http/Controllers/Api/Admin/V1/ClubController.php`
- `app/Models/Club.php`
- `app/Http/Controllers/Api/V1/ClubController.php` (mobile read)

**Tests required:**
- Feature test: club at `pending_approval` → absent from mobile list
- Feature test: club approved → present in mobile list
- Feature test: club suspended → absent from mobile list
- Feature test: activity log entry created on approve/reject

**Acceptance criteria:**
- `PATCH /approve` → `clubs.status = active`, `approved_by = auth()->id()`
- `PATCH /reject {rejection_reason: "Missing documents"}` → `status = rejected`
- Mobile `GET /api/v1/clubs` with `city_id=X` only returns active clubs in that city
- `spatie/laravel-activitylog` entry exists for approval action

**Done definition:** Full approval lifecycle working. Mobile isolation confirmed.

---

### T-031 — VenuePricingTier CRUD with Syria Calendar

**Why:** Prices vary by day type and time. Syria weekday = Sun–Thu, weekend = Fri+Sat.

**Scope:**
- `POST|PUT|DELETE /api/club/v1/clubs/{club}/venues/{venue}/pricing-tiers`
- Fields: `name`, `day_type`, `specific_day`, `start_time`, `end_time`, `duration_minutes`, `price`, `is_active`
- `TierMatchingService::resolvePrice($venue, $date, $duration)`:
  - Determine day classification for $date (weekday/weekend/friday/specific)
  - Find tier WHERE `day_type` matches AND `start_time <= slot_start < end_time` AND `duration_minutes = $duration`
  - Return price or null (slot not bookable)
- Syria calendar: Sunday=weekday, Friday=friday (separate case), Saturday=weekend

**Dependencies:** T-029

**Files affected:**
- `app/Services/Booking/TierMatchingService.php`
- `app/Models/VenuePricingTier.php`
- `app/Http/Controllers/Api/Club/V1/PricingTierController.php`

**Tests required:**
- Unit test: date is Friday → `day_type=friday` tier matched, not `weekend`
- Unit test: date is Saturday → `day_type=weekend` matched
- Unit test: date is Sunday → `day_type=weekday` matched
- Unit test: slot at 16:00, tier covers 15:00–22:00 → matched
- Unit test: slot at 14:00, tier covers 15:00–22:00 → not matched
- Unit test: no tier for duration 90min when only 60min tier exists → returns null

**Acceptance criteria:**
- Friday slot → matched to `day_type=friday` tier (not weekend tier even if `weekend` tier exists)
- Tier for duration 60min only → request for 90min → slot not bookable
- Overlapping tiers for same day+duration → application validation rejects second one

**Done definition:** Tier resolution correct for all Syria calendar cases.

---

## Phase 4 — Sprint 4A: Slot Engine

---

### T-036 — SlotAvailabilityService

**Why:** Core booking engine. Must compute available slots on-the-fly without a slots table.

**Scope:**
- `SlotAvailabilityService::getAvailableSlots($venue, $date, $duration)`:
  1. Parse `venue.opening_hours` via `spatie/opening-hours` for $date
  2. Generate candidate slots from open_from to open_until, step = $duration_minutes
  3. Exclude: `bookings` with `status NOT IN (cancelled, failed)` overlapping slot
  4. Exclude: `slot_reservations` with `reserved_until > NOW()` for same slot
  5. For each remaining slot: call `TierMatchingService::resolvePrice()`
  6. If no price → omit slot
  7. Return: `[{start_time, end_time, price, duration_minutes}]`
- `GET /api/v1/clubs/{club}/venues/{venue}/slots?date=YYYY-MM-DD&duration=60`

**Dependencies:** T-031, T-030 (opening hours)

**Files affected:**
- `app/Services/Booking/SlotAvailabilityService.php`
- `app/Http/Controllers/Api/V1/SlotController.php`

**Tests required:**
- Unit test: venue open 08:00–22:00, duration 60 → 14 slots
- Unit test: confirmed booking at 10:00 → that slot excluded
- Unit test: active reservation at 10:00 → that slot excluded
- Unit test: expired reservation at 10:00 → slot included
- Unit test: no tier for requested duration → all slots excluded
- Unit test: date in the past → 422 validation error
- Feature test: concurrent requests for slots → correct availability returned

**Acceptance criteria:**
- Venue open 08:00–22:00, no bookings, duration=60: exactly 14 slots returned
- Each slot has: `start_time`, `end_time`, `price` (from tier), `duration_minutes`
- Past date request → 422
- Slot with active reservation (`reserved_until > NOW()`) → not in response
- Slot with expired reservation → in response

**Done definition:** Slot algorithm correct for all edge cases. Unit tests green.

---

### T-038 — Slot Reservation (10-min Lock)

**Why:** Prevents double-booking between slot selection and payment completion.

**Scope:**
- Created automatically by payment initiation (T-055), not a separate endpoint
- `slot_reservations` INSERT with `reserved_until = NOW() + 10 min`
- `UNIQUE(venue_id, booking_date, start_time)` enforced at DB level
- On collision: DB throws unique violation → caught → return `SLOT_UNAVAILABLE`
- `SlotReservationService::reserve($venueId, $date, $startTime, $endTime, $duration, $userId, $paymentId)`

**Dependencies:** T-036, T-003

**Files affected:**
- `app/Services/Booking/SlotReservationService.php`
- `app/Models/SlotReservation.php`

**Tests required:**
- Unit test: reserve → record created with correct `reserved_until`
- Feature test (concurrency): two simultaneous reserve calls for same slot → first succeeds, second gets `SLOT_UNAVAILABLE`

**Acceptance criteria:**
- `slot_reservations` record created with `reserved_until = NOW() + 10 min`
- Concurrent second request for same slot: `SLOT_UNAVAILABLE` error, one DB row only
- `reserved_until` reflects system setting (`slot_reservation_minutes`, default 10)

**Done definition:** UNIQUE constraint is the safety net. DB-level protection confirmed.

---

### T-039 — Reservations Expire Scheduler

**Why:** Expired reservations must be cleaned to release slots.

**Scope:**
- `php artisan reservations:expire`
- `DELETE FROM slot_reservations WHERE reserved_until < NOW()`
- Scheduled: every 1 minute via `Schedule::command('reservations:expire')->everyMinute()`

**Dependencies:** T-038

**Files affected:**
- `app/Console/Commands/ReservationsExpireCommand.php`
- `routes/console.php` (or `bootstrap/app.php` schedule)

**Tests required:**
- Unit test: command deletes expired records, preserves active records
- Feature test: slot reserved, wait past TTL (fake time), run command → slot available again

**Acceptance criteria:**
- Command deletes only records WHERE `reserved_until < NOW()`
- Record with `reserved_until > NOW()` not deleted
- After command: slot endpoint returns the previously reserved slot as available

**Done definition:** Scheduler command testable and correct.

---

## Phase 5 — Sprint 5A: OTP Payment Providers

---

### T-052 — PaymentGateway Interface + Registry

**Why:** Four providers need unified interface. Registry resolves provider by key.

**Scope:**
- `App\Contracts\PaymentGateway` interface:
  - `initiate(Booking $booking, array $params): PaymentInitiationResult`
  - `confirm(Payment $payment, string $otp): PaymentConfirmResult` (OTP providers only)
  - `handleCallback(array $payload): PaymentCallbackResult` (WebView providers only)
  - `resendOtp(Payment $payment): void` (Syriatel only)
- `PaymentGatewayRegistry` — resolves by `provider_key`
- `PaymentInitiationResult` DTO: `{payment_id, flow_type, amount, ...provider_specific}`

**Dependencies:** T-003

**Files affected:**
- `app/Contracts/PaymentGateway.php`
- `app/Services/Payment/PaymentGatewayRegistry.php`
- `app/DTOs/Payment/PaymentInitiationResult.php`

**Tests required:**
- Unit test: registry returns correct gateway class for each provider key
- Unit test: unknown provider key throws exception

**Acceptance criteria:**
- `PaymentGatewayRegistry::resolve('mtn_cash')` returns `MtnCashGateway` instance
- `PaymentGatewayRegistry::resolve('invalid')` throws `InvalidPaymentProviderException`

**Done definition:** Interface and registry in place. All gateways can be tested independently.

---

### T-053 — MtnCashGateway

**Why:** MTN Cash is the 3-step OTP provider. RSA signature required on every request.

**Scope:**
- `createInvoice($invoiceId, $amount)` → POST to MTN, returns `invoiceId`
- `initiatePayment($invoiceId, $phone)` → returns `{guid, operationNumber}`
- `confirmPayment($guid, $otp_hashed, $phone, $invoiceId, $operationNumber)` → success/fail
- RSA signature: `X-Signature` header using `storage/keys/private.pem`
- OTP hash for confirm: `base64_encode(hash('sha256', $otp, true))`
- HTTP client: `verify: false` for MTN self-signed cert
- `provider_meta` encrypted: stores `{guid, phone, invoiceId, operationNumber}`

**Dependencies:** T-052

**Files affected:**
- `app/Services/Payment/Gateways/MtnCashGateway.php`

**Tests required:**
- Unit test: RSA signature generation (mock key, verify format)
- Unit test: OTP hash calculation matches expected format
- Feature test (mock HTTP): initiate → confirm success → payment completed
- Feature test (mock HTTP): confirm with wrong OTP → payment failed

**Acceptance criteria:**
- `confirmPayment` hash: `base64_encode(hash('sha256', $otp, true))` — not plain OTP
- `provider_meta` in DB is encrypted (not readable as plain JSON)
- SSL verification disabled: `['verify' => false]` in HTTP client options
- MTN HTTP base URL configurable via `services.mtn_cash.url` config key

**Done definition:** MTN gateway unit + integration tested with HTTP mock.

---

### T-068 — CommissionService

**Why:** Commission must be calculated identically on every booking. Always deducted from club.

**Scope:**
- `CommissionService::resolveConfig($venueId)`:
  - Phase 1: only global scope → `commission_configs WHERE scope='global' AND is_active=1 ORDER BY effective_from DESC LIMIT 1`
- `CommissionService::calculate($venuePrice, CommissionConfig $config)`:
  - `commission_type = 'fixed'`: `commission_amount = config->commission_value`
  - `commission_type = 'percentage'`: `commission_amount = round($venuePrice * $config->commission_value / 10000)`
  - `total_price = $venuePrice` (always)
  - `club_payout_amount = $venuePrice - $commission_amount`
- Returns DTO with all four fields

**Dependencies:** T-003

**Files affected:**
- `app/Services/Payment/CommissionService.php`
- `app/DTOs/Payment/CommissionResult.php`

**Tests required:**
- Unit test: 7% on 350,000 → commission = 24,500, payout = 325,500, total = 350,000
- Unit test: fixed 10,000 SYP → commission = 10,000, payout = venue_price - 10,000
- Unit test: no active config → exception thrown (not silent)

**Acceptance criteria:**
- 7% commission on 350,000: `commission_amount = 24,500`, `club_payout_amount = 325,500`, `total_price = 350,000`
- `total_price` always equals `venue_price` (player never pays more)
- `apply_as` not referenced anywhere in CommissionService

**Done definition:** Unit tests green. Commission always deducted from club. Never from player.

---

## Phase 6 — Sprint 6B: Notifications

---

### T-094 — Custom FcmService

**Why:** FCM notifications to mobile and PWA. No wrapper package. HTTP v1 API.

**Scope:**
- `app/Services/Notification/FcmService.php`
- Auth: Google OAuth 2.0 service account → access token
- Service account JSON: `storage/app/fcm-service-account.json` (gitignored)
- Access token cached 55 min (file cache)
- `send($fcmToken, $title, $body, $data)`:
  - POST to `https://fcm.googleapis.com/v1/projects/{project_id}/messages:send`
  - Skip if `$fcmToken === null`
- Returns success/failure, logs failures

**Dependencies:** T-003

**Files affected:**
- `app/Services/Notification/FcmService.php`
- `config/services.php` (`fcm.project_id`)
- `storage/app/fcm-service-account.json` (gitignored)

**Tests required:**
- Unit test (mock HTTP): send to valid token → POST sent with correct structure
- Unit test: `fcm_token = NULL` → no HTTP call made
- Unit test: access token cached → second send reuses token (one HTTP call for token)

**Acceptance criteria:**
- FCM HTTP call uses `Authorization: Bearer {access_token}` header
- `fcm_token = NULL` → no HTTP call, no error
- Failed FCM send: logged to `laravel.log`, does not throw exception (jobs retry)
- Service account JSON path configurable via `config/services.php`

**Done definition:** FCM service functional. Token caching verified.

