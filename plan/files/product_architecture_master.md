# Product Architecture Master Document
> Sports Venue Booking Application
> Version: 2.0 — Clean Start
> Last Updated: Session Start
> Status: Active Working Draft

---

## 1. Project Scope Summary

### What is this system?
A **Sports Venue Booking Platform** that allows **players (لاعبين)** to discover, browse, and book sports venues (football fields, basketball courts, tennis courts, and other sport categories) through a mobile application. The platform is managed entirely through a web-based Admin Dashboard that controls all content, configurations, categories, venues, bookings, payments, and system behavior dynamically.

### Core Philosophy
- **Everything is dynamic:** All content visible in the mobile app is controlled and editable from the Admin Dashboard.
- **Backend + Admin Dashboard only:** This team builds the API layer and the Admin Panel. The mobile app is a separate concern — we design APIs that serve it cleanly.
- **API-first design:** Every piece of data, every configuration, every content block must be served through a well-structured, versioned API.

### Confirmed Tech Stack

| Layer | Technology | Status |
|---|---|---|
| Backend Framework | Laravel 13 | ✅ Confirmed |
| Permissions | Spatie Laravel Permissions | ✅ Confirmed |
| Dashboard Frontend | Inertia.js + Vue 3 + Tailwind CSS 4 + shadcn-vue | ✅ Confirmed |
| Dashboard Maps | Leaflet.js + OpenStreetMap (free, no API key) | ✅ Confirmed |
| Mobile Maps | Google Maps SDK (mobile team's concern) | ✅ Confirmed |
| PWA (Club Dashboard) | Vite PWA Plugin + laravel-pwa package | ✅ Confirmed |
| Deployment | Docker | ✅ Confirmed |
| Development Tool | Claude Code | ✅ Confirmed |
| Database | MySQL | ✅ Confirmed |
| Cache / Queue | Laravel File Cache + Database Queue | ✅ Confirmed |
| File Storage | Local (Laravel storage/public) | ✅ Confirmed |
| External Sports API | football-data.org (free tier) | ✅ Confirmed |
| Push Notifications | Firebase Cloud Messaging (FCM) — official HTTP v1 API | ✅ Confirmed |
| Google Auth Verify | Firebase Auth — official JWT verification (no kreait) | ✅ Confirmed |
| SMS OTP Auth | Syriatel OTP Service + MTN (primary) + WhatsApp via Baileys (optional, unofficial) | ✅ Confirmed |
| Payment: MTN Cash | RSA-signed HTTP API — `cashmobile.mtnsyr.com:9000` | ✅ Confirmed |
| Payment: Syriatel Cash | Token-based HTTP API — OTP 2-step flow | ✅ Confirmed |
| Payment: Fatora | Hosted page (WebView) — multi-bank card gateway | ✅ Confirmed |
| Payment: SamaPay | Same as Fatora — different credentials only | ✅ Confirmed |
| Asset Bundler | Vite (built into Laravel 13) | ✅ Confirmed |
| API Auth | Laravel Sanctum (multi-guard) | ✅ Confirmed |
| 2FA | Custom TotpService — RFC 6238 + PHP native (no package) | ✅ Confirmed |
| Debugging | Laravel Telescope (dev only) | ✅ Confirmed |
| Backup | spatie/laravel-backup | ✅ Confirmed |
| API Query | spatie/laravel-query-builder | ✅ Confirmed |
| Activity Log | spatie/laravel-activitylog | ✅ Confirmed |
| Phone Validation | propaganistas/laravel-phone | ✅ Confirmed |
| State Machine | spatie/laravel-model-states | ✅ Confirmed |

### System Components
| Component | Responsibility | Built By This Team |
|---|---|---|
| Backend API | All business logic, data, auth, payments | Yes |
| Admin Dashboard (Web) | Full system control panel | Yes |
| Mobile App — Flutter (iOS/Android) | End-user interface (اللاعب) | No — API consumer only |

### Confirmed Sport Categories (Initial)
- Football (كرة القدم)
- Basketball (كرة السلة)
- Tennis (تنس)
- Others — Admin can add any category from the Dashboard

### Geographic Scope
- Syria-focused (payment providers confirm this)
- Multi-city support assumed

---

## 2. Locked Decisions

### LD-041 — Booking Flow: Immediate Confirmation After Payment (No Pending Hold)
- **Decision:** The booking flow is: Select slot → Pay → Confirmed immediately. There is no "hold slot then pay later" flow. Payment is required to complete the booking. Booking status jumps from initiated to `confirmed` only after successful payment.
- **Reason:** Explicitly confirmed — "بنقي الوقت، بيدفع، وبينحجز فوراً."
- **Impact:**
  - No `pending` booking that holds a slot without payment.
  - Slot remains available until payment is confirmed — race condition risk must be handled (DB transaction + lock).
  - On payment success → Booking status = `confirmed` → FCM notification dispatched immediately to Admin.
  - On payment failure → No booking record persisted (or marked `failed` and slot released).
  - Mobile flow: Club Detail → Grounds List → Venue Detail → Slot Selection → Payment → Confirmation.

### LD-042 — Admin/Club Dashboard: Manual Booking (Blocked Slots)
- **Decision:** Club Admin and Club Data Entry can add manual bookings from the Club Dashboard. These represent external/offline bookings or blocked periods (maintenance, private events). They block the slot from being booked by mobile users.
- **Reason:** Explicitly confirmed — "عنا إيا حدا من Dashboard النادي بضيف الحجوزات الموجودة أو إيا حجز يدوي."
- **Impact:**
  - Manual bookings are stored in the same `bookings` table with `source: manual` flag.
  - Manual bookings have no associated payment record.
  - Manual bookings block the slot in availability checks — slot query must exclude ALL booking types not just mobile ones.
  - Types of manual bookings: `external` (real person booked offline), `blocked` (maintenance, closed period).
  - Club Admin can create, edit, delete manual bookings for their own venues.
  - Mobile slot availability API must filter out all booked slots regardless of source.

### LD-043 — FCM Notification to Admin on New Booking
- **Decision:** When a booking is confirmed (payment successful), an immediate FCM push notification is sent to the Super Admin and the Club Admin of the booked venue.
- **Reason:** Explicitly confirmed — "بيجي إشعار فوري للأدمن، اربطها مع Firebase FCM token."
- **Impact:**
  - `AdminUser` and `ClubStaffMember` (club_admin role) must store `fcm_token` field.
  - On booking confirmation → dispatch `BookingConfirmedAdminNotification` job to database queue.
  - Job sends FCM to: Super Admin tokens + Club Admin tokens for that specific club.
  - FCM payload: `{ booking_code, venue_name, club_name, user_name, date, time, amount }`.
  - Token management: Admin/Club Admin updates their FCM token on Dashboard login (PWA).
  - Also send FCM to the booking user (confirmation notification).

### LD-044 — Slot Soft Lock: 10 Minutes Reservation Window
- **Decision:** When a player initiates payment for a slot, that slot is soft-locked for 10 minutes. If payment is not completed within 10 minutes, the lock is released and the slot becomes available again. No booking record is created during the lock — only a temporary reservation.
- **Reason:** Confirmed — race condition protection, 10-minute window approved.
- **Impact:**
  - New table `slot_reservations` (or column on a temporary reservations model):
    `id, venue_id, booking_date, start_time, end_time, duration_minutes, sport_category_id, user_id, payment_id, reserved_until, created_at`
  - Slot availability query must exclude slots with active reservation: `WHERE reserved_until > NOW()`
  - Laravel Scheduler: `php artisan reservations:expire` runs every minute — releases expired locks
  - On payment success → `slot_reservation` is deleted + `booking` is created (confirmed)
  - On payment failure / timeout → `slot_reservation` is deleted, no booking created
  - `reserved_until` TTL configurable via Settings (`slot_reservation_minutes`, default: 10)

### LD-045 — Internal Wallet + Partial Refund on Cancellation
- **Decision:** A Wallet system is built internally since Syrian payment providers do not support refunds. Upon cancellation of a paid booking:
  - The refunded amount = `booking.total_price - deduction_amount`
  - `deduction_amount` = calculated by Admin-configured policy (flat amount or percentage)
  - Refund goes to the player's internal Wallet balance
  - Player can use Wallet balance for future bookings
  - Club Admin can view cancellation/refund info for their venues (read-only)
  - Player must type "cancel" or "الغاء" (or "إلغاء" — case-insensitive, hamza-insensitive) to confirm cancellation
- **Cancellation confirmation rules:**
  - Accepted: "cancel", "CANCEL", "الغاء", "إلغاء", "الغاء" — all equivalent
  - Arabic normalization: strip hamza variants, lowercase comparison
  - This prevents accidental cancellations
- **Deduction Policy (Admin-controlled):**
  - `cancellation_deduction_type`: `percentage` or `flat`
  - `cancellation_deduction_value`: the amount or percentage
  - Configurable from `/admin/` Dashboard → BookingSettings
  - Club can VIEW the policy but cannot change it
- **Impact:**
  - New entity `Wallet` (one per user)
  - New entity `WalletTransaction` (ledger of all credits/debits)
  - Booking cancellation flow: validate → calculate deduction → create WalletTransaction (credit) → update booking status → notify player
  - Payment at booking: checks wallet balance first (if player wants to use wallet)
  - `payments.provider` gets new value: `wallet` for wallet payments

### LD-046 — Scheduled (Recurring) Booking
- **Decision:** Players can create recurring bookings (e.g., every Friday at 6 PM). Each occurrence is treated as an independent booking that requires separate manual payment. No automatic payment deduction.
- **Flow:**
  1. Player selects slot + sets recurrence (weekly/bi-weekly, for X occurrences or until date)
  2. System creates all future bookings with status `scheduled` (new status)
  3. Player receives notification before each occurrence: "حجزك المجدول يوم الجمعة — ادفع الآن"
  4. Player pays → booking becomes `confirmed` → normal rules apply
  5. If player doesn't pay within the payment window → booking auto-cancelled, NO deduction from wallet
  6. After first payment: if player cancels → normal cancellation rules apply (wallet deduction)
- **Impact:**
  - New booking status: `scheduled` — slot is reserved but not paid
  - New fields on `bookings`: `is_recurring`, `recurrence_pattern` (JSON), `recurrence_parent_id` (FK to first booking)
  - Slot availability: `scheduled` bookings DO block the slot (reserved)
  - New scheduler job: `SendScheduledBookingPaymentReminder` — runs daily
  - New scheduler job: `ExpireUnpaidScheduledBookings` — cancels unpaid scheduled bookings after window
  - `payment_window_hours` configurable from Admin (e.g., 24 hours before the booking time)
  - Cancellation of a `scheduled` (unpaid) booking = free, no deduction

### LD-047 — Account Linking: OTP Verification When Google Phone Matches Existing User
- **Decision:** If a player signs in with Google and the phone number extracted from Firebase already belongs to an existing OTP-registered account:
  1. Backend detects the match
  2. Returns a specific response: `{ "action": "link_required", "masked_phone": "+963*****XXX" }`
  3. Player is prompted: "هذا الرقم مرتبط بحساب موجود — أدخل رمز التحقق لربط الحسابين"
  4. Player receives OTP (via SMS or WhatsApp) → enters it → accounts are merged
  5. After merge: single account with both Google identity AND phone — player can use either to login
- **Impact:**
  - Auth flow has a new branch: `link_required` response type
  - Merge strategy: keep the older account's data (bookings, wallet, reviews) + add Google identity
  - `social_identities` table links Google UID to the merged user
  - No duplicate accounts allowed

### LD-048 — WhatsApp OTP via Baileys — Smart Detection + User Choice
- **Decision:** WhatsApp OTP uses Baileys (unofficial Node.js library). Flow is smart:
  1. Player enters phone number on login/register screen
  2. Backend checks via Baileys: **does this number have WhatsApp?**
  3. If YES → response includes `{ "whatsapp_available": true }` → Mobile shows two buttons: "إرسال عبر WhatsApp" + "إرسال عبر SMS"
  4. If NO → response includes `{ "whatsapp_available": false }` → OTP sent via SMS immediately, no choice shown
  5. Player picks their preferred channel → OTP dispatched accordingly

- **Why this UX is correct:**
  - Never asks the player to choose a channel that doesn't work for them
  - No confusion — if they don't have WhatsApp, they never see the option
  - Same as how Movo and other professional Syrian apps handle it

- **Architecture:**
  - Baileys runs as a **separate Node.js microservice** (Docker container)
  - `POST http://whatsapp-service:3000/check` → `{ "has_whatsapp": true/false }`
  - `POST http://whatsapp-service:3000/send` → sends OTP message
  - Laravel calls both endpoints via internal HTTP (`WhatsAppService` class)
  - If Baileys service is down → `whatsapp_available: false` returned → SMS fallback

- **API Impact:**
  ```
  POST /api/v1/auth/otp/request
  Body: { "phone_number": "+963XXXXXXXXX" }

  Response:
  {
    "verification_request_id": "uuid",
    "expires_in_seconds": 120,
    "resend_after_seconds": 60,
    "masked_destination": "+963*****XXX",
    "whatsapp_available": true | false,
    "default_channel": "whatsapp" | "sms"
  }
  // إذا whatsapp_available = false → OTP أُرسل فوراً عبر SMS
  // إذا whatsapp_available = true  → ينتظر اختيار المستخدم

  POST /api/v1/auth/otp/choose-channel  [جديد]
  Body: {
    "verification_request_id": "uuid",
    "channel": "whatsapp" | "sms"
  }
  Response: { "sent": true, "channel_used": "whatsapp" }
  ```

- **Risks (documented and accepted):**
  - Baileys = unofficial → WhatsApp number may be banned without notice
  - Baileys may break on WhatsApp Web protocol updates
  - **Mitigation:** If banned/broken → Baileys service returns error → Laravel returns `whatsapp_available: false` → SMS sent automatically. Zero player impact.

- **Admin Control:**
  - Admin can disable WhatsApp channel entirely from Dashboard (Settings toggle)
  - When disabled → `whatsapp_available` always returns `false` regardless of Baileys check

- **Impact on Schema:**
  - `otp_challenges.channel` (enum: `sms` / `whatsapp`) — which channel was used
  - No other schema changes



### LD-061 — Business Model: Commission-Based Revenue
- **Decision:** Platform takes a commission on every confirmed booking. Two commission modes supported:
  1. **Added Fee:** Player pays `venue_price + commission`. Club receives full `venue_price`. Commission = platform revenue.
  2. **Deducted Fee:** Player pays `venue_price`. Club receives `venue_price - commission`. Commission = platform revenue.
- **Commission Policy (Admin-controlled):**
  - `commission_type`: `added` | `deducted`
  - `commission_calculation`: `flat` | `percentage`
  - `commission_value`: amount or percentage
  - Also: **cancellation commission** — platform keeps a flat fee from the cancellation deduction (e.g., 25,000 SYP)
- **Impact on `bookings` table:**
  - `venue_price` — original venue price (from pricing tier, snapshot)
  - `commission_amount` — platform commission for this booking (snapshot at creation)
  - `commission_type` — snapshot of which mode was used
  - `total_price` — what player actually paid (venue_price + commission if added, or venue_price if deducted)
  - `club_payout_amount` — what club is owed (venue_price if added, venue_price - commission if deducted)
- **Impact on cancellations:**
  - `cancellation_commission` — platform keeps this from the deduction amount
  - Remaining deduction goes to platform, refund goes to player wallet
- **Revenue tracking:** Admin Dashboard shows: total revenue, commission earned, cancellation commissions, net per period.

### LD-062 — Club Settlement System
- **Decision:** Club owners are paid out periodically. The platform tracks:
  - How much each club has earned (sum of `club_payout_amount` from confirmed bookings)
  - How much has been paid (via `club_settlements` records)
  - Remaining balance owed to club
  - Club Dashboard shows their own settlement history — what they earned, what was paid, outstanding balance
- **Impact:**
  - New table `club_settlements`: records each payout from platform to club
  - `club_settlements.status`: `pending` | `paid` | `cancelled`
  - When admin marks as paid → `paid_at` + `paid_by` set
  - Club Dashboard: read-only view of their settlements

### LD-063 — Scalability: Generic Bookable Entity (Not Just Venues)
- **Decision:** The platform is designed to book ANY physical space — sports venues today, wedding halls, conference rooms, funeral halls, anything. The domain model must support this without schema changes.
- **How:** Introduce `venue_type` on venues (or a `categories` approach at the club level). The booking flow, pricing, and commission are identical regardless of venue type.
- **Impact:**
  - `clubs.category_type`: `sports` | `events` | `general` (nullable, default: `sports`) — for filtering and UI context
  - `venues.venue_type`: free-form text or future enum — e.g., "football", "basketball", "wedding_hall", "conference_room"
  - The sport_category_id on venues becomes NULLABLE for non-sports venues
  - `sport_category_id` on `bookings` also becomes nullable (no sport for a wedding hall booking)
  - No other schema changes needed — the rest of the system is already generic
  - Admin can create clubs of any type from Day 1

### LD-061 — Commission-Based Business Model
- **Decision:** The platform operates on a commission basis. For every booking:
  - `venue_price` = the venue's original price (from pricing tier)
  - `commission_amount` = platform cut (fixed SYP or percentage — Admin-configured)
  - `commission_type` = `added` (player pays extra) OR `deducted` (platform takes from venue price)
  - `total_price` = what the player actually pays
  - `club_payout_amount` = what the club receives

- **Two commission models:**
  1. **Added:** Player pays venue_price + commission. Club gets venue_price. Platform gets commission.
     - Example: Venue = 350,000 SYP. Commission = 5,000 SYP. Player pays 355,000 SYP. Club gets 350,000 SYP.
  2. **Deducted:** Player pays venue_price. Club gets venue_price - commission. Platform gets commission.
     - Example: Venue = 350,000 SYP. Commission = 5,000 SYP. Player pays 350,000 SYP. Club gets 345,000 SYP.

- **Cancellation fee:** Separate fixed amount platform keeps from cancellation deduction. Configurable per global/club/venue.
  - Example: Cancellation deduction = 25,000 SYP. This goes to platform, NOT to club.

- **Commission Config Resolution (most specific wins):**
  1. Venue-level config (if exists)
  2. Club-level config (if exists)
  3. Global config (fallback)

- **Impact:**
  - New table `commission_configs` — Admin-managed
  - `bookings` table stores snapshot of all amounts at booking time (immutable audit trail)
  - New fields on `bookings`: `venue_price`, `commission_amount`, `commission_type`, `total_price`, `club_payout_amount`, `cancellation_commission`
  - Admin Dashboard: Revenue report showing gross / commission / net per period
  - Club Dashboard: Shows bookings + what they are owed (club_payout_amount) + what was settled

### LD-062 — Settlement System (Platform Pays Club)
- **Decision:** Platform collects all payments from players. Periodically, Super Admin generates a settlement for each club and marks it paid after transferring money offline.
- **Flow:**
  1. Player pays → money goes to platform (via payment provider)
  2. Platform accumulates `club_payout_amount` per booking
  3. Super Admin generates settlement for club: SELECT all unsettled confirmed/completed bookings → compute totals
  4. Admin pays club offline (bank transfer, cash) → marks settlement as `paid` + enters reference
  5. Club Dashboard shows their settlements history (read-only)
- **Impact:**
  - New tables: `settlements` + `settlement_items`
  - `settlement_items` links specific bookings to a settlement — prevents double-counting
  - Admin Dashboard: Settlement management UI — create, review, mark paid
  - Club Dashboard: Read-only settlement history + "pending payout" balance

### LD-063 — Scalability: Venue is Category-Agnostic (Supports Any Bookable Space)
- **Decision:** The system is designed from the start to book ANY type of space, not just sports venues. `venue_categories` replaces `sport_categories` as the category system for venues. `sport_categories` remains for football matches display (Events tab only).
- **Reason:** Explicitly confirmed — "بسرعة هائلة بدنا نكبرها لتصير صالات أفراح وأتراح وأي شيء بينحجز."
- **Impact:**
  - New table `venue_categories` — generic categories (Sports, Wedding Halls, Mourning Halls, Conference Rooms, etc.)
  - `venues.sport_category_id` renamed to `venues.category_id` FK → `venue_categories`
  - `venue_categories` has `type` field to distinguish sports vs non-sports
  - `sport_categories` kept ONLY for the Events tab (football matches display)
  - Booking flow adapts: if category type = sports → show sport-specific UI; else → generic booking UI
  - `bookings.sport_category_id` becomes nullable — non-sports bookings have NULL
  - Admin can add any category type from Dashboard
  - Mobile app: "Browse by Category" shows all category types — sports, halls, courts, etc.

### LD-060 — Club Staff Assignment: Dynamic via club_user Pivot (Not Single FK)
- **Decision:** Club staff can be assigned to multiple clubs dynamically. No `users.club_id` column. Assignment managed via `club_user` pivot table from Admin Dashboard.
- **Reason:** Explicitly confirmed — "خليها dynamic ما تكون ثابتة لواحد دايما، بيقدر يتحكم فيها من dashboard الأدمن العام."
- **Impact:**
  - New table `club_user` (club_id, user_id, UNIQUE)
  - Admin Dashboard: Super Admin assigns/removes staff from any number of clubs at runtime
  - Spatie permissions still control WHAT the staff can do — `club_user` controls WHERE
  - FCM notification targeting: `JOIN club_user WHERE club_id = X` to find staff to notify
  - Club scoping in Club Dashboard: `auth()->user()->clubs` → returns all clubs via pivot


### LD-064 — Geographic Data Source: nnjeim/world Package (No Manual Entry)
- **Decision:** Geographic data (countries, states/provinces, cities) is sourced from the `nnjeim/world` Laravel package, NOT entered manually by Admin. Admin Dashboard uses the package's pre-seeded data to select which countries, states, and cities to activate for the platform. The package data is the canonical source; the Admin controls visibility only.
- **Reason:** Explicitly confirmed — "من داشبورد الأدمن أنقي اللي بدي وأرسله للتطبيق. ما أخلي التطبيق يعمل اللي هو بده أو يجيب أي شيء. أنا بحدد بالزبط شو المدن اللي بدي."
- **Package:** `nnjeim/world` (https://github.com/nnjeim/world)
  - Countries, States, Cities — all pre-seeded from authoritative international data
  - Arabic locale (ar) supported
  - Phone country codes included
  - ISO2/ISO3 codes included
  - Filterable seeding: `allowed_countries` config
- **Setup:**
  ```bash
  composer require nnjeim/world
  php artisan vendor:publish --tag=world
  php artisan migrate
  php artisan db:seed --class=WorldSeeder  # ~15 minutes
  ```
- **Schema Impact:**
  - Drop custom `cities` table — replaced by `world_cities` (package table)
  - Drop custom `areas` table — replaced by `world_states` (package table)
  - Add `is_active TINYINT(1) DEFAULT 0` to `world_countries`, `world_states`, `world_cities`
  - `clubs.area_id` → FK to `world_cities.id` (or `world_states.id` depending on granularity)
  - `users.default_city_id` → FK to `world_cities.id`
  - `users.default_area_id` → FK to `world_states.id` (محافظة)
- **Admin Dashboard Flow:**
  1. Admin opens "Geographic Management"
  2. Sees all countries from package → activates Syria (toggle)
  3. Sees all Syrian states/provinces → activates: Damascus, Aleppo, Homs...
  4. Sees all cities per state → activates specific areas/neighborhoods
  5. Mobile app receives ONLY activated cities/areas
- **Mobile API:** Returns only `is_active = 1` records — Admin controls the list completely
- **Country Code for OTP:** Sourced from `world_countries.phone_code` — no hardcoding of +963

### LD-051 — Single Users Table for Everyone (No Separate admin_users)
- **Decision:** One `users` table for ALL actors: players, club staff, super admins, data entry. Role separation is handled entirely via Spatie Permissions guards and roles — not separate tables.
- **Reason:** Explicitly confirmed — "مابدي table admin_users، بدي خلص table users يكون فيها اللاعبين وادارة النادي وكله مدار عبر Spatie."
- **Impact:**
  - `users` table is the single identity table for the entire system
  - No `admin_users`, no `club_staff_members` tables
  - Guards: `api` for mobile players, `web` for dashboard users (both from same `users` table)
  - Spatie roles determine what each user can do and where they can go
  - `users.guard_name` or role guard determines access level
  - All FCM tokens on `users.fcm_token` — applies to players AND dashboard staff

### LD-052 — Dashboard Auth: Simple Email + Password (No 2FA for Club Staff)
- **Decision:** 
  - **Club Dashboard:** Email + password only. No 2FA. Simple login — same UX simplicity as the player app. Club staff may not be tech-savvy.
  - **Super Admin Dashboard:** Email + password + mandatory 2FA (TOTP via Google Authenticator or similar). No exceptions.
  - **Other admin roles (data entry, etc.):** Email + password, no 2FA.
- **Reason:** "ادارة النادي ممكن يكونو جاهلين تقنياً — بدنا شيء بسيط. أما Super Admin فأكيد في 2FA."
- **Dashboard uses a Starter Kit** — existing Laravel starter kit handles the auth UI.
- **Impact:**
  - `users.google2fa_secret` (encrypted VARCHAR, nullable) + `users.google2fa_enabled_at` nullable columns — only set for super admins
  - **No external package** — custom `TotpService` class using PHP native functions only:
    - `hash_hmac('sha1', ...)` — PHP built-in (php.net/manual/en/function.hash-hmac.php)
    - Base32 decode — written manually (RFC 3548)
    - QR code URL: `otpauth://totp/{app}:{email}?secret={secret}&issuer={app}` — standard format
    - QR code image: use a simple SVG generator or pass the URL to a frontend QR library
  - `TotpService::generateSecret()` — `random_bytes(20)` encoded as Base32
  - `TotpService::verify($secret, $code, $window = 1)` — checks current + ±1 time windows for clock skew
  - Middleware: custom `EnsureTwoFactorAuthenticated` on all `/admin/*` routes
  - Setup flow: Admin scans QR code → enters 6-digit code → `google2fa_enabled_at` set
  - Login flow: email+password → redirect to 2FA page → enter TOTP code → session flag set
  - Source: RFC 6238 (https://www.rfc-editor.org/rfc/rfc6238) + PHP manual hash_hmac
  - Club staff created by Super Admin → receive temp password via SMS
  - Password reset via SMS OTP (same Syriatel/MTN infrastructure) — no email reset
  - Dashboard routes protected by `auth:web` middleware + Spatie permission check per route

### LD-053 — All Routes Protected by Spatie Permission Middleware
- **Decision:** Every dashboard route requires a specific permission to access. Using Spatie's `permission` middleware on all routes — no route is accessible without an explicit permission assignment.
- **Reason:** "بتذكر إنو بالـ Spatie permissions في middleware إذا حطيته بصير كل route إجباري بده permission عشان تفوت عليه — بدنا إياه."
- **Impact:**
  ```php
  // routes/admin.php
  Route::middleware(['auth:web', 'permission:admin.clubs.view'])->get('/clubs', ...);
  Route::middleware(['auth:web', 'permission:admin.clubs.create'])->post('/clubs', ...);
  
  // routes/club.php  
  Route::middleware(['auth:web', 'permission:club.bookings.view'])->get('/bookings', ...);
  ```
  - Permission naming convention: `{guard}.{resource}.{action}`
    - Admin: `admin.clubs.view`, `admin.clubs.create`, `admin.venues.edit`, `admin.bookings.manage`, `admin.users.manage`, `admin.settings.manage`, `admin.roles.manage`, `admin.payments.view`, `admin.reports.export`
    - Club: `club.bookings.view`, `club.bookings.create_manual`, `club.venues.view`, `club.venues.edit`, `club.pricing.manage`, `club.reports.view`
  - Super Admin role has ALL permissions assigned automatically on seed
  - Roles are seeded initially but fully manageable from Dashboard at runtime

### LD-054 — Venue Supports ONE Sport Only (No Multi-Sport per Venue)
- **Decision:** A venue is assigned exactly ONE sport category. The many-to-many `venue_sport_categories` pivot table is removed.
- **Reason:** Explicitly confirmed — "اعمل قرار إنو ما في ملعب فيه أكتر من رياضة."
- **Impact:**
  - `venues.sport_category_id` — single FK, not nullable, directly on venues table
  - Remove `venue_sport_categories` pivot table from schema
  - In Slot Selection → sport is auto-selected from venue — player never chooses sport manually
  - Booking record captures `sport_category_id` copied from `venue.sport_category_id` at booking time
  - Filter by sport: `WHERE venues.sport_category_id = X` — simple indexed query

### LD-055 — VenuePricingTier: Time-Based Pricing (Day + Time Range)
- **Decision:** Pricing is based on opening_hours tiers — each tier defines a day pattern + time range + price. Multiple tiers per venue for different time slots.
- **Reason:** Explicitly confirmed — "يوم الجمعة من الساعة كذا للساعة كذا بالملعب الكذا سعرها كذا، بيوم تاني أو بساعة تانية أو بملعب تاني غير شيء."
- **Tier Structure:**
  ```
  venue_pricing_tiers:
    id
    venue_id (FK)
    name (json translatable — e.g., "Morning Rate", "Friday Evening")
    day_type (enum: weekday | weekend | friday | specific_day)
    specific_day (enum nullable: saturday|sunday|monday|...|friday — used when day_type=specific_day)
    start_time (time — e.g., "08:00")
    end_time   (time — e.g., "14:00")
    duration_minutes (FK → venue_allowed_durations or just integer)
    price (integer, SYP)
    is_active (boolean)
    order_column (for Spatie sortable)
  ```
- **Slot price resolution:** When generating slots for a date, system finds the matching tier:
  - Match by day_type (is it a weekday? weekend? friday?)
  - Match by time range (does the slot start_time fall within tier start_time→end_time?)
  - If no tier matches → slot is not bookable (no price = unavailable)
- **Admin Dashboard:** Visual pricing grid per venue — easy to add "Friday 16:00-22:00 = 80,000 SYP"
- **Impact:**
  - Replaces any flat `price_per_hour` approach
  - `venues.price_from` cached = MIN(active tiers price) for this venue

### LD-056 — Cancellation: Allowed Anytime Except Last 30 Minutes + Vacancy Notification
- **Decision:**
  - Cancellation is allowed at any time EXCEPT when less than 30 minutes remain before booking start time
  - If < 30 min → cancellation is blocked. Player sees: "لا يمكن الإلغاء قبل أقل من 30 دقيقة من الموعد"
  - The 30-minute threshold is configurable from Admin Dashboard (BookingSettings)
  - **Added Value Feature:** Upon cancellation → immediate push notification sent to nearby players: "ملعب [X] أصبح متاحاً الآن — احجز خلال 30 دقيقة!"
  - Nearby = players who have saved this venue OR players in same area with matching sport preference
- **Refund on cancellation:** Per LD-045 — wallet refund minus Admin-configured deduction
- **Impact:**
  - Cancellation API checks: `booking.start_datetime - NOW() >= 30 minutes`
  - `cancellation_min_minutes_before` in BookingSettings (default: 30)
  - New notification job: `VenueAvailableNotificationJob` — finds nearby/saved players → dispatches FCM
  - `users.default_area_id` + `saved_venues` used to find notification targets
  - Notification payload: `{ venue_name, club_name, date, time, price, action: "book_now" }`

### LD-057 — Reviews: Linked to Club (Not Venue), Written by Players Who Booked That Club
- **Decision:**
  - Review is at **Club level** — not venue level
  - Only players who have AT LEAST ONE completed booking at ANY venue of that club can write a review
  - Review has: rating (1-5 stars), optional text body (min 50 chars if provided)
  - Player can hide their name (is_anonymous = true) — but system shows "لاعب حجز في [Venue Name]" to prove authenticity
  - Each player can write ONE review per club (not one per venue, not one per booking)
- **Reason:** Explicitly confirmed — "الـ review بتكون للنادي، الي بيكتب الـ review هو حصراً الي حاجز بمرة بالملعب هاد أو النادي هاد بشكل عام."
- **Impact:**
  - `reviews` table: `user_id`, `club_id`, `booking_id` (the specific booking that qualifies them), `rating`, `body`, `is_anonymous`, `venue_hint` (name of venue they booked — shown even when anonymous)
  - Remove: `reviews.venue_id` FK — reviews are on clubs now
  - `avg_rating` on Club = AVG of its own reviews directly (not computed from venues)
  - `avg_rating` on Venue = removed or kept as separate "venue quality" metric — TBD
  - Unique constraint: one review per `(user_id, club_id)`
  - Query to check eligibility: `SELECT 1 FROM bookings WHERE user_id=? AND venue.club_id=? AND status=completed`

### LD-058 — FCM on Logout: Token Must Be Cleared
- **Decision:** When a user logs out from ANY client (mobile app, club dashboard PWA, admin dashboard), their `fcm_token` field is set to NULL in the `users` table. No notifications sent to logged-out users.
- **Reason:** Explicitly confirmed — "أكيد مع الـ logout لازم حذف للـ FCM token."
- **Impact:**
  - Logout API: `POST /api/v1/auth/logout` → deletes Sanctum token + sets `users.fcm_token = NULL`
  - Dashboard logout: same — clears `users.fcm_token`
  - FCM notification jobs must skip users with `fcm_token IS NULL`

### LD-059 — FCM Notifications Target Both Mobile AND Dashboard PWA
- **Decision:** Push notifications are sent to ALL user types that have an FCM token:
  - Players → mobile FCM token (Flutter app)
  - Club Staff → PWA FCM token (Club Dashboard PWA)
  - Super Admin → PWA FCM token (Admin Dashboard PWA, if installed)
- **Reason:** Explicitly confirmed — "الإشعارات حتكون للـ PWA وللتطبيق، يعني بالأدمن بأنواعه وللاعبين."
- **PWA FCM Setup:** Dashboard PWA uses Firebase Web SDK to request notification permission and store FCM token → saved to `users.fcm_token` on Dashboard login/session start.
- **Single token field:** `users.fcm_token` handles both — the same FCM HTTP v1 API sends to any FCM token regardless of platform (Android/iOS/Web).
- **Impact:**
  - No separate token tables — one `fcm_token` column on `users` serves all platforms
  - FCM Web SDK must be initialized in Dashboard Vue app (admin + club)
  - Notification permission prompt shown on first Dashboard login
  - `users.fcm_platform` (enum: `mobile`/`web` nullable) — helps with notification formatting (web notifications have different display limits)

### LD-050 — Slots: Computed On-the-Fly (Not Pre-Generated in DB)
- **Decision:** Available time slots are computed dynamically per request — never stored as rows in DB.
- **Reason:** Pre-generating slots is wasteful (millions of rows), hard to maintain, and breaks with pricing/hours changes. Senior standard approach.
- **Algorithm:**
  ```
  1. Parse venue.opening_hours (spatie/opening-hours) for requested date
  2. Generate all possible slots: opening_time → closing_time, step = duration_minutes
  3. Exclude slots where:
     a. A confirmed/scheduled booking exists (bookings table)
     b. An active slot_reservation exists (slot_reservations table, reserved_until > NOW())
     c. A manual booking exists (source = manual)
  4. For each remaining slot → get price from VenuePricingTier matching time + duration
  5. Return available slots with prices
  ```
- **Impact:** No `slots` table. Slot availability is a pure computation. Fast for small venues (10-20 slots/day). MySQL query to exclude booked slots is simple indexed lookup.

### LD-049 — Booking Reminders: 2 Hours + 1 Hour Before
- **Decision:** Two FCM push notifications are sent before each confirmed booking:
  - First reminder: 2 hours before booking start time
  - Second reminder: 1 hour before booking start time
- **Reason:** Explicitly confirmed.
- **Impact:**
  - Laravel Scheduler runs every 15 minutes: finds bookings starting in ~2h and ~1h → dispatches reminder jobs
  - Respects `user.notifications_reminders_enabled` — if false, skips
  - Scheduled bookings (unpaid) also get a payment reminder — different notification type
  - `reminder_2h_sent_at` + `reminder_1h_sent_at` nullable timestamps on `bookings` to prevent duplicates

### LD-038 — Database: MySQL
- **Decision:** MySQL is the database engine.
- **Reason:** Confirmed by developer — matches hosting environment.
- **Impact:** All migrations use MySQL-compatible syntax. Geospatial queries use Haversine formula (no PostGIS). Use `utf8mb4` charset on all tables to support Arabic text.

### LD-039 — No Redis: Laravel File Cache + Database Queue
- **Decision:** No Redis. Cache uses Laravel's file driver. Queue uses Laravel's database driver.
- **Reason:** Explicitly confirmed — "لن نستخدم Redis."
- **Impact:**
  - `config/cache.php` → `driver: file`
  - `config/queue.php` → `driver: database` → requires `jobs` + `failed_jobs` tables (Laravel default)
  - Football API cache (5-min TTL): Laravel file cache — `Cache::remember('football_matches_today', 300, fn() => ...)`
  - FCM notifications: dispatched via database queue jobs
  - SMS OTP: dispatched via database queue jobs
  - Unpaid booking expiry: scheduled command via Laravel Scheduler (not queue-dependent)
  - **Trade-off noted:** File cache is not shared across multiple server instances. If scaling to multiple servers in future, this must be revisited. Acceptable for single-server deployment.

### LD-040 — File Storage: Local (Laravel storage/public)
- **Decision:** File storage uses Laravel's local disk (`storage/app/public` symlinked to `public/storage`).
- **Reason:** Explicitly confirmed — "File Storage: local."
- **Impact:**
  - `config/filesystems.php` → default disk: `public`
  - All uploaded files (club images, venue images, category icons, user avatars): stored locally
  - `php artisan storage:link` required on deployment
  - Docker volume must mount `storage/` to persist files across container restarts
  - No S3/CDN dependency — simpler setup, but files are tied to the server
  - **Trade-off noted:** Local storage doesn't scale horizontally. Acceptable for current deployment model.

### LD-037 — OTP Length: 5 Digits
- **Decision:** OTP code is 5 digits (not 6).
- **Reason:** Explicitly confirmed — aligned with existing codebase (`random_int(11111, 99999)`).
- **Impact:** All OTP input UIs use 5 boxes. All validation rules enforce exactly 5 digits. Range: 11111–99999.

### LD-001 — Authentication Methods
- **Decision:** User authentication supports two methods only: Mobile Phone OTP (SMS) and Google Sign-In.
- **Reason:** Explicitly confirmed. No email/password authentication.
- **Impact:** No password fields, no password hashing, no password reset flow anywhere in the system. Auth module supports OTP challenge flow and Google identity token verification only.

### LD-002 — SMS OTP Provider: Syriatel + MTN (Dual Provider, Abstracted)
> **Code Analysis Note (AuthController.php):**
> الكود القديم يستخدم `resendVerifyCode` → يستدعي `generatVerifyNumber()` → يستدعي `dispatchVerificationCode()` → يرسل عبر MTN أو Twilio.
> **المشاكل المكتشفة في الكود القديم التي يجب تصحيحها في مشروعنا:**
> 1. OTP = 5 أرقام (`random_int(11111, 99999)`) — ✅ هذا صحيح، نبقي 5 أرقام
> 2. مزود الإرسال = MTN SMS أو Twilio WhatsApp — يجب استبداله بـ **Syriatel OTP أو MTN SMS** (لا Twilio)
> 3. `code_number` مخزون plain text على جدول `users` — يجب نقله لجدول **`otp_challenges` منفصل** مع hashing
- **Decision:** SMS OTP delivery supports two providers: Syriatel OTP Service and MTN. Both are abstracted behind a unified `SmsOtpService` interface so the active provider can be switched without touching business logic. Working code from previous projects is already available for both.
- **Reason:** Confirmed — "ممكن يكون إرسال الـ OTP عن طريق Syriatel أو MTN."
- **Implementation (from existing code):**

  **Syriatel OTP:**
  - HTTP GET to Syriatel API URL with: `user_name`, `password`, `sender`, `to` (963+number), `template_code`, `param_list` (the OTP code)
  - Phone normalization: strip leading 0 → prepend 963
  - Templates: bilingual (AR/EN) — stored as template codes in `.env`
  - Config keys: `services.syriatel_otp.url`, `.user`, `.password`, `.from`

  **MTN OTP (via MtnCashService infrastructure):**
  - Uses RSA private key signature (`private.pem` in storage/keys/)
  - Headers: `Request-Name`, `Subject` (terminalId), `X-Signature`
  - Phone normalization: strip leading 0 → prepend 963
  - Config keys: `services.mtn_cash.terminal_id`

- **Abstraction:** Create `SmsOtpService` interface with `send(phone, code)` method. Config-driven provider selection. Admin can switch active SMS provider from Dashboard via SystemSettings.
- **Three providers:** Syriatel (primary) + MTN (primary) + WhatsApp/Baileys (optional — LD-048).
- **Impact:** OTP generation stays on backend. SMS delivery is pluggable. Both providers tested from existing implementations.

### LD-003 — Payment Providers: MTN Cash + Syriatel Cash + Fatora + SamaPay
- **Decision:** Four payment providers are supported. All are abstracted behind a unified `PaymentGateway` interface.
- **Reason:** Explicitly confirmed. Working code from previous projects available for all providers.

#### Provider 1: MTN Cash
- **Flow (3-step OTP):**
  1. `createInvoice(invoiceId, amount)` → MTN returns `invoiceId`
  2. `initiatePayment(invoiceId, phone)` → MTN sends OTP to customer + returns `guid` + `operationNumber`
  3. Customer enters OTP → `confirmPayment(guid, otp, phone, invoiceId, operationNumber)` → MTN confirms
- **Auth:** RSA signature (`private.pem`) on every request — `X-Signature` header
- **Base URL:** `https://cashmobile.mtnsyr.com:9000`
- **Phone format:** strip leading 0 → prepend 963
- **OTP hash:** `base64_encode(hash('sha256', $otp, true))` before sending to confirm
- **Config:** `services.mtn_cash.terminal_id`, `storage_path('keys/private.pem')`
- **SSL:** `verify: false` (self-signed cert on MTN side)
- **Stored state:** `guid`, `phone`, `invoiceId`, `operationNumber` on Payment record

#### Provider 2: Syriatel Cash
- **Flow (2-step OTP):**
  1. `paymentRequest(customerMsisdn, amount, transactionId)` → Syriatel sends OTP to customer
  2. Customer enters OTP → `paymentConfirmation(otp, transactionId)` → Syriatel confirms
  3. `resendOTP(transactionId)` available for resend
- **Auth:** Token-based — `getToken(username, password)` → cached 3 minutes
- **Phone format:** raw customerMsisdn as provided
- **Config:** `services.syriatel_cash.username`, `.password`, `.merchant_msisdn`, `.url`
- **Error handling:** `errorCode == 0 AND errorDesc == 'Success'` = confirmed
- **Stored state:** `transaction_id` on Payment record

#### Provider 3: Fatora (Bank Gateway — Multi-Bank)
- **Nature:** Hosted payment page — NOT an OTP flow. User enters card number + expiry + OTP (sent by bank via SMS to cardholder's phone)
- **Supporting banks (from screenshot):** البركة، بنك سورية والخليج، شارب، بنك سوريا الدولي الإسلامي
- **Flow:**
  1. Backend builds signed form payload → returns hosted page URL to mobile
  2. Mobile opens WebView to Fatora's hosted page
  3. User enters card details + bank OTP on Fatora's page
  4. Fatora calls backend `callBackUrl` with result (`transactionStat`, `idTransaction`)
  5. Backend responds: `{"responseCode":"OK"}` or `{"responseCode":"KO"}`
- **Hosted endpoint:** `https://tecom.albaraka.com.sy:8433/ss-ecom-merchant-kit/buyForm/completeTransaction`
- **Key payload fields:** `pspId`, `mpiId`, `cardAcceptor`, `mcc`, `merchantKitId`, `authenticationToken`, `transactionAmount`, `transactionReference`, `redirectBackUrl`, `callBackUrl`
- **Constants:** `currency=SYP`, `countryCode=SYR`, `transactionTypeIndicator=SS`
- **Config:** `services.fatora.*` (pspId, mpiId, merchantKitId, authenticationToken, etc.)

#### Provider 4: SamaPay
- **Nature:** Identical to Fatora — same developer, same hosted page architecture, same flow
- **Difference:** Different credentials only (`pspId`, `mpiId`, `merchantKitId`, `authenticationToken`, hosted URL)
- **Implementation:** Reuse Fatora adapter class with different config namespace `services.sama_pay.*`

#### Unified Payment Architecture
```
PaymentGateway (interface)
  ├── initiate(booking, phone?) → PaymentInitiationResult
  ├── confirm(payment, otp?) → PaymentConfirmResult  [MTN + Syriatel only]
  ├── handleCallback(payload) → PaymentCallbackResult  [Fatora + SamaPay only]
  └── resendOtp(payment) → void  [Syriatel only]

Implementations:
  MtnCashGateway
  SyriatelCashGateway
  FatoraGateway
  SamaPayGateway  (extends/wraps FatoraGateway with different config)
```

- **Mobile flow difference:**
  - MTN/Syriatel: mobile shows OTP input screen → calls `POST /api/v1/payments/{id}/confirm`
  - Fatora/SamaPay: mobile opens WebView → Fatora handles everything → callback hits backend
- **Impact:** Payment record stores `provider`, `provider_payload` (JSON), `status`. Mobile needs to handle two distinct UX flows based on provider type returned from initiate response.

### LD-004 — Admin Controls Everything
- **Decision:** The Admin Dashboard must allow editing of virtually all system data and configurations — content, categories, venues, pricing, bookings, users, payments, and system settings.
- **Reason:** Explicitly confirmed ("كلشي كلشي كلشي").
- **Impact:** Nearly every domain entity needs admin CRUD. Settings must be stored as configurable records, not hardcoded values. Feature flags and content blocks must be database-driven.

### LD-005 — Dynamic Sport Categories
- **Decision:** Sport categories are not hardcoded. Admin can add, edit, or remove any sport category from the Dashboard at any time.
- **Reason:** Explicitly confirmed. Admin drives category structure.
- **Impact:** Category entity must be a first-class database model. Venues belong to categories dynamically. Mobile app fetches categories from API — never assumes a fixed list.

### LD-006 — API-First, Mobile-Ready
- **Decision:** All APIs must be designed to serve a mobile application cleanly, even though this team does not build the mobile app.
- **Reason:** The mobile team will consume these APIs. Design must account for pagination, lightweight responses, enums/constants, filter/search support, and clear error contracts.
- **Impact:** Every API endpoint must consider mobile consumption patterns. Response shapes must be lean and consistent.

### LD-007 — No Email Authentication
- **Decision:** Email-based login is explicitly excluded.
- **Reason:** Confirmed by developer.
- **Impact:** No email field required for authentication. Email may still be collected as optional profile data but is never used as a login identifier.

### LD-008 — Club → Venue Hierarchy (Two-Level Structure)
- **Decision:** The core data model is a two-level hierarchy: Club (نادي) contains one or more Venues (ملاعب). The Club is the top-level entity users discover. Each Venue inside the Club is a specific physical court/field assigned to one or more sports.
- **Reason:** Explicitly confirmed — "اسمه بالغالب نادي، بقلب النادي في عدة ملاعب، كل ملعب لرياضة معينة."
- **Impact:**
  - New first-class entity `Club` added to domain model.
  - `Venue` is now a child of `Club`, not a standalone entity.
  - Home Screen cards (Popular Grounds, Nearby You) display **Clubs**, not individual Venues.
  - Booking flow: User selects Club → selects Venue inside → selects time slot.
  - API listing/detail endpoints reflect Club as the primary discovery entry point.
  - Admin Panel needs Club management + nested Venue management per Club.

### LD-009 — Venue Supports Multiple Sports
- **Decision:** A single Venue can support more than one sport category (e.g., a court used for basketball and football at different times).
- **Reason:** Explicitly confirmed by developer.
- **Impact:**
  - `Venue` connects to `SportCategory` via many-to-many pivot `venue_sport_categories`.
  - No single `sport_category_id` FK on Venue.
  - Filtering by sport returns all Venues (and parent Clubs) that include that sport.
  - Booking record must capture `sport_category_id` to know which sport is being booked.

### LD-010 — Dual Club Management (Admin + Club Owner)
- **Decision:** Both the Admin (from Dashboard) and a Club Owner (from their own account) can add and manage Clubs and Venues.
- **Reason:** Explicitly confirmed — "الاثنين — أدمن + صاحب نادي."
- **Impact:**
  - New role `club_owner` added to the permissions model.
  - Club Owner has a dedicated scoped dashboard — can only manage their own clubs/venues.
  - Admin has full override access to all clubs regardless of owner.
  - `Club` entity has `owner_id` FK pointing to a ClubOwner user record.
  - Club Owner cannot view or edit other owners' data.
  - Admin approval flow for new Club Owner accounts: TBD.

### LD-011 — Dual Discovery Mode (By Sport or By Club)
- **Decision:** Users can discover and book venues either by selecting a sport category first, or by browsing a Club directly.
- **Reason:** Explicitly confirmed — "الاثنين ممكنين."
- **Impact:**
  - API supports filtering by `sport_category_id` AND by `club_id` independently.
  - Search works across both dimensions simultaneously.
  - Home → tap Category → shows Clubs/Venues matching that sport.
  - Home → tap Club card → shows Club detail with all its Venues inside.

### LD-012 — Location-Based Content by Default (Region/Area, Not City)
- **Decision:** The system serves content based on the user's current region/area by default. Clubs outside the user's region are NOT shown unless the user explicitly changes their region. The filter unit is "region/area" (منطقة), not "city" (مدينة) — because showing clubs from other governorates when the user is in Damascus makes no sense.
- **Reason:** Explicitly confirmed — "اذا انت حاطط منطقتك دمشق ليش لاعرضلك من الاساس ملاعب ريف دمشق، دايركت ما منعرضهن، بتعرضله حسب المنطقة."
- **Impact:**
  - `Club` entity must store `region_id` or `area_id` (not just city string).
  - A `Region` / `Area` entity must exist — Admin manages the list of regions.
  - User profile or app session must store the user's selected/detected region.
  - All Club listing APIs filter by user's region by default — no region = no results or prompt to set region.
  - Region filter on search/listing is a refinement within the already-scoped region, not a cross-region switch.
  - User can manually change their region from their profile or a region picker.

### LD-013 — Search is Full-Text Across Club + Venue + Region + Sport
- **Decision:** The text search (`q` param) searches across: Club name, Venue name, Region name, and Sport category name simultaneously.
- **Reason:** Explicitly confirmed — "النادي + الملعب + المدينة + الرياضة."
- **Impact:**
  - Search backend must query across multiple tables/fields in a single call.
  - Recommend using a full-text search approach (MySQL FULLTEXT, PostgreSQL tsvector, or a search service like Meilisearch/Algolia).
  - Results should indicate match type (matched by club name, by sport, etc.) to help mobile UI highlight the match.

### LD-015 — City → Area → Club → Venue (Three-Level Geographic Hierarchy)
- **Decision:** The full geographic hierarchy is: City (مدينة) → Area/District (منطقة/حي) → Club (نادي) → Venue (ملعب). City is the top-level grouping. Area is the discovery/filter unit. Club belongs to an Area. Venue belongs to a Club.
- **Reason:** Confirmed by example — "دمشق (City) → المزة (Area) → نادي الفيحاء (Club) → ملاعب (Venues)."
- **Impact:**
  - Two geographic entities: `City` and `Area`. Area belongs to City.
  - `Club` has `area_id` FK (not city, not region — Area is the granular unit).
  - User sets their city first, then optionally their area.
  - Listing API defaults to user's area; can widen to full city.
  - Admin manages Cities and Areas from Dashboard.

### LD-016 — Time-Based Pricing Per Venue (Pricing Tiers)
- **Decision:** Each Venue has pricing tiers defined by time periods within the day. The same venue can have different prices at different times (e.g., morning rate vs. evening rate). Pricing is NOT flat per venue.
- **Reason:** Confirmed by developer — "ملعب كرة قدم مع شمسية في الساعات الصباحية له سعر حجز."
- **Impact:**
  - New entity `VenuePricingTier` required — stores time range + price per slot duration.
  - Slot generation must respect the active pricing tier for the requested time.
  - When a user views available slots, each slot must show its price (derived from the matching tier).
  - Slot price is locked at booking time — not recalculated later.

### LD-017 — Fixed Slot Durations Set by Admin (No Free Choice)
- **Decision:** Slot duration is NOT chosen freely by the user. It is predefined per Venue by the Admin (Club Admin or Super Admin). A venue may offer one or two duration options (e.g., 45 min OR 90 min), but the user only selects from what is offered — not a free input.
- **Reason:** Confirmed by developer — "وحدة الحجز محددة من الأدمن مسبقاً ولا يمكن للمستخدم الاختيار بحرية."
- **Impact:**
  - `Venue` stores allowed durations as a defined set (not a min/max range).
  - New field: `allowed_durations` — array of allowed slot durations in minutes (e.g., [45, 90]).
  - Slot availability API returns slots grouped by duration option.
  - Booking creation must validate that requested duration is in the venue's allowed set.
  - Pricing tier is matched against both the time period AND the duration selected.

### LD-018 — Four-Level Club Staff Roles
- **Decision:** Each Club has four internal staff roles with different permission scopes:
  1. **club_owner** — Read-only access to club statistics and reports. Cannot edit anything.
  2. **club_admin** — Full control over their own club: venues, pricing, availability, staff management.
  3. **club_data_entry** — Can input/update data (venues, schedules, pricing) but cannot manage staff or access financial reports.
  4. **super_admin** (App Admin) — Full access to everything across all clubs.
- **Reason:** Explicitly described by developer.
- **Impact:**
  - All four roles are first-class in the permissions system.
  - Club Owner and Club Admin authenticate via email+password (separate from mobile user OTP/Google).
  - Club Owner sees: revenue stats, booking counts, occupancy rates — no edit capabilities.
  - Club Data Entry sees: venue management forms only — no financials, no user data.
  - Super Admin has override access to all clubs regardless of owner.
  - Role is stored on the ClubStaff entity with enum type.

### LD-019 — Production-Ready from Day One (No V1/V2 Phasing)
- **Decision:** The product must be built as a complete, production-ready system from the start. There is no V1/V2 phasing. All confirmed features must be fully implemented.
- **Reason:** Explicitly confirmed — "ما عنا V1 وV2، بدنا نطالع منتج production ready."
- **Impact:**
  - All architecture decisions must be made with production quality in mind.
  - No shortcuts, no placeholder implementations, no "we'll add this later" deferred features.
  - Deployment: Docker-based from day one.
  - Development: Claude Code will be used for implementation.
  - Database schema must be final and migration-safe before coding begins.
  - All confirmed modules must be fully built: Auth, Clubs, Venues, Bookings, Payments, Notifications, Admin Dashboard, Club Owner Portal.

### LD-035 — "My Grounds" = Recently Visited Venues (Not Clubs)
- **Decision:** "My Grounds" in Profile shows the Venues (ملاعب) the user has previously booked — not Clubs. Each item shows: venue image, venue name, last booked duration. This is a "recent venues" shortcut, not a favourites/saved list.
- **Reason:** Confirmed visually + explicitly — "بدها يكون فيها الملاعب اللي أنا رايح عليها من قبل، ركز ملاعب وليس نوادي." The "Add" button visible in the design mockup is ignored — it's a design artefact, not a feature.
- **Impact:**
  - No new entity needed — derived from existing `bookings` table.
  - Query: distinct venues from user's bookings, ordered by most recent booking date, with last booked duration.
  - Response per item: `venue.id`, `venue.name`, `venue.primary_image_url`, `last_booking_duration_minutes` (formatted as "X Hour" on mobile).
  - No "Add" button — read-only list.
  - Tap on venue card → opens Venue Detail (within its parent Club).

### LD-035 — My Grounds: Recent + Saved with Personal Rating Filter Logic
- **Decision:** "My Grounds" in Profile is a mixed list of two venue types shown together with a distinguishing flag:
  1. **Recent** — automatically derived from the user's completed/cancelled bookings. No separate entity — computed from Booking + Review history.
  2. **Saved/Favourite** — manually saved by the user via an explicit action.
- **Display Rule (IMPORTANT — confirmed):**
  - `Recent` venues: shown ONLY if **the user's own personal rating** for that venue's booking >= 3.0. If the user rated it below 3 → hidden. If the user didn't rate it yet → shown (no rating = not filtered out).
  - `Saved` venues: shown ALWAYS regardless of any rating — user explicitly chose to save them.
  - This is NOT based on the global avg_rating of the venue — it's the user's personal experience rating.
- **Why this logic:** "ما بدي أرجع لملعب أنا شخصياً ما عجبني" — the filter is personal, not public.
- **Flag in Response:** Each item carries `type: "recent" | "saved"`.
- **If a venue is both recent AND saved:** show once with `type: "saved"` (Saved takes priority).
- **Reason:** Explicitly confirmed and clarified by developer.
- **Impact:**
  - `Recent` query: distinct venues from user's bookings JOIN reviews WHERE (review doesn't exist OR review.rating >= 3.0), ordered by most recent booking date.
  - `Saved` query: venues in `saved_venues` for this user.
  - Merge, deduplicate (saved wins), sort.
  - API: `GET /api/v1/me/grounds`
  - The 3.0 threshold remains configurable via SystemSettings (`my_grounds_min_rating`).
  - `avg_rating` on Venue is still needed but for a DIFFERENT purpose: public display on venue cards and club listings.

### LD-033 — History Tab Shows Completed + Cancelled Bookings
- **Decision:** The History tab displays both `completed` and `cancelled` bookings — not completed only.
- **Reason:** Explicitly confirmed.
- **Impact:**
  - `GET /api/v1/bookings?tab=history` returns bookings where `status IN (completed, cancelled)`
  - Cancelled bookings in history must visually distinguish themselves (e.g., badge or strikethrough)
  - "Write a review" button appears only on `completed` bookings — never on `cancelled`
  - Cancelled bookings may show refund status if applicable

### LD-034 — Venue Facilities are Free-Form JSON (Not a Fixed List)
- **Decision:** Venue facilities/amenities are stored as a free-form JSON array on the Venue record. Admin enters them freely per venue — no predefined global list.
- **Reason:** Explicitly confirmed — "المرافق خليهن JSON أنسب."
- **Impact:**
  - `Venue.amenities` field type: `JSON` (array of strings or objects)
  - Example: `["Parking", "CCTV", "Waiting Room", "Changing Room", "Lighting", "Shaded area"]`
  - Admin Dashboard: tag-input or comma-separated input field per venue
  - Mobile displays each facility as an icon+label chip — icon mapping done on mobile side based on key names or a predefined icon map
  - No FK to a facilities table — purely JSON on Venue
  - Backend validation: must be valid JSON array, max 20 items, each item max 50 chars

### LD-030 — Review Structure: Stars (Required) + Text (Optional) + Anonymous Mode
- **Decision:** A review consists of: star rating (required, 1–5), text body (optional, min 50 chars IF provided), and an anonymous toggle that hides the user's identity in the public display.
- **Reason:** Explicitly confirmed — "نجوم ونص، النص optional، وإمكانية إخفاء الهوية كمان فكرة مهمة."
- **Impact:**
  - `Review.rating` — required, decimal 1.0–5.0 (displayed as stars)
  - `Review.body` — nullable, min 50 chars if provided
  - `Review.is_anonymous` — boolean, default false
  - When `is_anonymous = true` → mobile shows "مستخدم مجهول" / "Anonymous" instead of name + avatar
  - Backend always stores the real `user_id` — anonymity is a display decision only, never a data loss
  - Admin Dashboard always sees the real user identity regardless of anonymous flag
  - Validation: if `body` is provided → must be >= 50 chars; if null/empty → skip body validation

### LD-031 — Push Notifications via Firebase (Official SDK Only — No Third-Party Packages)
- **Decision:** Push notifications are sent via Firebase Cloud Messaging (FCM) using the official Firebase Admin SDK directly — no third-party Laravel packages (e.g., kreait/laravel-firebase is explicitly banned).
- **Reason:** Explicitly confirmed — "اشتغل على كلشي من الـ docs الرسمي تبع Firebase، لا kreait ولا أي package."
- **Impact:**
  - Install `google/cloud-firestore` or use Firebase REST API / Firebase Admin SDK via HTTP directly in a Laravel Service class.
  - Create a dedicated `FirebaseNotificationService` class that wraps FCM HTTP v1 API calls.
  - No Laravel package abstraction — pure HTTP calls to `https://fcm.googleapis.com/v1/projects/{project_id}/messages:send`
  - Auth: Google OAuth 2.0 service account credentials (JSON key file)
  - Store FCM device tokens on the User record (`fcm_token` field)
  - Notification events: booking confirmed, booking cancelled, booking reminder, payment status, system announcements
  - All notification sending goes through a Laravel Queue (job-based) — never synchronous

### LD-032 — Google Sign-In via Firebase Auth (Official SDK Only — No kreait)
- **Decision:** Google Sign-In on mobile uses Firebase Authentication. Backend verifies the Firebase ID token using Google's public keys directly — no kreait or any wrapper package.
- **Reason:** Same principle as LD-031 — official docs only.
- **Impact:**
  - Mobile sends Firebase ID token to backend: `POST /api/v1/auth/google` with `{ "firebase_token": "..." }`
  - Backend verifies token via JWT verification with Google's public JWKS keys
  - Create a dedicated `FirebaseAuthService` class for token verification
  - After verification → extract uid, email, name, photo_url, phone_number (if available in Firebase profile) → proceed with normal auth flow

### LD-036 — Phone Number for Google Users: Optional at Registration, Required at Booking
- **Decision:** When a user signs in with Google:
  1. Backend attempts to extract `phone_number` from Firebase token/profile — if present, auto-fills it.
  2. If `phone_number` is NOT available from Firebase → user can skip it and use the app normally.
  3. User can browse clubs, view venues, read content, check events — all WITHOUT phone number.
  4. When user attempts to CREATE A BOOKING → system blocks and prompts: "أضف رقم هاتفك لإتمام الحجز."
  5. User adds phone → optional OTP verification → phone saved → booking proceeds.
- **Reason:** Explicitly confirmed — "اذا قدرت تسحبه من Firebase login with Google تمام، اذا لا خليه يستعمل التطبيق عادي بس ما يقدر يحجز ملعب بدون رقم موبايل."
- **Impact:**
  - `User.phone_number` is nullable — can be null for Google-only users initially.
  - `User.phone_verified_at` is nullable — null until OTP verification of phone.
  - Booking creation API must validate: `user.phone_number IS NOT NULL` → else return `PHONE_REQUIRED` error.
  - Mobile must handle `PHONE_REQUIRED` error → show "Add phone number" bottom sheet or screen.
  - New flow: `POST /api/v1/me/phone/add` → sends OTP to new number → verify → saves to user.
  - Firebase `phone_number` field: extracted from token if present (Firebase stores it if user linked phone to Google account).

### LD-029 — App Startup API + Version Control (Fully Dynamic, Platform-Agnostic)
- **Decision:** A dedicated `POST /api/v1/app/startup` endpoint is called on every app launch. Everything is dynamic and DB-driven — platforms, environments, and update links are all managed by Super Admin from the Dashboard. No platform or environment is hardcoded.
- **Reason:** Explicitly confirmed and extended — "اعملها dynamic حتى الـ iOS والـ Android dynamic بركي نزلناه على متجر هواوي مستقبلاً."
- **Impact:** See full spec below.

#### Startup Endpoint Full Spec

**Request (Mobile → Backend):**
```json
{
  "platform_key": "ios" | "android" | "huawei" | "any-future-key",
  "app_version": "1.2.0"
}
```

**Response (Backend → Mobile):**
```json
{
  "base_url": "https://live.example.com",
  "update": {
    "is_required": false,
    "is_optional": true,
    "latest_version": "1.3.0",
    "download_url": "https://apps.apple.com/..." | "https://play.google.com/..." | "https://cdn.example.com/app.apk"
  },
  "app_settings": {
    "app_name": "SportBook",
    "maintenance_mode": false,
    "maintenance_message": null
  }
}
```

**Version Logic (same for all platforms):**
- `app_version < minimum_required_version` → `is_required: true`
- `app_version >= minimum_required_version AND < latest_version` → `is_optional: true`
- `app_version == latest_version` → both `false`

**Download URL Logic:**
- Default: return `store_url` of the platform
- Override: if `direct_apk_enabled = true` AND `direct_apk_url` is set → return `direct_apk_url` instead
- `direct_apk_url` is only meaningful for Android-type platforms (iOS App Store does not allow APK)

**Admin Dashboard Control (Super Admin Only):**
- **Platform Manager:** Add / edit / deactivate platforms dynamically
  - `platform_key` (unique slug: `ios`, `android`, `huawei`, etc.)
  - `platform_name` (display label: "iOS", "Android", "Huawei AppGallery")
  - `store_url` — official store link
  - `direct_apk_url` — direct APK download link (nullable, Android-type only)
  - `direct_apk_enabled` — toggle: use direct APK link instead of store URL
  - `latest_version`
  - `minimum_required_version`
  - `optional_update_version` (nullable)
  - `is_active` — whether this platform is served
- **Environment Manager per Platform:** Add / edit / deactivate environments
  - `env_name` (e.g., "Live", "Stage", "Beta")
  - `base_url`
  - `is_active` — only one environment can be active per platform at a time
- **Warning Banner:** if active environment is not "Live" → prominent red warning in Dashboard

### LD-026 — Dashboard Frontend: Inertia.js + Vue 3 + Tailwind CSS + shadcn-vue
- **Decision:** Both dashboards (`/admin/` and `/club/`) are built with Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS 4 + shadcn-vue component library.
- **Reason:** Best fit for the requirements: PWA-capable, super responsive, excellent Claude Code support, official Laravel 13 starter kit, rich component ecosystem.
- **Impact:**
  - Single Laravel monorepo — no separate frontend repo needed.
  - Inertia handles routing through Laravel controllers — no REST API needed for the dashboard itself.
  - Two separate Inertia "apps" inside the same Laravel project: one for `/admin/*`, one for `/club/*`.
  - Both share the same Vue + Tailwind + shadcn-vue component library.
  - shadcn-vue provides: tables, forms, charts, modals, date pickers, dropdowns — all responsive and accessible.
  - Vite handles HMR and production builds.

### LD-027 — PWA for Dashboards Only (Club + Admin) — NOT for End Users
- **Decision:** PWA applies exclusively to the two dashboards:
  - `/club/*` — Club Dashboard as PWA (primary — Club Admin installs on mobile)
  - `/admin/*` — Super Admin Dashboard as PWA (secondary — nice to have)
  - End user (اللاعب) = Flutter only. No PWA for end users.
- **Reason:** Confirmed — PWA for dashboards was always the plan. User-facing PWA evaluated and deferred — Flutter covers the end-user experience completely.
- **Impact:**
  - `vite-plugin-pwa` → PWA manifest + service worker for both dashboards
  - Dashboard must be fully responsive (mobile-first Tailwind) — Club Admin uses it on phone
  - PWA features: installable, offline-safe navigation, push notifications via FCM Web SDK
  - End user terminology: "لاعب" not "مستخدم" — branding decision (no technical impact on backend)
- **Deferred:** User-facing PWA → Phase 2, after product is stable. API is already ready to serve it when needed.

### LD-028 — Maps: Leaflet (Dashboard) + Google Maps (Mobile — separate concern)
- **Decision:** Dashboard uses Leaflet.js + OpenStreetMap for map picker (selecting club/venue location). Mobile app uses Google Maps SDK. The two are completely separate — no compatibility issue because they run in different contexts.
- **Reason:** Confirmed. Leaflet + OSM is free with no API key. Google Maps is superior for mobile GPS/routing.
- **Impact:**
  - Install `leaflet` + `vue-leaflet` (or `@vue-leaflet/vue-leaflet`) in the Dashboard frontend.
  - Map picker component: click on map → sets `latitude` + `longitude` fields on the form.
  - Venue location is optional/nullable on Venue — only Club location is required.
  - Backend stores only `latitude` (decimal 10,8) + `longitude` (decimal 11,8) — no map library dependency.
  - Mobile team uses Google Maps SDK independently — zero coordination needed from our side.

### LD-023 — All Club Accounts Created by Super Admin Only (No Self-Registration)
- **Decision:** All club-side accounts — Club Owner, Club Admin, Club Data Entry — are created exclusively by the Super Admin from the `/admin/` Dashboard. There is no self-registration flow for any club role.
- **Reason:** Explicitly confirmed — "كلشي حسابات بتم إنشاؤها من أدمن التطبيق."
- **Impact:**
  - No `/club/register` endpoint or screen.
  - Admin creates account → sets role → system sends credentials to the person (via SMS or email).
  - Club Owner account is created after the Super Admin approves the club and verifies the owner.
  - All ClubOwner + ClubStaffMember records are insert-only from the admin API.

### LD-024 — Dynamic Roles & Permissions via Spatie Laravel Permissions
- **Decision:** The permissions system is fully dynamic and managed through Spatie Laravel Permissions package. Roles are created and configured by the Super Admin. Each role has its own permission set attached dynamically — not hardcoded in code.
- **Reason:** Explicitly confirmed — "كلشي أدوار بيتم إنشاؤها من أدمن التطبيق وكل دور يتم إرفاق له صلاحياته وبكون كله dynamic عن طريق Spatie Permissions."
- **Impact:**
  - **Tech Stack confirmed: Laravel** (Spatie is a Laravel-specific package).
  - Roles table, permissions table, and pivot tables are managed by Spatie.
  - Super Admin can create new roles, rename roles, and attach/detach permissions from the Dashboard at runtime.
  - All backend middleware uses Spatie's `can()` / `hasRole()` / `hasPermissionTo()` gates.
  - Permission names must be defined consistently as constants (e.g., `clubs.create`, `venues.edit`, `bookings.view`).
  - The `/admin/` Dashboard needs a full Role & Permission management UI.
  - This applies to BOTH the `/admin/` domain AND the `/club/` domain.

### LD-025 — Rating & Reviews System Confirmed
- **Decision:** A rating and review system exists in the product. Visual evidence from Venue Detail screen shows star ratings with reviewer name, avatar, timestamp, and review text.
- **Reason:** Confirmed visually (Screen 04) and verbally — "طبعا في تقييم."
- **Impact:**
  - New entity `Review` required.
  - Rating is on the **Venue** level (shown on Venue Detail screen — not Club level).
  - Club-level rating is a computed average of its venues' ratings.
  - Only users who completed a booking for that venue can submit a review (assumption — needs confirmation).
  - Review contains: rating (stars), text body, reviewer name + avatar.
  - Admin can moderate/delete reviews from Dashboard.

### LD-020 — Two Separate Dashboards (Different Layout + URL Prefix)
- **Decision:** Two completely separate web dashboard applications with different layouts, URL prefixes, and auth domains:
  1. `/admin/*` — Super Admin Dashboard: full system control.
  2. `/club/*` — Club Dashboard: scoped per-club management with role-based access (club_owner, club_admin, club_data_entry).
- **Reason:** Explicitly confirmed — "حيكون في 2 dashboard كل وحدة layout مختلف و prefix مختلف."
- **Impact:**
  - Two separate frontend entry points (can be same codebase with different routing, or two separate apps).
  - Two separate auth flows: both use email+password but different token scopes and middleware.
  - `/admin/*` auth tokens carry `role: super_admin`.
  - `/club/*` auth tokens carry `role: club_owner | club_admin | club_data_entry` + `club_id` scope.
  - Backend API routes for admin actions prefixed `/api/admin/v1/`.
  - Backend API routes for club actions prefixed `/api/club/v1/`.

### LD-021 — Club Approval is Manual (Admin Contacts Club Owner Directly)
- **Decision:** When a new Club is submitted (by Club Owner or Admin), it enters `pending_approval` status. The Super Admin reviews it manually — contacts the club owner by phone to verify legitimacy before approving. No automated approval.
- **Reason:** Explicitly confirmed — "ادمن التطبيق يتواصل معه يدوياً عن طريق الرقم ويفهم ليش نزل هاد الملعب."
- **Impact:**
  - `Club.status` must have `pending_approval` as a real state — not just a flag.
  - Admin Dashboard shows a "Pending Approval" queue with club details + owner phone number prominently displayed.
  - Admin can Approve / Reject with a reason.
  - Club Owner receives a notification (SMS or in-app) when their club is approved or rejected.
  - No club appears on the mobile app until `status = active`.

### LD-022 — Events Tab: Two Sections (Our Competitions + Today's Matches from External API)
- **Decision:** The Events tab contains two sections:
  1. **Our Competitions (مسابقاتنا):** Content created and managed by the Super Admin from the Dashboard. Static content module.
  2. **Today's Matches (مباريات اليوم):** Live/today match data fetched from a free external football API. Display only — no user interaction.
- **Reason:** Explicitly confirmed — "نعرض المسابقات اللي حنعملها نحن ونعرض المباريات اللي حتنلعب اليوم."
- **Selected External API:** `football-data.org`
  - **Why:** Completely free forever for top competitions. Covers: Premier League, La Liga, Champions League, Europa League, Bundesliga, Serie A, Ligue 1, Eredivisie, and more. RESTful JSON API. 10 requests/minute on free tier. No credit card required.
  - **Key endpoint:** `GET https://api.football-data.org/v4/matches?dateFrom={today}&dateTo={today}` with `X-Auth-Token` header.
  - **Data returned:** match time, home team, away team, score, status (SCHEDULED/LIVE/FINISHED), competition name + emblem.
- **Impact:**
  - Our backend acts as a **proxy/cache layer** — fetches from football-data.org and caches for 5 minutes to respect rate limits.
  - Mobile calls our API: `GET /api/v1/events/matches/today` — never calls football-data.org directly.
  - `Competition` entity needed for admin-managed competitions section.
  - Admin can create/edit/delete competitions from `/admin/` dashboard.
  - Events tab is **read-only** for mobile users — no booking, no registration.

### LD-014 — Available Filters on Club/Venue Listing
- **Decision:** The following filters are available as query params on the Club/Venue listing API:
  - `q` — full-text search (club name, venue name, region, sport)
  - `sport_category_id` — filter by sport (single or multiple)
  - `region_id` — region/area filter (defaults to user's region, user can override)
  - `price_min` / `price_max` — price range per hour
  - `rating_min` — minimum rating (e.g., 4.0+)
- **Reason:** Explicitly confirmed by developer.
- **Impact:**
  - All params are optional query params on `GET /api/v1/clubs`.
  - Backend must handle any combination of these filters simultaneously.
  - Price filter applies to Venue pricing (since pricing is per Venue).
  - Rating filter requires a `rating` computed field on Club (average of its venues' ratings or its own rating — TBD).

---

## 3. Global Assumptions

- **GA-001:** A single user account can authenticate via phone OTP or Google Sign-In. Both methods can be linked to the same account (account linking strategy TBD).
- **GA-002:** The system operates primarily in Syria. Currency is Syrian Pound (SYP) unless confirmed otherwise.
- **GA-003:** Venues are physical locations managed by the Admin or by Venue Owners (role TBD).
- **GA-004:** A booking is a time-slot reservation for a specific venue on a specific date.
- **GA-005:** Payments are processed through the supported electronic providers — cash payment support is TBD.
- **GA-006:** The Admin Dashboard is a web application accessible only to authorized staff — not exposed to end users.
- **GA-007:** All API responses will be versioned under `/api/v1/`.
- **GA-008:** The system will need Arabic language support in content fields (bilingual: Arabic + English assumed).
- **GA-009:** Push notifications for booking confirmations, reminders, and cancellations are likely needed — not yet confirmed.
- **GA-010:** Venue images/media will be stored via a file storage service (S3-compatible or local) — storage provider TBD.
- **GA-011:** The system is production-ready from day one. Deployment is Docker-based. No phased releases.
- **GA-012:** Development will be executed using Claude Code after architecture and schema are finalized.
- **GA-013:** Database migrations must be safe and versioned from the first commit.
- **GA-014:** Venue `latitude`/`longitude` is optional (nullable). Club `latitude`/`longitude` is required.
- **GA-015:** The `/club/` dashboard is PWA-installable — it must work well on mobile screens (375px+).

---

## 4. Modules Overview

### Module 01 — Authentication & Identity
- **Purpose:** Allow users to enter the system via Mobile OTP or Google Sign-In. Issue and manage tokens.
- **Scope:** OTP request, OTP verify, Google auth, token refresh, logout, guest session (TBD), account linking.
- **Actors:** Mobile User, Guest
- **Admin Visibility:** User management, block/suspend accounts, auth logs.

### Module 02 — User Profile & Onboarding
- **Purpose:** Capture and manage user profile data after authentication.
- **Scope:** Profile creation, profile update, profile completion tracking, prefill from Google.
- **Actors:** Mobile User
- **Admin Visibility:** View/edit user profiles, manage account status.

### Module 03 — Sport Categories
- **Purpose:** Define the types of sports supported by the platform. Fully dynamic.
- **Scope:** Category CRUD (Admin), category listing (Mobile API), category icon/image management.
- **Actors:** Admin (manage), Mobile User (view)
- **Admin Visibility:** Full CRUD, ordering, activation/deactivation.

### Module 04 — Clubs
- **Purpose:** Represent sports clubs — the top-level entity users discover and browse. A Club is the "brand" that owns one or more venues.
- **Scope:** Club CRUD, club images, club location, club status, club owner assignment.
- **Actors:** Admin (full manage), Club Owner (manage own clubs), Mobile User (view/search/filter)
- **Admin Visibility:** Full CRUD, owner assignment, status control, approval of new clubs.
- **Notes:** This is the PRIMARY discovery entity. Home Screen cards show Clubs. Users browse Clubs then dive into their Venues.

### Module 04b — Venues
- **Purpose:** Represent individual physical courts/fields inside a Club. A Venue belongs to exactly one Club and supports one or more sports.
- **Scope:** Venue CRUD (nested under Club), venue images, venue amenities, venue availability rules, venue pricing, multi-sport assignment.
- **Actors:** Admin (full manage), Club Owner (manage venues of own clubs), Mobile User (view within club detail)
- **Admin Visibility:** Full CRUD nested under Club, multi-sport tag management, availability rule editor.
- **Notes:** Venue is NOT a standalone discoverable entity — it's always accessed through its parent Club.

### Module 05 — Bookings
- **Purpose:** Allow users to reserve a time slot at a venue.
- **Scope:** Slot availability check, booking creation, booking confirmation, booking cancellation, booking history.
- **Actors:** Mobile User (create/cancel), Admin (view/manage/override)
- **Admin Visibility:** Full booking list, status management, manual override.

### Module 06 — Payments
- **Purpose:** Process payments for bookings through four supported payment providers.
- **Scope:** Payment initiation, provider routing (OTP flow vs WebView flow), OTP confirmation, callback handling, payment status tracking, refunds (TBD).
- **Actors:** Mobile User (pay), Admin (view/manage)
- **Providers:** MTN Cash (OTP), Syriatel Cash (OTP), Fatora (WebView/card), SamaPay (WebView/card)
- **Two distinct mobile flows:**
  - OTP flow: MTN + Syriatel → mobile shows OTP input
  - WebView flow: Fatora + SamaPay → mobile opens hosted payment page
- **Admin Visibility:** Payment records, status, provider, manual reconciliation, raw provider payload.

### Module 06b — Club Owner Portal
- **Purpose:** Scoped management interface for Club Owners to manage their own clubs and venues only.
- **Scope:** Club profile edit, Venue CRUD under own clubs, availability rules, pricing, booking view (read-only for own venues), basic reporting.
- **Actors:** Club Owner
- **Admin Visibility:** Admin can see all Club Owner accounts, approve/suspend them.
- **Notes:** Club Owner cannot access system settings, other clubs, user management, or payment configs.

### Module 07 — Admin Dashboard
- **Purpose:** Central control panel for managing the entire system.
- **Scope:** All modules above accessible via web UI with full CRUD and configuration controls.
- **Actors:** Super Admin, Admin Staff (roles TBD)
- **Notes:** Every configurable value in the system must be editable from here.

### Module 07b — App Startup & Version Control
- **Purpose:** Provide a single public endpoint the mobile app hits on every launch. Returns environment URL, update policy, and basic app settings. Fully controlled by Super Admin.
- **Scope:** AppConfig CRUD (2 records only — iOS + Android), version comparison logic, environment switching, store URL management.
- **Actors:** Super Admin (manage), Mobile App (consume on startup)
- **Admin Visibility:** Dedicated panel in `/admin/` — separate iOS and Android sections.
- **Notes:**
  - This is the most critical API in the system — if it's down, the app can't start.
  - Must be the fastest endpoint in the system (< 50ms).
  - Must have its own health monitoring.

### Module 08 — System Configuration & Content
- **Purpose:** Store and serve all dynamic system-level settings and content blocks.
- **Scope:** App settings, feature flags, static content pages (About, Privacy Policy, Terms, Help), banners, announcements, notification templates. Content pages stored as HTML (rich text), bilingual (AR + EN).
- **Actors:** Admin (manage), Mobile App (consume via API)
- **Admin Visibility:** Full control over all content and settings.

### Module 09 — Events
- **Purpose:** Display two types of content in the Events tab: admin-managed competitions + today's football matches from external API.
- **Scope:**
  - Competitions CRUD (Super Admin manages from `/admin/` Dashboard)
  - Today's Matches: backend proxy/cache layer fetching from `football-data.org`
- **Actors:** Super Admin (manage competitions), Mobile User (view only)
- **External Dependency:** `football-data.org` free API — cached on our backend every 5 minutes.
- **Notes:** Zero user interaction — display only. No booking, no registration.

### Module 10 — Notifications (Firebase FCM — Official SDK Only)
- **Purpose:** Send push notifications to mobile users for booking events and system updates.
- **Provider:** Firebase Cloud Messaging (FCM) via official HTTP v1 API — no kreait or any wrapper package.
- **Scope:**
  - Booking confirmed → notify user + Super Admin + Club Admin (immediate, LD-043)
  - Booking cancelled (by user or admin) → notify user
  - Booking reminder (X hours before) → scheduled Laravel command
  - Payment completed / failed → notify user
  - System announcement (Admin broadcast) → notify all or segment
- **FCM Token Storage:**
  - `users.fcm_token` — mobile user
  - `admin_users.fcm_token` — Super Admin (updated on Dashboard login)
  - `club_staff_members.fcm_token` — Club Admin (updated on Club Dashboard login, PWA)
- **Notification Preferences (respected before every send):**
  - Check `user.notifications_push_enabled` before FCM
  - Check `user.notifications_sms_enabled` before SMS notifications
  - Check `user.notifications_reminders_enabled` before reminder jobs
  - OTP auth SMS is ALWAYS sent regardless of preferences (security-critical)
  - Use `user.preferred_language` for FCM notification text language
- **Actors:** System (auto-trigger via Laravel Queue Jobs), Admin (manual broadcast from Dashboard)
- **Implementation:**
  - `FirebaseNotificationService` — dedicated Laravel service class
  - Calls FCM HTTP v1 API directly: `POST https://fcm.googleapis.com/v1/projects/{id}/messages:send`
  - Auth: Google OAuth 2.0 service account (JSON key stored in storage, path in .env)
  - All sends are queued — never synchronous
  - `fcm_token` stored on User record, updated on every app login
- **Notes:** Google Sign-In verification also uses Firebase — handled by `FirebaseAuthService` (separate class).

---

## 5. Screen-by-Screen Analysis

> Screens are added as submitted. Each follows the standard analysis template.
> Last updated: Batch 01 — 5 images (Home Screen states)

---


---


---

### Screen E01 — Events Tab
> Source: No image — built from confirmed decisions (LD-022) + developer description
> Status: Assumed — "الأعلى: انتظرو مسابقاتنا، الأسفل: مباريات اليوم والمباريات القادمة"

#### A. Visual Summary (Assumed)
شاشة Events مقسّمة لقسمين رئيسيين (scroll عمودي):

**القسم الأول — Our Competitions (مسابقاتنا):**
- Header: "انتظرونا" أو "مسابقاتنا القادمة"
- كروت أفقية أو عمودية لكل مسابقة تعرض:
  - صورة المسابقة
  - اسم المسابقة
  - التاريخ (start_date → end_date)
  - وصف مختصر
- إذا لا توجد مسابقات → رسالة "لا توجد مسابقات حالياً"

**القسم الثاني — Today's Matches + Upcoming (مباريات اليوم والقادمة):**
- Sub-tabs أو Sections منفصلة:
  - "اليوم" — مباريات يوم اليوم (LIVE + SCHEDULED + FINISHED)
  - "القادمة" — مباريات الأيام القادمة (SCHEDULED)
- كل كارد مباراة يعرض:
  - شعار الدوري + اسم الدوري
  - الفريق المحلي (شعار + اسم) vs الفريق الضيف
  - وقت المباراة
  - النتيجة إذا LIVE أو FINISHED
  - Badge: LIVE 🔴 / FINISHED / وقت البداية

#### B. Functional Purpose
- إبقاء المستخدم engaged بمحتوى رياضي إضافي خارج الحجز
- الترويج لمسابقات التطبيق
- عرض مباريات عالمية كـ value-add للمستخدم

#### C. Primary Actor
Mobile User (مسجّل + غير مسجّل — شاشة عامة)

#### D. Data Sources
| القسم | المصدر | Cache |
|---|---|---|
| مسابقاتنا | Backend DB — `competitions` table | لا cache — fresh دايماً |
| مباريات اليوم | football-data.org API → backend proxy | ✅ 5 دقائق TTL |
| المباريات القادمة | football-data.org API → backend proxy | ✅ 30 دقيقة TTL |

#### E. Business Rules
- **مسابقاتنا:** Admin ينشر/يخفي المسابقات من Dashboard → `competitions.is_published = true` فقط
- **مباريات اليوم:** Backend يجلب من football-data.org ويكاش 5 دقائق — Mobile لا يكلّم football-data.org مباشرة أبداً
- **المباريات القادمة:** نفس المصدر لكن يجلب الأيام الـ 7 القادمة، cached 30 دقيقة
- **Leagues المعروضة:** الدوريات الكبرى المتاحة مجاناً: Premier League، La Liga، Champions League، Bundesliga، Serie A، Ligue 1، Eredivisie
- **No user interaction** — عرض فقط، لا حجز، لا تسجيل، لا تعليق

#### F. States
| State | Description |
|---|---|
| `loading` | Skeleton placeholders لكلا القسمين |
| `competitions_empty` | "لا توجد مسابقات حالياً — ترقبوا" |
| `matches_loaded` | المباريات ظاهرة مع نتائج/أوقات |
| `matches_error` | خطأ في جلب المباريات → "تعذّر تحميل المباريات" + retry |
| `no_matches_today` | "لا مباريات اليوم" |

#### G. API Impact

```
GET /api/v1/events/competitions
Auth: Public
Response: [
  {
    "id", "title_ar", "title_en",
    "description_ar", "description_en",
    "image_url",
    "start_date", "end_date",
    "is_published"
  }
]
Notes: يرجع published فقط. Fresh — لا cache.

GET /api/v1/events/matches/today
Auth: Public
Response: {
  "date": "2025-06-15",
  "cached_at": "2025-06-15T14:00:00Z",
  "matches": [
    {
      "id": "external_id",
      "status": "SCHEDULED | LIVE | FINISHED | POSTPONED",
      "kick_off": "20:00",
      "competition": { "name", "emblem_url" },
      "home_team": { "name", "crest_url" },
      "away_team": { "name", "crest_url" },
      "score": { "home": null, "away": null }
    }
  ]
}
Cache: 5 دقائق — Laravel file cache

GET /api/v1/events/matches/upcoming
Auth: Public
Query: days=7 (default)
Response: {
  "matches": [
    {
      // نفس shape + "match_date": "2025-06-16"
    }
  ]
}
Cache: 30 دقيقة — Laravel file cache
```

#### H. Backend Implementation — football-data.org Proxy

```php
// EventsController@todayMatches
public function todayMatches()
{
    $data = Cache::remember('football_matches_today', 300, function () {
        $response = Http::withHeaders([
            'X-Auth-Token' => config('services.football_data.api_key')
        ])->get('https://api.football-data.org/v4/matches', [
            'dateFrom' => today()->format('Y-m-d'),
            'dateTo'   => today()->format('Y-m-d'),
        ]);
        return $response->json();
    });

    return response()->json($this->formatMatches($data));
}

// EventsController@upcomingMatches
public function upcomingMatches()
{
    $data = Cache::remember('football_matches_upcoming', 1800, function () {
        $response = Http::withHeaders([
            'X-Auth-Token' => config('services.football_data.api_key')
        ])->get('https://api.football-data.org/v4/matches', [
            'dateFrom' => today()->addDay()->format('Y-m-d'),
            'dateTo'   => today()->addDays(7)->format('Y-m-d'),
        ]);
        return $response->json();
    });

    return response()->json($this->formatMatches($data));
}
```

#### I. Admin Panel Impact
- **Competitions Management** (قسم 8 موجود مسبقاً):
  - إضافة مسابقة: عنوان AR/EN، وصف AR/EN، صورة، تواريخ، publish/draft
  - حذف أو أرشفة المسابقات المنتهية
- **لا إدارة للمباريات** — تأتي من football-data.org تلقائياً

#### J. Mobile App Impact
- الشاشة تُحمَّل عند tap على "Event" tab في Bottom Navigation
- Skeleton loading لكلا القسمين أثناء الجلب
- **المباريات القادمة** مقسّمة بالتاريخ (grouped by date)
- Badge "LIVE 🔴" يتحدث إذا المستخدم refresh اليدوي (pull-to-refresh)
- لا auto-refresh (لتوفير البطارية) — pull-to-refresh يكسر الـ cache ويُعيد الجلب

#### K. Assumptions
- **PA-E01-001:** Pull-to-refresh يُلغي الـ cache ويجلب بيانات جديدة.
- **PA-E01-002:** المباريات مرتبة: LIVE أولاً → SCHEDULED → FINISHED.
- **PA-E01-003:** المسابقات مرتبة بـ `start_date` DESC (الأحدث أولاً أو القادمة أولاً).
- **PA-E01-004:** football-data.org free tier = 10 requests/minute — الـ cache يحمي من تجاوز هذا الحد.

#### L. Risks
- **R-020:** إذا football-data.org down → matches section يعرض رسالة خطأ فقط — competitions section تبقى تعمل بشكل مستقل
- **R-021:** Free tier = 12 competition فقط — إذا أردنا دوريات إضافية مستقبلاً → paid plan مطلوب
- **R-022:** Match status "LIVE" يحتاج polling متكرر — pull-to-refresh هو الحل المناسب بدل WebSocket لتجنب التعقيد

#### M. Recommendations
- افصل جلب Competitions عن جلب Matches — كل section يُحمَّل مستقل
- إذا football-data.org أرجع خطأ → أعرض آخر cached response مع timestamp ("آخر تحديث منذ X دقيقة")
- أضف `config('services.football_data.api_key')` من `.env` — لا hardcoded

### Screen A01 — Authentication Entry (Landing / Welcome)
> Source: No image — built from confirmed decisions (LD-001, LD-007, LD-023, LD-032)
> Status: Assumed — awaiting visual confirmation

#### A. Visual Summary (Assumed)
أول شاشة يراها المستخدم بعد الـ Splash Screen. تعرض:
- شعار التطبيق + اسمه
- زر "Continue with Google" (Google sign-in)
- زر "Continue with Phone Number" (OTP)
- رابط "Privacy Policy" و"Terms of Service" في الأسفل
- لا يوجد: email field، password field، "Forgot password"

#### B. Functional Purpose
بوابة الدخول الوحيدة — يوجّه المستخدم لأحد مسارين فقط.

#### C. Primary Actor
Guest / New User / Returning User

#### D. User Actions
| Action | Destination |
|---|---|
| Tap "Continue with Google" | Firebase Google Sign-In SDK → Screen A03 |
| Tap "Continue with Phone Number" | Screen A02 (Phone input) |
| Tap Privacy Policy | Screen 11 (Privacy Policy) |
| Tap Terms | ContentPage slug: `terms` |

#### E. Business Rules
- لا يوجد guest mode مؤكد — المستخدم يجب أن يسجّل للوصول للحجز
- إذا المستخدم مسجّل مسبقاً وعنده token صالح → يتخطى هذه الشاشة تلقائياً
- بعد Splash Screen + App Startup response → إذا `requires_auth = true` → هذه الشاشة

#### F. Backend Impact
- لا API call من هذه الشاشة مباشرة
- Google button → يطلق Firebase Auth SDK على الموبايل → ينتج Firebase ID token → يُرسل لـ Screen A03

#### G. Assumptions
- **PA-A01-001:** الشاشة تحتوي على صورة/illustration رياضية في الخلفية أو الأعلى.
- **PA-A01-002:** لا "Skip" أو Guest mode — الحجز يتطلب حساب.
- **PA-A01-003:** Dark/Light mode حسب الثيم — الشاشات الأخرى تعرض Dark mode.

---

### Screen A02 — Phone Number Input
> Source: No image — built from confirmed decisions (LD-001, LD-002)
> Status: Assumed

#### A. Visual Summary (Assumed)
شاشة إدخال رقم الهاتف. تعرض:
- Header: "Enter your phone number"
- Country code selector + Phone number input
- زر "Send OTP" / "Continue"
- نص توضيحي: "سنرسل رمز تحقق عبر SMS"

#### B. Functional Purpose
جمع رقم الهاتف وطلب إرسال OTP عبر SMS.

#### C. User Actions
| Action | Description |
|---|---|
| Select country code | اختيار كود الدولة (+963 سوريا افتراضياً) |
| Type phone number | إدخال الرقم |
| Tap Continue | إرسال `POST /api/v1/auth/otp/request` |
| Tap "إرسال عبر WhatsApp" | إذا `whatsapp_available = true` — `POST /auth/otp/choose-channel {channel: whatsapp}` |
| Tap "إرسال عبر SMS" | إذا `whatsapp_available = true` — `POST /auth/otp/choose-channel {channel: sms}` |

#### D. Validation Rules
- رقم الهاتف إلزامي
- صيغة صحيحة حسب كود الدولة
- Rate limiting: لا يُرسل أكثر من X طلب في الدقيقة لنفس الرقم

#### E. API Impact
```
POST /api/v1/auth/otp/request
Body: { "phone_number": "+963XXXXXXXXX", "device_id": "optional" }
Response: {
  "verification_request_id": "uuid",
  "expires_in_seconds": 120,
  "resend_after_seconds": 60,
  "masked_destination": "+963*****XXX"
}
```

#### F. Business Rules
- البلد الافتراضي: سوريا (+963)
- الرقم يُطبَّع إلى E.164 format قبل الإرسال للـ backend
- عند النجاح → الانتقال لـ Screen A03 مع تمرير `verification_request_id` + `masked_destination`

#### G. Assumptions
- **PA-A02-001:** Country code selector يعرض العلم + الكود — +963 افتراضي.
- **PA-A02-002:** زر Continue يُعطَّل حتى يكتمل إدخال رقم صحيح.

---

### Screen A03 — OTP Verification
> Source: No image — built from confirmed decisions (LD-001, LD-002)
> OTP length: 5 digits (confirmed)
> Status: Assumed

#### A. Visual Summary (Assumed)
شاشة إدخال رمز OTP. تعرض:
- Header: "Verify your number"
- نص: "تم إرسال رمز مكون من 5 أرقام إلى [masked_destination]"
- 5 مربعات input منفصلة (OTP boxes)
- Countdown timer: "Resend in 00:60"
- رابط "Resend OTP" (مخفي حتى انتهاء الـ countdown)
- رابط "Change number" للعودة لـ A02
- زر "Verify" (يُفعَّل عند إدخال 5 أرقام)

#### B. Functional Purpose
التحقق من هوية المستخدم عبر الرمز المرسل بـ SMS.

#### C. User Actions
| Action | Description |
|---|---|
| Input 5 digits | إدخال الرمز في المربعات |
| Auto-submit | يُرسل تلقائياً عند اكتمال الـ 5 أرقام (مستنتج) |
| Tap Verify | إرسال يدوي |
| Tap Resend | `POST /api/v1/auth/otp/resend` بعد انتهاء الـ countdown |
| Tap Change number | العودة لـ A02 |

#### D. Data Inputs
- `otp_code` — 5 أرقام فقط، numeric only
- `verification_request_id` — مُمرَّر من Screen A02

#### E. Validation Rules
- 5 أرقام بالضبط — لا أقل، لا أكثر
- أرقام فقط (0-9)
- Verify يُعطَّل حتى اكتمال الـ 5
- OTP ينتهي خلال 120 ثانية (من SystemSettings)
- Max 5 محاولات فاشلة → lock (من SystemSettings)

#### F. States
| State | Description |
|---|---|
| `waiting_input` | ينتظر إدخال المستخدم، countdown يعمل |
| `inputting` | المستخدم يكتب |
| `submitting` | loading بعد الإرسال |
| `error_invalid` | رمز خاطئ — رسالة حمراء + تنظيف المربعات |
| `error_expired` | انتهت صلاحية الرمز — اضغط Resend |
| `error_locked` | تجاوز المحاولات — طلب OTP جديد |
| `success` | تحقق ناجح → routing حسب auth_outcome |

#### G. Business Rules
- Countdown يبدأ من `resend_after_seconds` القادم من API response
- بعد انتهاء الـ countdown → يظهر "Resend OTP"
- Resend يعيد تعيين الـ countdown
- عند نجاح التحقق:
  - `auth_outcome = "registration"` AND `requires_profile_completion = true` → Screen A05 (Profile Completion)
  - `auth_outcome = "login"` → Home Screen مباشرة

#### H. Error Codes (يجب أن يرجعها الـ backend)
| Code | المعنى |
|---|---|
| `OTP_INVALID` | الرمز خاطئ |
| `OTP_EXPIRED` | انتهت الصلاحية |
| `OTP_MAX_ATTEMPTS` | تجاوز المحاولات |
| `OTP_LOCKED` | محاولات كثيرة — نادي جديد |
| `REQUEST_NOT_FOUND` | verification_request_id غير موجود |

#### I. API Impact
```
POST /api/v1/auth/otp/verify
Body: {
  "verification_request_id": "uuid",
  "otp_code": "123456",
  "device_id": "optional"
}
Response: {
  "access_token": "...",
  "refresh_token": "...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "auth_outcome": "login" | "registration",
  "requires_profile_completion": true | false,
  "next_step": "home" | "profile_completion",
  "user": { id, name, phone_number, email, avatar_url, account_status }
}

POST /api/v1/auth/otp/resend
Body: { "verification_request_id": "uuid" }
Response: { "expires_in_seconds": 120, "resend_after_seconds": 60, "masked_destination": "..." }
```

#### J. Mobile App Impact
- OTP boxes: 5 مربعات منفصلة أو input واحد masked
- Auto-focus يتنقل بين المربعات تلقائياً
- Paste support — إذا المستخدم copy-paste الرمز من SMS
- Countdown timer driven by `resend_after_seconds` من الـ API — لا hardcoded
- Token يُحفظ في secure storage (Flutter secure storage)

#### K. Risks
- SMS لم يصل → المستخدم عالق. Mitigation: Resend + دعم واضح
- OTP auto-read على Android (SMS Retriever API) — شغل الموبايل، ليس backend
- المستخدم يضغط back بعد طلب OTP → الـ verification_request_id يضيع محلياً

---

### Screen A04 — Google Sign-In (Firebase Flow)
> Source: No image — built from confirmed decisions (LD-001, LD-032)
> Status: Assumed — Google Sign-In UI هو Firebase SDK native sheet

#### A. Visual Summary (Assumed)
Google Sign-In هو Native Bottom Sheet من Firebase SDK — ليس شاشة نصممها نحن. يظهر تلقائياً عند tap "Continue with Google".

#### B. Flow
```
User taps "Continue with Google"
    ↓
Firebase Auth SDK يفتح Google Account Picker (native)
    ↓
المستخدم يختار حسابه
    ↓
Firebase يرجع ID Token للموبايل
    ↓
الموبايل يرسل: POST /api/v1/auth/google
    ↓
Backend يتحقق من Token (بدون kreait — JWT verification مباشر)
    ↓
نفس response shape كـ OTP verify
```

#### C. API Impact
```
POST /api/v1/auth/google
Body: {
  "firebase_token": "eyJhbGci...",
  "device_id": "optional"
}
Response: {
  "access_token": "...",
  "refresh_token": "...",
  "auth_outcome": "login" | "registration",
  "requires_profile_completion": true | false,
  "next_step": "home" | "profile_completion",
  "onboarding_prefill": {
    "name": "Ronald Richards",
    "email": "ronald@gmail.com",
    "avatar_url": "https://..."
  },
  "user": { ... }
}
```

#### D. Backend Verification (بدون kreait)
```php
// FirebaseAuthService.php
// 1. Decode JWT header → get kid
// 2. Fetch Google public keys: https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com
// 3. Verify signature + expiry + audience (Firebase project ID)
// 4. Extract: uid, email, name, picture
```

#### E. Business Rules
- إذا `auth_outcome = "registration"` → `onboarding_prefill` يحتوي name + email + avatar من Google
- Backend يحاول استخراج `phone_number` من Firebase token — إذا موجود يُحفظ تلقائياً
- إذا `phone_number` غير موجود في Firebase → `phone_number = null` على الـ User — مقبول
- المستخدم بدون phone يستخدم التطبيق بالكامل EXCEPT الحجز
- عند محاولة الحجز بدون phone → backend يرجع `PHONE_REQUIRED` error → موبايل يعرض prompt لإضافة الرقم
- إذا `requires_profile_completion = true` → Screen A05

#### F. Assumptions
- **PA-A04-001:** إذا Google Sign-In فشل (المستخدم ألغى أو خطأ) → يعود لـ Screen A01 مع رسالة.
- **PA-A04-002:** Firebase project ID مخزون في `.env` — لا hardcoded.

---

### Screen A05 — Profile Completion (Onboarding)
> Source: No image — built from confirmed decisions (LD-001, LD-008)
> Status: Assumed — تظهر فقط عند `requires_profile_completion = true`

#### A. Visual Summary (Assumed)
شاشة تكملة الملف الشخصي بعد أول تسجيل. تعرض:
- Header: "Complete your profile"
- Avatar upload (اختياري)
- Name input (إلزامي) — pre-filled من Google إذا Google registration
- Email input (اختياري) — pre-filled من Google
- Phone number (إلزامي لمستخدمي Google — فارغ لمستخدمي OTP لأنه موجود)
- City selector (إلزامي — من قائمة Cities)
- Area selector (اختياري — من قائمة Areas بناءً على City)
- زر "Save & Continue"

#### B. Functional Purpose
جمع البيانات الأساسية المطلوبة قبل الوصول للتطبيق.

#### C. Business Rules
- Name إلزامي — يظهر في Home Screen greeting
- City إلزامي — يحدد الـ default_city_id لفلتر المحتوى
- OTP users: phone_number موجود ومتحقق مسبقاً → لا يُطلب
- Google users: phone_number يُعرض إذا سُحب من Firebase (pre-filled)، اختياري في هذه المرحلة
- بدون phone → يكمل لـ Home Screen طبيعياً، يُحظر فقط عند الحجز
- بعد الحفظ → `onboarding_completed_at` يُضبط → Home Screen

#### D. API Impact
```
PUT /api/v1/me
Body: {
  "name": "Ronald Richards",
  "email": "optional",
  "phone_number": "optional for Google users",
  "avatar": "file upload optional",
  "default_city_id": 1,
  "default_area_id": null
}
```

#### E. Open Questions Added
- **OQ-026 [RESOLVED]:** Phone number = optional for Google users at registration. Required only at booking time. See LD-036.

---


---

### Screen S01 — Search Bar Interaction (Search Entry)
> Source: No image — built from confirmed decisions (LD-013, LD-014, LD-012)
> Status: Assumed

#### A. Visual Summary (Assumed)
تُفتح عند tap على شريط البحث في Home Screen. تعرض:
- Search input نشط مع cursor
- Keyboard مفتوح
- قسم "Recent Searches" — آخر عمليات بحث المستخدم (إذا موجودة)
- قسم "Popular Categories" — shortcuts للـ categories (icons)
- نتائج فورية تظهر أثناء الكتابة (live search / debounced)

#### B. Functional Purpose
نقطة الدخول للبحث النصي الحر عبر الأندية والملاعب والمناطق والرياضات.

#### C. Primary Actor
Mobile User (مسجّل + غير مسجّل)

#### D. User Actions
| Action | Description |
|---|---|
| Type query | Live search — debounced 300ms |
| Tap recent search | يُعيد نفس البحث |
| Tap category shortcut | يفلتر مباشرة بهذه الرياضة |
| Tap filter icon | يفتح Screen S02 (Filter Sheet) |
| Tap result card | يفتح Club Detail |
| Clear search | يُظهر Recent Searches مجدداً |
| Tap Back | يعود لـ Home |

#### E. Search Scope (LD-013 مؤكد)
البحث يضرب في:
- `clubs.name_ar` + `clubs.name_en`
- `venues.name_ar` + `venues.name_en`
- `areas.name_ar` + `areas.name_en`
- `sport_categories.name_ar` + `sport_categories.name_en`

#### F. Business Rules
- البحث scoped بـ `default_city_id` المستخدم تلقائياً (LD-012)
- Debounce: 300ms بعد آخر ضغطة قبل إرسال الطلب
- Min chars للبحث: 2 حرف على الأقل
- Recent searches: تُحفظ محلياً على الموبايل (لا backend) — آخر 5 عمليات
- النتائج تعرض Clubs فقط (ليس venues منفردة) — Venue يظهر ضمن Club parent

#### G. States
| State | Description |
|---|---|
| `idle` | Recent searches + Category shortcuts |
| `typing` | Live results تظهر أسفل |
| `loading` | Debounce في انتظار النتيجة |
| `results` | قائمة Clubs مطابقة |
| `empty` | "لا نتائج لـ X" + اقتراحات |
| `error` | خطأ شبكة — retry |

#### H. API Impact
```
GET /api/v1/clubs?q={query}&city_id={default_city_id}&page=1&per_page=10

Response:
{
  "data": [
    {
      "id", "name", "area": { "name" },
      "logo_url", "cover_image_url",
      "avg_rating", "sports": [],
      "price_from", "distance_km",
      "match_type": "club" | "venue" | "area" | "sport"
    }
  ],
  "meta": { "total", "current_page", "last_page" }
}
```
- `match_type` يخبر الموبايل بم تطابق النتيجة (يساعد على highlight النص)
- `distance_km` إذا GPS متاح

#### I. Mobile App Impact
- Debounce 300ms — لا ترسل طلب لكل حرف
- Highlight نص المطابقة في النتيجة (مثلاً: اسم النادي يُحاط بـ bold)
- Recent searches تُخزَّن في local storage فقط — لا API

#### J. Assumptions
- **PA-S01-001:** البحث يعمل باللغتين العربية والإنجليزية في نفس الوقت
- **PA-S01-002:** Recent searches محلية فقط — تُمسح عند clear cache
- **PA-S01-003:** الحد الأدنى للبحث: حرفان

---

### Screen S02 — Filter Bottom Sheet
> Source: No image — built from confirmed decisions (LD-014)
> Status: Assumed

#### A. Visual Summary (Assumed)
Bottom sheet يظهر من الأسفل عند tap زر الفلتر. يعرض:
- Handle bar في الأعلى (drag to dismiss)
- Title: "Filter"
- **قسم Sport:** Grid من Sport Category chips (multi-select)
- **قسم Price Range:** Slider أو dual input (min/max)
- **قسم Rating:** Row من نجوم أو slider (1★ → 5★)
- **قسم Area:** اختياري — إذا أراد المستخدم تضييق المنطقة داخل مدينته
- زر "Apply Filters" أخضر
- زر "Reset" / "Clear all"

#### B. Functional Purpose
تمكين المستخدم من تصفية قائمة الأندية بمعايير متعددة في نفس الوقت.

#### C. User Actions
| Action | Description |
|---|---|
| Tap sport chip | Toggle تحديد/إلغاء رياضة (multi-select) |
| Drag price slider | تحديد نطاق السعر (min/max) |
| Tap rating stars | تحديد الحد الأدنى للتقييم |
| Select area | تضييق المنطقة (اختياري) |
| Tap Apply | تطبيق الفلاتر + إغلاق الـ sheet |
| Tap Reset | مسح كل الفلاتر |
| Drag down / tap outside | إغلاق بدون حفظ |

#### D. Filter Parameters (LD-014 مؤكد)
| Parameter | Type | Notes |
|---|---|---|
| `sport_category_id` | array of IDs | multi-select من الـ categories |
| `region_id` / `area_id` | single ID | من Areas تحت مدينة المستخدم |
| `price_min` | integer | بالوحدة المحلية (SYP) |
| `price_max` | integer | بالوحدة المحلية |
| `rating_min` | decimal | 1.0 → 5.0، step 0.5 |

#### E. Business Rules
- Sport chips تُجلب من `GET /api/v1/categories` (cached)
- Areas تُجلب من `GET /api/v1/cities/{city_id}/areas` بناءً على `default_city_id`
- Price range: يعرض min/max السعر الفعلي الموجود في DB (لا يعرض 0 → infinity)
- الفلاتر تتراكم — يمكن تطبيق أكثر من فلتر في نفس الوقت
- عدد الفلاتر النشطة يظهر كـ badge على أيقونة الفلتر في Home/Search

#### F. Data Dependencies
- Sport categories: من Module 03 (cached)
- Areas: من `GET /api/v1/cities/{id}/areas`
- Price range boundaries: من `GET /api/v1/clubs/price-range?city_id=X` — يرجع `{ min_price, max_price }` لضبط الـ slider

#### G. API Impact — New Endpoint Needed
```
GET /api/v1/clubs/price-range
Query: city_id (required)
Auth: Public
Response: { "min_price": 10000, "max_price": 500000, "currency": "SYP" }
Notes: يُستخدم لضبط حدود الـ price slider في Filter Sheet

GET /api/v1/cities/{city_id}/areas
Auth: Public
Response: [ { "id", "name_ar", "name_en" } ]
Notes: يُستخدم لملء Area selector في Filter
```

**Filtered Club Listing:**
```
GET /api/v1/clubs
Query params:
  q=           (text search)
  city_id=     (always present — from user's default)
  area_id=     (optional — from filter)
  sport_category_id[]=1&sport_category_id[]=2  (multi-value)
  price_min=   (optional)
  price_max=   (optional)
  rating_min=  (optional)
  sort=        distance | rating | price_asc | price_desc
  lat=, lng=   (optional — for distance sort)
  page=, per_page=
```

#### H. Mobile App Impact
- Filter state يُحفظ محلياً أثناء الجلسة
- Badge على فلتر icon يعرض عدد الفلاتر المفعّلة (مثلاً: 🔧 3)
- Price slider يُعرض بالـ currency الصحيحة (SYP)
- Sport chips تُحمَّل من الـ API — لا hardcoded

#### I. Assumptions
- **PA-S02-001:** Price filter يطبَّق على `venues.pricing_tiers` → `price_from` على الـ Club (computed min).
- **PA-S02-002:** Sort options: المسافة (إذا GPS)، التقييم، السعر تصاعدي/تنازلي.
- **PA-S02-003:** Default sort: إذا GPS متاح → distance، إذا لا → rating.
- **PA-S02-004:** Area filter اختياري — إذا لم يختر → يعرض كل مدينته.

#### J. Risks
- Price range boundaries تتغير مع إضافة ملاعب جديدة → Slider يجب يُحدَّث بجلب `/price-range` كل مرة تُفتح الـ Filter Sheet
- إذا المستخدم اختار فلاتر صارمة جداً → نتائج فارغة → يجب empty state مع "خفّف الفلاتر"

---

### Screen S03 — Search/Filter Results
> Source: No image — نفس component قائمة الأندية (Screens 02، 03)
> Status: Assumed — لا شاشة مستقلة جديدة

#### A. Visual Summary (Assumed)
نفس قائمة الأندية العمودية مع:
- Summary bar في الأعلى: "12 نادي في دمشق" + فلاتر نشطة كـ chips قابلة للحذف
- كل كارد: صورة، اسم النادي، منطقة، تقييم، sports icons، `price_from`، مسافة

#### B. Key UI Elements
- **Active filter chips:** كل فلتر مفعّل يظهر كـ chip مع X للإزالة الفردية
- **Results count:** "12 نتيجة" في الأعلى
- **Sort dropdown:** المسافة | التقييم | السعر ↑ | السعر ↓
- **Empty state:** "لا نتائج" + "امسح الفلاتر"

#### C. API Impact
نفس `GET /api/v1/clubs` مع الـ query params من S02.

#### D. Business Rules
- إزالة chip فلتر → يُعيد الطلب بدون هذا الفلتر فوراً
- "امسح الكل" → يُعيد الطلب بدون فلاتر
- Infinite scroll أو pagination بـ Load More

---


---

### Screen B01 — Slot Selection
> Source: No image — built from confirmed decisions (LD-016, LD-017, LD-041)
> Status: Assumed

#### A. Visual Summary (Assumed)
شاشة اختيار الوقت لملعب محدد. تعرض:
- Header: اسم الملعب + اسم النادي
- Date picker أفقي (اليوم + الأيام القادمة)
- Duration selector: الخيارات المتاحة من `VenueAllowedDurations` (مثلاً: 45 دقيقة / 90 دقيقة)
- Grid أو قائمة الـ slots المتاحة لليوم المختار والمدة المختارة
- كل slot يعرض: وقت البداية، وقت النهاية، السعر (من VenuePricingTier)
- Slots المحجوزة تظهر بلون مختلف (مغلقة)
- زر "Book" على الـ slot المختار

#### B. Business Rules
- Slots تُولَّد ديناميكياً من `VenueAvailabilityRule` + `VenuePricingTier` + الحجوزات الموجودة
- Slot متاح = ضمن ساعات العمل + ليس محجوزاً (بأي مصدر: mobile أو manual)
- السعر يُجلب من `VenuePricingTier` المطابق لوقت الـ slot + المدة المختارة
- إذا لا يوجد pricing tier للوقت → الـ slot لا يظهر (غير قابل للحجز)
- المستخدم يجب يختار duration أولاً → تتحدث الـ slots
- Race condition: إذا حجز شخصان نفس الـ slot في نفس الوقت → DB transaction + SELECT FOR UPDATE

#### C. API Impact
```
GET /api/v1/clubs/{club_id}/venues/{venue_id}/slots
Query: date (required), duration_minutes (required), sport_category_id (required for multi-sport)
Auth: Public

Response:
{
  "date": "2025-06-15",
  "venue_id": "uuid",
  "duration_minutes": 90,
  "slots": [
    {
      "start_time": "08:00",
      "end_time": "09:30",
      "price": 50000,
      "currency": "SYP",
      "is_available": true
    },
    {
      "start_time": "09:30",
      "end_time": "11:00",
      "price": 50000,
      "is_available": false,
      "unavailability_reason": "booked"
    }
  ]
}
```

#### D. Risks
- Slot يظهر متاحاً للمستخدم لكنه يُحجز بين اللحظة التي يراه والدفع → Race condition
- Mitigation: DB-level lock عند إنشاء الحجز، لا عند عرض الـ slots

---

### Screen B02 — Payment Selection + Initiation
> Source: No image — built from confirmed decisions (LD-003, LD-041)
> Status: Assumed

#### A. Visual Summary (Assumed)
شاشة الدفع بعد اختيار الـ slot. تعرض:
- ملخص الحجز: اسم النادي، الملعب، التاريخ، الوقت، المدة، السعر
- قائمة طرق الدفع المتاحة (radio buttons):
  - Syriatel Cash
  - MTN Cash
  - Fatora (بطاقة مصرفية)
  - SamaPay
- إذا MTN أو Syriatel: حقل رقم الهاتف (pre-filled من `user.phone_number`)
- زر "Pay Now"

#### B. Business Rules
- طرق الدفع تُجلب من API — Admin يفعّل/يعطّل كل طريقة من Dashboard
- MTN + Syriatel: يتطلبان رقم هاتف → pre-filled لكن المستخدم يقدر يغيره
- Fatora + SamaPay: لا يتطلبان رقم هاتف — WebView يُفتح مباشرة
- عند "Pay Now":
  - `POST /api/v1/payments/initiate`
  - Response يحدد `flow: otp | webview`

#### C. API Impact
```
GET /api/v1/payment-methods
Auth: Public
Response: [ { "id", "name", "type": "otp|webview", "icon_url", "is_active" } ]

POST /api/v1/payments/initiate
Body: { booking_id, provider, phone_number? }
```

---

### Screen B03 — OTP Confirmation (MTN / Syriatel)
> Source: No image — built from confirmed decisions (LD-003, LD-041)
> Status: Assumed — يظهر فقط لـ MTN + Syriatel

#### A. Visual Summary (Assumed)
- نص: "تم إرسال رمز التأكيد إلى [phone]"
- 5 أو 6 مربعات OTP input
- Countdown timer للـ resend (Syriatel فقط)
- زر "Confirm Payment"
- زر "Resend" (Syriatel فقط — بعد انتهاء الـ countdown)

#### B. Business Rules
- المستخدم يدخل OTP الذي أرسله المزود لهاتفه
- MTN: resend = إعادة `initiatePayment` بنفس invoice + sequence++
- Syriatel: resend = `resendOTP(transactionId)`
- عند نجاح التأكيد → Booking status = `confirmed` → FCM فوري للأدمن + للمستخدم

#### C. API Impact
```
POST /api/v1/payments/{id}/confirm
Body: { "otp_code": "12345" }
Response: { "status": "completed", "booking": { booking_code, date, time } }

POST /api/v1/payments/{id}/resend-otp
Response: { "message": "تم إرسال رمز جديد" }
```

---

### Screen B04 — WebView Payment (Fatora / SamaPay)
> Source: No image — built from confirmed decisions (LD-003, LD-041)
> Status: Assumed — يظهر فقط لـ Fatora + SamaPay

#### A. Visual Summary (Assumed)
- WebView يُفتح على الـ hosted payment page (Fatora/SamaPay URL)
- المستخدم يدخل بيانات البطاقة على صفحة الـ gateway مباشرة
- بعد إتمام الدفع → Fatora تستدعي callback على الـ backend
- Mobile يُغلق الـ WebView + يـ poll على `/payments/{id}/status`

#### B. Business Rules
- الـ backend يستلم callback من Fatora/SamaPay
- عند نجاح الـ callback → Booking `confirmed` → FCM فوري
- Mobile يـ poll كل 3 ثواني لمدة max 2 دقيقة
- إذا انتهت المدة بدون تأكيد → يعرض "تأكد من حالة الدفع"

#### C. API Impact
```
GET /api/v1/payments/{id}/status
Auth: Required
Response: { "status": "pending|completed|failed", "booking_status": "confirmed|null" }

POST /api/v1/payments/callback/fatora  (server-to-server)
POST /api/v1/payments/callback/sama_pay  (server-to-server)
```

---

### Screen B05 — Booking Confirmation
> Source: No image — built from confirmed decisions (LD-041, LD-043)
> Status: Assumed

#### A. Visual Summary (Assumed)
شاشة تأكيد الحجز بعد نجاح الدفع. تعرض:
- أيقونة ✅ كبيرة
- "تم الحجز بنجاح"
- Booking Code (GR0175)
- ملخص: النادي، الملعب، التاريخ، الوقت، المبلغ المدفوع
- زر "عرض تفاصيل الحجز" → Screen 07 (Booking Details)
- زر "العودة للرئيسية"

#### B. FCM Notifications المُرسلة عند هذه اللحظة
| المستلم | المحتوى |
|---|---|
| المستخدم | "تم تأكيد حجزك في [نادي الفيحاء] يوم [التاريخ]" |
| Super Admin | "حجز جديد: [booking_code] — [Club Name] — [Amount]" |
| Club Admin | "حجز جديد في [Venue Name]: [booking_code] — [Date/Time]" |

#### C. FCM Job Structure
```php
// BookingConfirmedNotificationJob
// Dispatched to: database queue
// Sends to:
//   1. user.fcm_token
//   2. AdminUser::where('role', 'super_admin')->pluck('fcm_token')
//   3. ClubStaffMember::where('club_id', X)->where('role', 'club_admin')->pluck('fcm_token')
```

### Screen 01 — Home Screen (الشاشة الرئيسية)

> Source images: 08_0036_1s, 09_0039_1s, 10_0041_6s, 11_0043_5s, 12_0050_0s
> All five images are the same screen in different states (loaded, skeleton loading, scrolled variations).

---

#### A. Visual Summary
الشاشة الرئيسية للتطبيق بعد تسجيل الدخول. تعرض:
- تحية شخصية باسم المستخدم مع أيقونة emoji حسب وقت اليوم ("Good morning ☀️")
- أيقونة الإشعارات في الأعلى يمين
- شريط بحث مع زر فلتر
- قسم Categories أفقي قابل للتمرير مع "View all"
- قسم Popular grounds (ملاعب مميزة) أفقي بكروت مع صور
- قسم Nearby you (مشار إليه في scrolled state) مع "View all"
- شريط تنقل سفلي: Home، Booking، Event، History، Profile

---

#### B. Functional Purpose
- نقطة الدخول الرئيسية للمستخدم بعد المصادقة
- تمكين الاستكشاف السريع للرياضات والملاعب
- توجيه المستخدم نحو الحجز عبر Categories أو Popular/Nearby venues
- الوصول السريع لكل أقسام التطبيق عبر Bottom Navigation

---

#### C. Primary Actor
- **Mobile User** (مستخدم مسجّل)
- الشاشة تعرض اسم المستخدم — إذن Guest لا يصل لهذه الشاشة بالشكل الكامل، أو يرى نسخة مقتطعة

---

#### D. User Actions
| Action | Description |
|---|---|
| Tap notification icon | فتح شاشة الإشعارات |
| Tap search bar | الانتقال لشاشة البحث الكاملة |
| Tap filter button | فتح فلاتر البحث/الاستكشاف |
| Tap category icon | عرض ملاعب هذه الرياضة تحديداً |
| Tap "View all" (Categories) | عرض كل الفئات |
| Tap venue card | فتح تفاصيل الملعب |
| Tap "View all" (Popular) | عرض كل الملاعب المميزة |
| Tap "View all" (Nearby) | عرض كل الملاعب القريبة |
| Tap Bottom Nav items | التنقل بين: Home، Booking، Event، History، Profile |
| Scroll vertically | الكشف عن sections إضافية (Nearby you...) |

---

#### E. Data Inputs
- لا يوجد إدخال مباشر في هذه الشاشة
- Search bar هو entry point فقط — لا يبحث من الشاشة الرئيسية مباشرة (ينقل لشاشة بحث)
- الفلتر: مدخلاته غير واضحة بعد — تحتاج شاشة مخصصة

---

#### F. Data Outputs (ما تعرضه الشاشة)

**Header:**
- `user.name` — الاسم الأول على الأقل
- greeting_text — نص التحية المبني على وقت اليوم (لوجيك محلي أو من API)
- `notifications_unread_count` — عدد الإشعارات غير المقروءة (badge)

**Categories Section:**
- `categories[]` — قائمة الفئات النشطة، كل فئة:
  - `id`, `name`, `icon_url`, `slug`
  - مرتبة حسب `sort_order`
  - مقيّدة بأول X فئات (preview — ليس كلهم)

**Popular Grounds Section:**
- `popular_venues[]` — ملاعب مميزة/مرشحة، كل ملعب:
  - `id`, `name`, `city`, `primary_image_url`
  - `sport_icons[]` — أيقونات الرياضات المتاحة في هذا الملعب (ظاهرة في الكروت)
  - `is_featured` flag

**Nearby You Section:**
- `nearby_venues[]` — ملاعب قريبة جغرافياً:
  - نفس حقول Popular + `distance`
  - **تتطلب location permission من المستخدم**

---

#### G. States

| State | Description | Visual Evidence |
|---|---|---|
| `loaded` | الشاشة محملة بالكامل مع بيانات حقيقية | Images 08, 10, 11 |
| `skeleton_loading` | حالة التحميل — placeholders رمادية بدل المحتوى | Image 12 |
| `scrolled` | المستخدم مرر لأسفل وظهر قسم Nearby you | Image 09 |
| `notifications_badge` | أيقونة الجرس تعرض badge عند وجود إشعارات غير مقروءة | مستنتج |
| `no_nearby_venues` | لو لم يُعطِ المستخدم permission للموقع أو لا يوجد ملاعب قريبة | مستنتج |

---

#### H. Validation Rules
- Categories: يُعرض فقط الفئات التي `is_active = true`
- Popular Venues: يُعرض فقط الملاعب التي `status = active` و `is_featured = true`
- Nearby Venues: يتطلب إحداثيات المستخدم — إذا غير متاحة يُخفى القسم أو يُعرض رسالة
- Greeting text: يتحدد بناءً على وقت الجهاز أو وقت الـ API (morning/afternoon/evening)

---

#### I. Business Rules
- الصفحة الرئيسية تُحمَّل بعد المصادقة الناجحة مباشرة
- المستخدم يرى اسمه الشخصي — إذن `name` حقل إلزامي يُجمع في onboarding
- Categories تُعرض بالترتيب الذي يحدده الأدمن (`sort_order`)
- Popular Grounds يحددها الأدمن عبر `is_featured = true` على الملعب
- Nearby Venues تعتمد على الـ GPS — النظام يقبل `latitude/longitude` من الموبايل
- عدد العناصر في كل section محدود في الـ preview (مثلاً: 4 categories, 5 venues) — "View all" يفتح قائمة كاملة
- كل section "View all" ينقل لشاشة مستقلة

---

#### J. Required Permissions
| Action | Who |
|---|---|
| View home screen | Authenticated Mobile User |
| View categories | Public (no auth needed for list) |
| View popular venues | Public |
| View nearby venues | Authenticated + Location Permission |
| View notifications count | Authenticated Mobile User |

---

#### K. Backend Impact
**Models needed:**
- `User` — للاسم والتحية
- `SportCategory` — للقسم الأول
- `Venue` — للقسمين الثاني والثالث
- `Notification` — لعدد الإشعارات غير المقروءة

**Services needed:**
- `HomeScreenService` أو `FeedService` — يجمع بيانات الـ home في استدعاء واحد أو منفصل
- `NearbyVenueService` — يحسب المسافة بناءً على lat/long

**Queries:**
- Categories: `WHERE is_active = true ORDER BY sort_order ASC LIMIT n`
- Popular Venues: `WHERE status = active AND is_featured = true ORDER BY created_at DESC LIMIT n`
- Nearby Venues: Haversine formula أو PostGIS إذا PostgreSQL

**Events:**
- لا يوجد event يُطلق من هذه الشاشة مباشرة

---

#### L. Database Impact
- لا جداول جديدة — تستخدم: `users`, `sport_categories`, `venues`
- إضافة على `venues`: تأكيد وجود `latitude`, `longitude`, `is_featured`
- إضافة جدول `notifications` (مُشار إليه بأيقونة الجرس) — تفاصيله تحتاج شاشة Notifications
- **تأكيد مطلوب:** هل `sport_icons` الظاهرة على كروت الملاعب تعني أن الملعب يدعم رياضات متعددة؟ (venue multi-sport) — راجع OQ-011 أدناه

---

#### M. Admin Panel Impact
- **Categories Management:** الأدمن يتحكم بما يظهر وترتيبه في هذا القسم مباشرة
- **Featured Venues:** الأدمن يُعلّم أي ملعب كـ `is_featured` ليظهر في Popular Grounds
- **Content:** نص التحية إذا أراد الأدمن تخصيصه يُخزّن في SystemSettings
- **لا يوجد admin section مخصص لـ "Home Screen layout"** — التحكم يكون عبر: categories order + featured venues

---

#### N. API Impact

**Option A — Single Home Feed Endpoint (موصى به)**
```
GET /api/v1/home
Auth: Required
Query Params: latitude, longitude (optional)

Response:
{
  "greeting": {
    "text": "Good morning",
    "user_name": "Ronald"
  },
  "notifications_unread_count": 3,
  "categories": [
    { "id", "name", "icon_url", "slug" }
    // max 6 items for preview
  ],
  "popular_venues": [
    { "id", "name", "city", "primary_image_url", "sport_icons": [] }
    // max 5 items
  ],
  "nearby_venues": [
    { "id", "name", "city", "primary_image_url", "distance_km", "sport_icons": [] }
    // max 5 items — empty array if no location provided
  ]
}
```

**Option B — Separate Endpoints**
- `GET /api/v1/categories?limit=6`
- `GET /api/v1/venues?featured=true&limit=5`
- `GET /api/v1/venues?nearby=true&lat=X&lng=Y&limit=5`
- `GET /api/v1/me/notifications/count`

> **Recommendation:** Option A أفضل لأداء الموبايل — شاشة واحدة = طلب واحد.
> لكن Option B أكثر مرونة ويسهل الـ caching لكل section بشكل مستقل.
> **القرار يحتاج تأكيد من المطور.**

---

#### O. Mobile App Impact
- الموبايل يحتاج `latitude` و `longitude` من GPS لإرسالها مع طلب الـ Home
- الـ Skeleton loading state يعني الموبايل يعرض placeholders أثناء تحميل الـ API
- Bottom Navigation بـ 5 items: Home, Booking, Event, History, Profile — كل tab يحتاج API خاص
- `notifications_unread_count` يُعرض كـ badge على أيقونة الجرس
- الـ greeting يُحسب محلياً على الموبايل أو يُعاد من الـ API — قرار TBD
- كروت الملاعب تعرض `sport_icons[]` — الموبايل يحتاج هذه القائمة من الـ API

---

#### P. Assumptions
- **PA-001:** الشاشة تتطلب مصادقة — المستخدم غير المسجل لا يصل لها (أو يرى نسخة مقيدة).
- **PA-002:** "Nearby you" يعتمد على GPS — إذا رفض المستخدم الإذن يُخفى القسم.
- **PA-003:** الـ greeting ("Good morning/afternoon/evening") يُحدد بوقت الجهاز.
- **PA-004:** كروت الملاعب تعرض أيقونات رياضات متعددة — يعني ملعب واحد يمكن أن يدعم أكثر من رياضة.
- **PA-005:** Bottom nav item "Event" يشير لوحدة Events/Tournaments — لم تظهر في التحليل بعد.
- **PA-006:** عدد العناصر في الـ preview محدود (4-6 categories, 5 venues) — الكل عبر "View all".

---

#### Q. Risks / Edge Cases
- **RC-001:** إذا لم توجد ملاعب مميزة (`is_featured = true`) يكون قسم Popular فارغاً — يجب معالجة الـ empty state.
- **RC-002:** إذا لم يوجد أي category نشطة — Home تبدو فارغة جداً.
- **RC-003:** المستخدم يرفض إذن الموقع — Nearby You section يجب أن يُخفى بأمان.
- **RC-004:** الـ greeting باللغة العربية/الإنجليزية — يجب التحقق من أن النظام يرجع النص بلغة المستخدم.
- **RC-005:** venue يدعم رياضات متعددة — هذا يؤثر على كيفية الفلترة بالـ category (هل venue يظهر في أكثر من category؟).

---

#### R. Recommendations
- استخدم **Single Home Feed Endpoint** لتقليل عدد الطلبات عند فتح التطبيق.
- خزّن عدد `notifications_unread_count` في الـ cache وحدّثه عبر WebSocket أو polling بسيط.
- اجعل عدد عناصر كل section (4، 5، 6) قابلاً للتعديل من Admin عبر SystemSettings.
- وضّح نموذج "multi-sport venue" مبكراً — يؤثر على schema الملاعب والفلترة.

---

### Screen 02 — Nearby You (Full List)
> Source: 15_0067_8s.jpg
> Also visible as a section in Home Screen (16_0070_7s.jpg, 22_0117_2s.jpg)

#### A. Visual Summary
قائمة عمودية كاملة للأندية/الملاعب القريبة من المستخدم. كل كارد يحتوي: صورة كبيرة، مسافة (KM) في الزاوية، اسم النادي، أيقونة الموقع + اسم المنطقة.

#### B. Functional Purpose
عرض كل الأندية القريبة من موقع المستخدم مرتبة بالمسافة. هي الـ "View all" لقسم Nearby في الـ Home Screen.

#### C. Primary Actor
Mobile User (مسجّل + أعطى إذن الموقع)

#### D. User Actions
- Scroll عمودي لاستعراض القائمة
- Tap على كارد → فتح Club Detail Screen
- Back → الرجوع للـ Home

#### E. Data Outputs
- `club.name`, `club.primary_image_url`, `club.area.name`, `distance_km`
- قائمة مرتبة تصاعدياً بالمسافة

#### F. States
- `loaded` — قائمة بأندية فعلية + مسافات
- `empty` — لا يوجد أندية قريبة (يجب عرض رسالة + اقتراح توسيع النطاق)
- `location_denied` — المستخدم رفض إذن الموقع

#### G. Backend Impact
- Query: `SELECT clubs WHERE status=active ORDER BY distance(lat,lng) ASC`
- Haversine formula أو PostGIS extension
- `GET /api/v1/clubs?lat=X&lng=Y&sort=distance&page=1`

#### H. API Impact
```
GET /api/v1/clubs
Query: lat, lng, sort=distance, page, per_page
Response: paginated clubs with distance_km
```

#### I. Mobile App Impact
- يرسل GPS coordinates مع كل طلب
- يعرض KM badge على كل صورة
- infinite scroll أو pagination

#### J. Assumptions
- المسافة تُحسب من موقع المستخدم الحالي وليس من منطقته المحفوظة
- لا يوجد radius filter مرئي — النظام يعرض كل الأندية في منطقته مرتبة بالمسافة

#### K. Risks
- إذا المستخدم في منطقة بلا أندية → empty state
- GPS accuracy تؤثر على ترتيب النتائج

---

### Screen 03 — Category Filter Results (Football)
> Source: 16_0070_7s.jpg

#### A. Visual Summary
شاشة قائمة الأندية مفلترة بـ category محددة (Football). Header يعرض اسم الرياضة كعنوان. كل كارد: صورة كبيرة، مسافة KM، اسم النادي، أيقونة موقع + اسم المنطقة.

#### B. Functional Purpose
عرض كل الأندية التي تحتوي ملاعب للرياضة المختارة، مع المسافة. تُفتح بعد tap على category في Home Screen.

#### C. Primary Actor
Mobile User

#### D. User Actions
- Scroll عمودي
- Tap على كارد → Club Detail
- Back → Home Screen

#### E. Data Outputs
- `club.name`, `club.primary_image_url`, `club.area.name`, `distance_km`
- مفلترة: فقط الأندية التي فيها venue يدعم هذه الرياضة

#### F. Business Rules
- النتائج = أندية تحتوي على venue واحد على الأقل مرتبط بهذه الـ sport_category
- مرتبة بالمسافة (إذا توفر GPS) أو بـ featured أولاً

#### G. API Impact
```
GET /api/v1/clubs?sport_category_id={id}&lat=X&lng=Y&sort=distance
```
- نفس endpoint قائمة الأندية + filter إضافي

#### H. Admin Panel Impact
- لا يوجد شيء إضافي — يعتمد على sport_categories وvenues الموجودة

#### I. Mobile App Impact
- عنوان الشاشة = اسم الـ category المختارة (يجيه من الـ API)
- نفس component كارد الـ Nearby You

---

### Screen 04 — Club/Venue Detail (scrolled — lower section)
> Source: 18_0095_5s.jpg

#### A. Visual Summary
الجزء السفلي من شاشة تفاصيل النادي/الملعب (بعد scroll). يعرض:
- Duration selector أفقي: "3 Hour" (selected)، "1 Hour"، "2 Hour"
- قسم Reviews مع "View all" — يعرض أول review: اسم، avatar، نجمة، تقييم 4.5، timestamp، نص
- قسم "Our popular features": قائمة مميزات (Hiring partners, Grass pitch, Natural grass pitch, Miniature field, Outdoor/indoor)
- زر "Book now" أخضر ثابت في الأسفل
- زر Share في الأعلى يمين
- زر Back في الأعلى يسار

#### B. Functional Purpose
- تمكين المستخدم من اختيار مدة الحجز
- عرض تقييمات المستخدمين السابقين
- عرض مميزات الملعب
- الانتقال لإتمام الحجز

#### C. Primary Actor
Mobile User

#### D. User Actions
| Action | Description |
|---|---|
| Tap duration option | اختيار مدة الحجز (1H / 2H / 3H) |
| Tap "View all" reviews | فتح قائمة كل التقييمات |
| Tap "Book now" | الانتقال لشاشة اختيار التاريخ والوقت |
| Tap Share | مشاركة النادي |
| Tap Back | العودة |

#### E. Data Outputs
- `venue.allowed_durations[]` — الخيارات الظاهرة في Duration selector
- `venue.reviews[]` — أول N reviews (preview)
- `venue.reviews_avg_rating` — متوسط التقييم
- `venue.features/amenities[]` — قائمة المميزات
- `venue.name`, صورة غلاف

#### F. Business Rules
- Duration options = `VenueAllowedDurations` — محددة من الأدمن مسبقاً
- المستخدم يختار duration أولاً قبل الضغط Book now
- السعر يتغير حسب الـ duration المختارة (يجب عرضه)
- لا يُسمح للمستخدم بإدخال مدة حرة — فقط الخيارات المعروضة

#### G. Database Impact
- `venue_allowed_durations` table يُغذي الـ Duration selector
- `reviews` table يُغذي قسم Reviews
- `amenities` JSON field على Venue يُغذي Popular Features

#### H. Backend Impact
- `VenueAllowedDuration` model
- `Review` model مع AVG rating computed
- Venue detail response يجب يشمل: allowed_durations, reviews_preview, avg_rating, amenities

#### I. API Impact
```
GET /api/v1/clubs/{club_id}/venues/{venue_id}
Response includes:
{
  "allowed_durations": [45, 60, 90, 120, 180],
  "reviews_preview": [ { user, rating, body, created_at } ],
  "reviews_avg_rating": 4.5,
  "reviews_count": 142,
  "amenities": ["Grass pitch", "Outdoor/indoor", ...]
}
```

#### J. Assumptions
- **PA-Screen04-001:** Duration selector يعرض المدد بالساعات (1 Hour, 2 Hour, 3 Hour) — يعني `allowed_durations` تُخزّن بالدقائق (60, 120, 180) وتُعرض كساعات.
- **PA-Screen04-002:** السعر يُعرض مع كل خيار duration على الشاشة (لم يظهر في هذا الجزء — قد يكون في الجزء العلوي من نفس الشاشة).
- **PA-Screen04-003:** "Book now" ينقل للـ booking flow حيث المستخدم يختار التاريخ والوقت.

#### K. Risks
- إذا venue ليس عنده allowed_durations → شاشة تفاصيل مكسورة
- السعر لكل duration يجب أن يكون واضحاً — إذا ما ظهر السعر قبل Book now، المستخدم قد يفاجأ

#### L. Recommendations
- اعرض السعر مع كل duration option (مثلاً: "90 min — 50,000 SYP")
- Preview reviews = 2-3 reviews max + AVG rating بارز
- "View all reviews" → شاشة مستقلة للـ reviews مع pagination
---

### Screen 08 — Write a Review
> Source: 26_0128_6s.jpg (empty state), 24_0123_7s.jpg (validation error — Arabic text), 27_0130_6s.jpg (validation error — Arabic keyboard)

#### A. Visual Summary
شاشة كتابة المراجعة. تحتوي على:
- Header: "Write a review" + Back
- Textarea كبير: placeholder "Write your review..."
- رسالة validation حمراء: "Minimum 50 characters required in reviews"
- زر "Submit review" أخضر ثابت في الأسفل
- لا يوجد star rating في هذه الشاشة — النجوم غائبة مرئياً

#### B. Functional Purpose
تمكين المستخدم من كتابة مراجعة نصية لملعب حجزه وأتم الحجز فيه. تُفتح من زر "Write a review" في History Detail.

#### C. Primary Actor
Mobile User — فقط من أتم حجزاً (booking status = completed)

#### D. User Actions
| Action | Description |
|---|---|
| Type in textarea | كتابة نص المراجعة |
| Tap Submit review | إرسال المراجعة — يتحقق من الـ validation أولاً |
| Tap Back | العودة لـ History Detail بدون حفظ |

#### E. Data Inputs
- `rating` — النجوم (إلزامي، 1–5) — **مؤكد، كان في الجزء العلوي غير المرئي من الشاشة**
- `body` — نص المراجعة (اختياري، لكن إذا كُتب minimum 50 حرف)
- `is_anonymous` — checkbox/toggle لإخفاء الهوية (اختياري، default: false)

#### F. Validation Rules
- `body` إلزامي
- `body` minimum 50 حرف — رسالة خطأ حمراء تظهر تحت الـ textarea فوراً
- Submit button يبقى فعّالاً بصرياً لكن validation تمنع الإرسال (أو يُعطَّل حتى يكتمل الشرط)
- النص يدعم العربية (مؤكد من الصور — Arabic keyboard + RTL text)

#### G. Business Rules
- المستخدم يقدر يكتب review فقط على booking بـ status = `completed`
- review واحدة لكل booking — لا تكرار
- بعد الإرسال الناجح → العودة لـ History Detail مع إظهار المراجعة

#### H. States
| State | Description |
|---|---|
| `empty` | Textarea فارغ، placeholder ظاهر |
| `typing_invalid` | نص أقل من 50 حرف، validation error ظاهر |
| `typing_valid` | نص 50 حرف+، جاهز للإرسال |
| `submitting` | loading state على الزر |
| `success` | redirect + confirmation |

#### I. Backend Impact
- `POST /api/v1/bookings/{booking_id}/review`
- Validation: booking belongs to user + status = completed + no existing review
- Minimum 50 chars enforced backend-side أيضاً (ليس فقط frontend)
- `review.body` يجب يدعم UTF-8 كامل (عربي + إنجليزي)

#### J. API Impact
```
POST /api/v1/bookings/{booking_id}/review
Auth: Required
Request: { "body": "نص المراجعة...", "rating": 4.5 }
Validation:
  - body required, min 50 chars
  - rating required (1.0 - 5.0) — pending confirmation OQ-024
  - booking must be completed + belong to auth user
  - no duplicate review per booking
Response: { "review": { id, body, rating, created_at } }
```

#### K. Mobile App Impact
- RTL support مطلوب في الـ textarea
- Character counter مقترح (مثلاً: "23/50") لمساعدة المستخدم
- Validation error يظهر inline تحت الـ textarea فوراً (لا wait للـ submit)

#### L. Assumptions
- **PA-Screen08-001:** Rating (النجوم) غائب من الشاشة المرئية — إما في الجزء العلوي غير المرئي من نفس الشاشة، أو قرار تصميمي بإخفائه. يحتاج تأكيد.
- **PA-Screen08-002:** لا يوجد حد أقصى للأحرف مرئي — نفترض max 1000 حرف.
- **PA-Screen08-003:** الشاشة تُفتح مرتبطة بـ `booking_id` محدد — ليست عامة.

#### M. Risks
- المستخدم يكتب بالعربية → body يُخزَّن كـ UTF-8 → تأكد من collation قاعدة البيانات (`utf8mb4`)
- إذا انقطع الإنترنت أثناء الإرسال → يجب الاحتفاظ بالنص محلياً مؤقتاً

#### N. Open Questions Added
- **OQ-024:** هل شاشة Write a Review تحتوي على star rating selector أم نص فقط؟ الجزء العلوي من الشاشة غير مرئي في الصور.

---

### Screen 09 — History Detail
> Source: 25_0126_1s.jpg

#### A. Visual Summary
شاشة "History detail" — تفاصيل حجز منتهٍ (completed). تعرض:
- Header: "History detail" + Back
- صورة الملعب (كبيرة أعلى)
- اسم النادي (Symbols ground) + أيقونة موقع + المدينة (Bangalore)
- قسم Facilities: أيقونات (Parking، Cam، Waiting، Changing room)
- Grid معلومات: Ground (Ground 01)، Booking Code (GR0175)، Date (November 7, 2017)، Time (11:00 PM)
- زر "Write a review" أخضر ثابت في الأسفل

#### B. الفرق الجوهري عن Booking Details (Screen 07)
| | Booking Details (Upcoming) | History Detail (Completed) |
|---|---|---|
| زر الأسفل | Cancel (أحمر) | Write a review (أخضر) |
| "Notify me" section | موجود | غير موجود |
| الهدف | إدارة الحجز | مراجعة التجربة |

#### C. Primary Actor
Mobile User (صاحب الحجز المنتهي)

#### D. User Actions
| Action | Description |
|---|---|
| Tap "Write a review" | فتح Screen 08 مرتبطة بهذا الـ booking_id |
| Tap Back | العودة لـ History list |

#### E. Business Rules
- زر "Write a review" يظهر فقط إذا `booking.status = completed`
- إذا المستخدم سبق وكتب review لهذا الحجز → يُغيَّر الزر لـ "View my review" أو يُخفى
- نفس بيانات Booking Details لكن بدون Cancel + بدون Notify me

#### F. API Impact
```
GET /api/v1/bookings/{id}
Response يجب يشمل: has_review (boolean)
→ إذا has_review = false → يعرض "Write a review"
→ إذا has_review = true → يعرض "View my review" أو يخفي الزر
```

#### G. Database Impact
- `has_review` computed field: `reviews.where(booking_id = X).exists()`
- أو إضافة `reviewed_at` nullable column على Booking

#### H. Recommendations
- أضف `has_review` في كل booking response — الموبايل يحتاجه في list وdetail معاً
- بعد إرسال review → redirect لـ History Detail مع إظهار "شكراً على تقييمك" toast

---

### Screen 10 — Profile Screen
> Source: 29_0146_4s.jpg (full menu), 31_0163_2s.jpg (scrolled — shows My grounds + Log out)

#### A. Visual Summary
شاشة Profile بثيم داكن (Dark mode). تعرض:
- Header: "Profile"
- Avatar دائري رمادي + اسم المستخدم (Ronald Richards) + البريد الإلكتروني (ronaldrichards@gmail.com)
- قائمة خيارات بـ chevron (>):
  - My profile
  - Settings
  - Privacy policy
  - Help
  - About us
  - Rate us
  - My grounds (ظاهر في scroll — Image 2)
  - Log out (ظاهر في scroll — Image 2)

#### B. Functional Purpose
مركز إدارة الحساب وإعدادات التطبيق. بوابة للوصول لكل الخيارات الشخصية.

#### C. Primary Actor
Mobile User (مسجّل)

#### D. User Actions
| Action | Destination |
|---|---|
| Tap My profile | شاشة تعديل الملف الشخصي |
| Tap Settings | شاشة الإعدادات |
| Tap Privacy policy | شاشة Privacy Policy (Screen 11) |
| Tap Help | شاشة المساعدة (TBD) |
| Tap About us | شاشة About Us (TBD — ContentPage بـ slug `about`) |
| Tap Rate us | يفتح App Store / Play Store لتقييم التطبيق (deep link) |
| Tap My grounds | قائمة ملاعب المستخدم المفضلة؟ أو حجوزاته؟ — **OQ-025** |
| Tap Log out | تسجيل خروج + حذف الـ token |

#### E. Data Outputs
- `user.name`
- `user.email` (nullable — قد يكون فارغاً لمستخدمي OTP)
- `user.avatar_url` (nullable — يعرض default avatar إذا فارغ)

#### F. Business Rules
- "Rate us" → يفتح Store URL الخاص بمنصة الجهاز (iOS → App Store، Android → Play Store) — نفس `store_url` من AppPlatform
- Log out يحذف الـ token المحلي + يرسل `POST /api/v1/auth/logout` للـ backend لإبطاله
- البريد الإلكتروني يظهر تحت الاسم — إذا مستخدم OTP بدون email يظهر رقم الهاتف بدلاً

#### G. New Fields/Screens Discovered
- "My grounds" — وظيفة غير واضحة → **OQ-025**
- "Settings" — شاشة إعدادات منفصلة → تحتاج شاشة لتحليلها
- Profile عرض email → يعني `email` أو `phone_number` يُعرض كـ identifier

#### H. API Impact
```
GET /api/v1/me
Response: { name, email, phone_number, avatar_url, ... }

POST /api/v1/auth/logout
Auth: Required
Action: invalidate current token
```

#### I. Admin Panel Impact
- Admin يرى profile كل مستخدم + آخر نشاط + طريقة المصادقة
- لا يوجد action جديد

#### J. Assumptions
- **PA-Screen10-001:** البريد الإلكتروني يظهر في Profile لأن هذا المستخدم سجّل عبر Google — مستخدم OTP يرى رقم هاتفه بدلاً.
- **PA-Screen10-002:** Avatar الافتراضي = دائرة رمادية مع أيقونة شخص — كما يظهر في الشاشة.
- **PA-Screen10-003:** "Rate us" يستخدم deep link للـ Store — يأخذ الـ URL من `AppPlatform.store_url` المحفوظ.

#### K. Open Questions Added
- **OQ-025:** ما هو "My grounds" في Profile؟ هل هي الملاعب المفضلة (Favorites)؟ أم شيء آخر؟

---

### Screen 11 — Privacy Policy
> Source: 28_0143_9s.jpg (skeleton/loading state), 30_0148_4s.jpg (loaded state)

#### A. Visual Summary
شاشة Privacy Policy بحالتين:
- **Loading:** النصوص تظهر كـ placeholder رمادي (skeleton)
- **Loaded:** محتوى حقيقي مع عناوين (Types of data we collect، Use Of Your Personal Data، Disclosure Of Your Data) ونصوص فقرات

#### B. Functional Purpose
عرض سياسة الخصوصية للتطبيق. محتوى ثابت قابل للتعديل من Admin Dashboard.

#### C. Primary Actor
Mobile User (مسجّل + غير مسجّل)

#### D. Data Outputs
- `content_page.title` (Privacy policy)
- `content_page.body` — HTML أو Markdown rich text مع headings وfقرات
- يدعم Arabic + English بناءً على `Accept-Language` header

#### E. States
| State | Description |
|---|---|
| `loading` | Skeleton placeholders بينما يُجلب المحتوى |
| `loaded` | محتوى كامل |
| `error` | خطأ في التحميل — retry button |

#### F. Business Rules
- المحتوى يُجلب من `GET /api/v1/content/privacy`
- Cacheable — نادراً ما يتغير — TTL: 1 ساعة
- إذا `body` فارغ → "سيتم إضافة المحتوى قريباً"

#### G. API Impact
```
GET /api/v1/content/{slug}
slug: privacy | about | terms | help
Auth: Public
Response: { slug, title_ar, title_en, body_ar, body_en, updated_at }
```
- نفس الـ endpoint لكل الصفحات الثابتة (about، help، terms)
- Mobile يختار `title_ar/en` و `body_ar/en` حسب لغة الجهاز

#### H. Admin Panel Impact
- Rich text editor لكل ContentPage
- Bilingual: حقل عربي + حقل إنجليزي منفصلين
- `updated_at` يظهر في Admin لمعرفة آخر تعديل

#### I. Mobile App Impact
- Skeleton loading مؤكد من الصور — الموبايل يعرضه أثناء الـ fetch
- Body يُعرض كـ HTML rendered أو WebView بسيط
- Cacheable locally لتجربة offline

#### J. Assumptions
- **PA-Screen11-001:** Privacy Policy + About Us + Help + Terms كلهم نفس الـ component بـ slug مختلف
- **PA-Screen11-002:** المحتوى يُخزَّن كـ HTML في DB — Admin يعدّله عبر rich text editor (مثل TipTap أو Quill)

#### K. Recommendations
- استخدم `slug` كـ cache key على الموبايل
- أضف `updated_at` في الـ response حتى يعرف الموبايل إذا المحتوى تغيّر

---

### Screen 12 — My Grounds
> Source: IMG_2222.png

#### A. Visual Summary
شاشة "My Grounds" — قائمة عمودية من ملاعب المستخدم. كل كارد: صورة مربعة rounded، اسم الملعب، مدة الحجز الأخيرة (3 Hour، 1 Hour...). زر "Add" أخضر ثابت في الأسفل.

#### B. Functional Purpose
عرض الملاعب المرتبطة بالمستخدم — مزيج من Recent (من الحجوزات) وSaved (محفوظة يدوياً). مع فلتر تلقائي يُخفي الملاعب المتردية التقييم من Recent.

#### C. Primary Actor
Mobile User (مسجّل)

#### D. User Actions
| Action | Description |
|---|---|
| Tap venue card | فتح تفاصيل الملعب (Venue Detail) |
| Tap "Add" | إضافة ملعب للمحفوظات — يفتح شاشة بحث أو browse |
| Swipe to delete (مستنتج) | إزالة ملعب من Saved |

#### E. Data Outputs
- `venue.name`, `venue.primary_image`
- `last_booking_duration` — المدة من آخر حجز (للـ Recent)
- `type` flag — saved أو recent (يُعرض كـ badge مختلف)

#### F. Business Rules
- Recent: venues من حجوزات المستخدم، مع فلتر **تقييم المستخدم الشخصي** (مو الـ avg العام):
  - إذا المستخدم قيّم الملعب < 3.0 → مخفي من Recent
  - إذا المستخدم لم يقيّم بعد → يظهر (لا فلتر على غير المقيَّم)
- Saved: كل الملاعب المحفوظة بغض النظر عن أي تقييم
- إذا venue في الاثنين → يظهر مرة واحدة كـ Saved
- "Add" button → يسمح للمستخدم بالبحث وحفظ ملعب جديد

#### G. New Insight — "Add" Button
زر Add يعني المستخدم يقدر يضيف ملعب لـ Saved يدوياً من هذه الشاشة. يفتح على الأرجح شاشة بحث أو browse للـ clubs/venues.

#### H. Admin Panel Impact
- SystemSettings: مفتاح `my_grounds_min_rating` (default: 3.0) — Admin يغيره من Dashboard
- لا إدارة مباشرة لـ My Grounds من Admin

#### I. API Impact
```
GET /api/v1/me/grounds
POST /api/v1/venues/{id}/save
DELETE /api/v1/venues/{id}/save
```

#### J. Assumptions
- **PA-Screen12-001:** المدة الظاهرة (3 Hour، 1 Hour...) هي مدة آخر حجز لهذا الملعب.
- **PA-Screen12-002:** لا يوجد pagination — قائمة محدودة طبيعياً.
- **PA-Screen12-003:** "Add" يفتح شاشة بحث Venues — لم تُحلَّل بعد.

#### K. Risks
- إذا المستخدم ليس عنده حجوزات ولا saved → شاشة فارغة → يجب empty state + دعوة للحجز
- threshold التقييم (3.0) إذا تغيّر من Admin → قد تختفي ملاعب من Recent فجأة

---

### Screen 12 — My Grounds
> Source: IMG_2222.png

#### A. Visual Summary
شاشة "My grounds" — قائمة الملاعب التي سبق للمستخدم حجزها. Dark theme. كل كارد يعرض: صورة الملعب (مربعة rounded)، اسم الملعب، آخر مدة حجز (X Hour). زر "Add" في الأسفل — **يُتجاهل، ليس feature مطلوبة**.

#### B. Functional Purpose
عرض الملاعب التي زارها المستخدم سابقاً كـ shortcuts للوصول السريع. مشتقة من سجل الحجوزات — لا entity منفصلة.

#### C. Primary Actor
Mobile User (مسجّل)

#### D. User Actions
| Action | Description |
|---|---|
| Tap venue card | فتح Venue Detail (ضمن Club Detail) |
| Tap Back | العودة لـ Profile |
| زر Add | **يُتجاهل — ليس feature مطلوبة** |

#### E. Data Outputs
```
[
  {
    "venue_id": "uuid",
    "venue_name": "Main ground",
    "venue_image_url": "...",
    "last_booked_duration_minutes": 180,  // يُعرض كـ "3 Hour"
    "last_booked_at": "2025-01-15T..."
  }
]
```
- مرتبة: الأحدث حجزاً أولاً
- Distinct venues — إذا نفس الملعب محجوز أكثر من مرة يظهر مرة واحدة فقط

#### F. Business Rules
- مشتق من `bookings` — لا جدول منفصل
- يشمل bookings بكل الحالات (confirmed, completed, cancelled) أم completed فقط؟ → **OQ-026**
- لا حد أقصى لعدد الملاعب المعروضة أم في pagination؟ → نفترض أول 20 + pagination

#### G. API Impact
```
GET /api/v1/me/grounds
Auth: Required
Response: paginated list of distinct venues from user's booking history
          ordered by most recent booking date DESC

Query logic (SQL concept):
SELECT DISTINCT venue_id, MAX(created_at) as last_booked_at,
       (SELECT duration_minutes FROM bookings b2
        WHERE b2.venue_id = b.venue_id AND b2.user_id = {user_id}
        ORDER BY created_at DESC LIMIT 1) as last_duration
FROM bookings b
WHERE user_id = {user_id}
GROUP BY venue_id
ORDER BY last_booked_at DESC
```

#### H. Database Impact
- لا جداول جديدة
- Query على `bookings` مع JOIN على `venues` و`venue_images`
- Index مهم: `bookings(user_id, venue_id, created_at)`

#### I. Mobile App Impact
- Duration يُعرض كـ "X Hour" أو "X Hours" — تحويل من دقائق للساعات على الموبايل
- Tap على الكارد → يحتاج `club_id` لفتح Club Detail ثم Venue — الـ API يجب يرجع `venue.club_id`

#### J. Assumptions
- **PA-Screen12-001:** "Add" button يُتجاهل كلياً — ليس feature.
- **PA-Screen12-002:** الـ duration الظاهر = مدة آخر حجز لهذا الملعب تحديداً.
- **PA-Screen12-003:** أقصى 20 ملعب في الـ preview مع pagination.

#### K. Open Questions Added
- **OQ-026:** My Grounds — يشمل bookings بكل الحالات أم completed + confirmed فقط؟




---


---

### Screen 04b — Club/Venue Detail (Upper Section)
> Source: IMG_2192.png
> Completes Screen 04 (lower section analyzed previously)

#### A. Visual Summary
الجزء العلوي من شاشة تفاصيل النادي/الملعب. يعرض:
- صورة غلاف كاملة العرض (hero image) مع:
  - زر Back يسار
  - زر Share يمين
  - Badge "8 KM" فوق الصورة يسار
- قسم المعلومات الرئيسية:
  - تقييم: ⭐ 4.5 (140 Reviews) — يسار
  - سعر: $100.00 — يمين (بالأخضر)
  - اسم النادي/الملعب (bold، كبير)
  - وصف مختصر مع "Read more"
- قسم Facilities: أيقونات أفقية (Parking، Cam، Waiting، Changing)
- قسم "Ground list" — يشير لقائمة الملاعب داخل النادي
- زر "Book now" أخضر ثابت في الأسفل

#### B. أهم الاكتشافات من هذه الشاشة

**1. الشاشة هي شاشة Club Detail — وليس Venue Detail**
- "Ground list" في الأسفل يؤكد أن هذه شاشة **النادي** التي تعرض قائمة ملاعبه
- المستخدم يرى النادي أولاً (rating، price، description، facilities) ثم يختار الملعب من "Ground list"
- Rating و Price هما على مستوى **النادي** في هذه الشاشة — وليس ملعب محدد

**2. السعر على مستوى النادي = `price_from` (أدنى سعر بين ملاعبه)**
- "$100.00" يمثّل أقل سعر متاح في أي ملعب بالنادي
- هذا يتوافق مع ما وثّقناه: `Club.price_from` computed field

**3. Rating = متوسط تقييمات الملاعب**
- "4.5 (140 Reviews)" — على مستوى النادي
- يُحسب من AVG of all published reviews across all venues in this club

**4. Facilities هنا = Facilities النادي العامة (مو ملعب محدد)**
- Parking، Cam (CCTV)، Waiting Room، Changing Room
- هذه مرافق النادي العامة → `Club.amenities` JSON

**5. "Ground list" = قائمة الملاعب داخل النادي**
- بعد scroll يظهر قسم Ground list مع كل ملعب وخصائصه
- المستخدم يختار ملعباً → ينتقل لـ Venue Detail (Screen 04 lower — duration، reviews، features)

#### C. Flow المُحدَّث بناءً على هذه الشاشة
```
Home / Search Results
    ↓ tap club card
Club Detail (Screen 04b — هذه الشاشة)
  → Rating + price_from + description + club facilities
  → Ground list (قائمة الملاعب)
    ↓ tap venue
Venue Detail (Screen 04 lower)
  → Duration selector + venue reviews + venue features
  → Book now
    ↓
Slot Selection (Screen B01)
```

#### D. Data Outputs
```json
{
  "id", "name", "description",
  "cover_image_url",
  "distance_km",
  "avg_rating": 4.5,
  "reviews_count": 140,
  "price_from": 100,
  "currency": "SYP",
  "amenities": ["Parking", "CCTV", "Waiting Room", "Changing Room"],
  "venues_preview": [
    // أول 3 ملاعب فقط للـ preview في Club Detail
    { "id", "name", "sports": [], "price_from", "primary_image_url" }
  ],
  "venues_count": 5
}
```
- **Full venues list** → `GET /api/v1/clubs/{club_id}/venues` (Screen 04c)

#### E. Business Rules
- `price_from` = MIN(VenuePricingTier.price) across all active venues in this club
- `avg_rating` = AVG(reviews.rating) across all published reviews of all venues in this club
- `reviews_count` = COUNT of published reviews across all venues
- Facilities على مستوى النادي — مو مستوى الملعب الفردي
- "Read more" يعني الـ description قد يكون طويلاً — truncated في القائمة، كامل عند tap

#### F. API Impact — تحديث Club Detail endpoint
```
GET /api/v1/clubs/{id}
Response: {
  "id", "name", "description",
  "cover_image_url", "logo_url",
  "distance_km",
  "avg_rating", "reviews_count",
  "price_from", "currency",
  "amenities": [],
  "address", "phone_number",
  "area": { "id", "name" }, "city": { "id", "name" },
  "venues": [
    {
      "id", "name",
      "sports": [{ "id", "name", "icon_url" }],
      "price_from",
      "allowed_durations": [45, 90],
      "primary_image_url",
      "amenities": [],
      "avg_rating",
      "reviews_count"
    }
  ]
}
```

#### G. Database Impact
- `clubs.amenities` JSON — مرافق النادي العامة (Parking، CCTV، Waiting Room، Changing Room...)
- `clubs.avg_rating` — computed/cached field على Club entity
- `clubs.reviews_count` — computed/cached field على Club entity
- `clubs.price_from` — computed/cached = MIN venue price في هذا النادي

#### H. Admin Panel Impact
- Club form يحتوي `amenities` كـ tag-input (JSON)
- `avg_rating` و `reviews_count` و `price_from` — computed لا يُدخلها الأدمن يدوياً
- تُحسب عند: إضافة review جديد، تعديل VenuePricingTier، إضافة/حذف venue

#### I. Assumptions
- **PA-04b-001:** "Ground list" هو قائمة الملاعب inside the club — المستخدم يختار منها للحجز.
- **PA-04b-002:** Rating وPrice في upper section = على مستوى Club — ليس venue محدد.
- **PA-04b-003:** Share button → يشارك رابط النادي (deep link).
- **PA-04b-004:** الـ description يدعم "Read more" → يعني يُخزَّن كامل ويُعاد truncated + full.

#### J. Risks
- `price_from` و `avg_rating` computed fields تحتاج إعادة حساب عند كل تغيير → إذا حُسبت real-time ستكون بطيئة. Mitigation: cached columns تُحدَّث عبر Observer أو scheduled job.
- إذا النادي ليس عنده أي ملعب بعد → "Ground list" فارغ → يجب empty state.

#### K. Recommendations
- استخدم cached computed columns (`avg_rating`، `reviews_count`، `price_from`) بدل real-time calculation على كل request
- Observers: `ReviewObserver` → يُحدّث `avg_rating` و `reviews_count` على Club + Venue عند كل review
- `VenuePricingTierObserver` → يُحدّث `price_from` على Club عند تغيير التسعير


---

### Screen 04c — Grounds List (ملاعب النادي)
> Source: No image — built from confirmed decision (developer confirmed separate screen)
> Status: Assumed

#### A. Visual Summary (Assumed)
شاشة مستقلة لعرض كل ملاعب نادٍ معين. تعرض:
- Header: "Grounds" + اسم النادي
- قائمة عمودية من ملاعب النادي
- كل كارد: صورة الملعب، اسم الملعب، الرياضات المدعومة (icons)، السعر من/ساعة، المدد المتاحة (45 min / 90 min)

#### B. Functional Purpose
تمكين المستخدم من استعراض كل الملاعب داخل نادٍ معين واختيار الملعب المناسب للحجز.

#### C. Flow
```
Club Detail (Screen 04b)
    ↓ tap "View all" أو tap ground card في Ground list preview
Grounds List (هذه الشاشة)
    ↓ tap ملعب
Venue Detail (Screen 04d)
```

#### D. Data Outputs
```json
{
  "club_id", "club_name",
  "venues": [
    {
      "id", "name",
      "primary_image_url",
      "sports": [{ "id", "name", "icon_url" }],
      "price_from",
      "allowed_durations": [45, 90],
      "avg_rating",
      "amenities": [],
      "status"
    }
  ]
}
```

#### E. Business Rules
- تعرض فقط الملاعب بـ `status = active`
- مرتبة حسب `sort_order` أو السعر (TBD)
- إذا نادٍ عنده ملعب واحد فقط → يُنتقل مباشرة لـ Venue Detail بدون هذه الشاشة

#### F. API Impact
```
GET /api/v1/clubs/{club_id}/venues
Auth: Public
Query: sport_category_id (optional filter)
Response: {
  "club": { "id", "name", "logo_url" },
  "venues": [ { id, name, primary_image_url, sports[], price_from, allowed_durations[], avg_rating, amenities[] } ]
}
```
- **لا pagination** — عدد الملاعب في نادٍ واحد محدود طبيعياً

#### G. Assumptions
- **PA-04c-001:** إذا النادي عنده ملعب واحد فقط → Mobile يتخطى هذه الشاشة وينتقل مباشرة لـ Venue Detail.
- **PA-04c-002:** يمكن فلترة الملاعب بالرياضة من نفس الشاشة (إذا النادي multi-sport).

---

### Screen 04d — Venue Detail (Play Ground Detail)
> Source: IMG_2192.png (upper) + Screen 04 previously analyzed (lower)
> هذه الشاشة تجمع Upper + Lower في تحليل واحد متكامل

#### A. Visual Summary
شاشة تفاصيل ملعب محدد داخل النادي. تعرض (من أعلى لأسفل):
- صورة الملعب hero image + زر Back + زر Share + badge KM
- Rating + Reviews count + السعر من
- اسم الملعب + وصف + "Read more"
- Facilities (JSON amenities للملعب)
- Duration selector: خيارات المدة من `VenueAllowedDurations`
- Reviews preview (2-3 reviews) + "View all"
- Popular features / amenities
- زر "Book now" ثابت في الأسفل

#### B. Functional Purpose
عرض كل تفاصيل الملعب وتمكين المستخدم من اختيار المدة والانتقال للحجز.

#### C. Flow
```
Grounds List (Screen 04c)
    ↓ tap
Venue Detail (هذه الشاشة)
    ↓ اختار duration + tap Book now
Slot Selection (Screen B01)
```

#### D. Data Outputs — Venue Detail API Response
```json
{
  "id", "name_ar", "name_en",
  "description_ar", "description_en",
  "primary_image_url",
  "images": [],
  "club": { "id", "name", "area": { "name" }, "distance_km" },
  "sports": [{ "id", "name", "icon_url" }],
  "allowed_durations": [45, 90],
  "price_from",
  "amenities": ["Parking", "CCTV", "Shaded area"],
  "avg_rating", "reviews_count",
  "reviews_preview": [
    { "user_name", "user_avatar", "is_anonymous", "rating", "body", "created_at" }
  ],
  "is_saved": true,
  "availability_summary": { "today_has_slots": true }
}
```

#### E. Key Fields
- `is_saved` — هل المستخدم حفظ هذا الملعب في My Grounds (SavedVenue)
- `availability_summary.today_has_slots` — badge سريع للموبايل بدون جلب كل الـ slots
- `allowed_durations` — يُغذّي الـ Duration selector في نفس الشاشة

#### F. API Impact
```
GET /api/v1/clubs/{club_id}/venues/{venue_id}
Auth: Optional (is_saved يتطلب auth)
Response: كما أعلاه
```

#### G. Business Rules
- Duration selector يُبنى من `VenueAllowedDurations` — مو hardcoded
- "Book now" ينقل لـ Slot Selection مع تمرير `venue_id` + `duration_minutes` المختارة
- `is_saved` يتطلب authenticated user — للـ guest يكون `false`
- Reviews preview: أحدث 3 reviews فقط — "View all" ينقل لشاشة reviews كاملة

#### H. Risks
- إذا الملعب ليس عنده pricing tiers → لا slots → "Book now" يُعطَّل مع رسالة
- إذا الملعب ليس عنده allowed_durations → duration selector فارغ → نفس المشكلة


---

### Screen 13 — Settings
> Source: IMG_2215.png
> Note: المصمم قال "كتير ناقصة" — نأخذ الأفكار الأساسية ونبني عليها منطقياً

#### A. Visual Summary
شاشة الإعدادات. تعرض toggles للتحكم بسلوك التطبيق:
- Push notification — تفعيل/تعطيل إشعارات FCM
- Message — تفعيل/تعطيل إشعارات SMS/in-app messages
- Face ID — تفعيل/تعطيل Biometric login
- Dark mode — تبديل الثيم

#### B. الإعدادات الأساسية المؤكدة (من الشاشة)
| Setting | النوع | Backend؟ |
|---|---|---|
| Push notification | boolean toggle | ✅ يُخزَّن على backend — يتحكم بإرسال FCM |
| Message | boolean toggle | ✅ يُخزَّن على backend — SMS/in-app |
| Face ID / Biometric | boolean toggle | ❌ محلي فقط — شغل Flutter |
| Dark mode | boolean toggle | ❌ محلي فقط — شغل Flutter |

#### C. الإعدادات المنطقية الإضافية (غير موجودة في الشاشة لكن متوقعة)
| Setting | النوع | Backend؟ |
|---|---|---|
| Language (AR/EN) | selector | ✅ يُخزَّن — يؤثر على API responses |
| Booking reminders | boolean toggle | ✅ يُخزَّن — يتحكم بإرسال reminder notifications |
| Change phone number | action | ✅ flow منفصل |
| Delete account | action (destructive) | ✅ API call |

#### D. Business Rules
- **Push notification = false** → backend لا يرسل FCM لهذا المستخدم
- **Message = false** → backend لا يرسل SMS notifications (غير OTP — OTP دايماً يُرسل)
- **Face ID / Dark mode** → محلي تماماً على الموبايل — Backend لا يعرف عنهم
- **Language** → يُرسل مع كل API request كـ `Accept-Language` header — يُخزَّن على `users.preferred_language` للـ FCM notifications

#### E. User fields المضافة على User entity
- `notifications_push_enabled` (boolean, default: true)
- `notifications_sms_enabled` (boolean, default: true)
- `notifications_reminders_enabled` (boolean, default: true)
- `preferred_language` (enum: `ar` / `en`, default: `ar`)

#### F. API Impact
```
GET /api/v1/me/settings
Auth: Required
Response: {
  "notifications_push_enabled": true,
  "notifications_sms_enabled": true,
  "notifications_reminders_enabled": true,
  "preferred_language": "ar"
}

PUT /api/v1/me/settings
Auth: Required
Body: {
  "notifications_push_enabled": false,
  "notifications_sms_enabled": true,
  "preferred_language": "ar"
}
```

#### G. Impact على FCM Notifications
عند إرسال أي FCM notification:
```php
// قبل الإرسال → تحقق:
if (!$user->notifications_push_enabled) return; // لا ترسل
// للـ reminders فقط:
if (!$user->notifications_reminders_enabled) return;
```

#### H. Assumptions
- **PA-13-001:** Face ID وDark mode محليان تماماً — Backend لا يخزنهما.
- **PA-13-002:** OTP للمصادقة يُرسل دايماً بغض النظر عن `notifications_sms_enabled` — هذا security critical.
- **PA-13-003:** Language setting تُرسل مع كل request كـ `Accept-Language` header — تُخزَّن على backend للـ FCM فقط.

---

### Screen 13b — Profile Toast (Rate App Confirmation)
> Source: IMG_2221.png (toast "Thanks for appraisal rating..! 😊")

#### A. الاكتشاف
بعد تقييم التطبيق على المتجر ("Rate us") → يظهر toast/snackbar في أعلى الشاشة:
**"Thanks for appraisal rating..! 😊"**

#### B. البيزنس لوجيك
- "Rate us" → يفتح App Store / Play Store (deep link من `AppPlatform.store_url`)
- بعد العودة للتطبيق → يظهر Toast تلقائياً
- **لا API call مطلوب** — هذا client-side فقط
- الموبايل يعرف المستخدم "رجع من المتجر" عبر `AppState` / `lifecycleState`

#### C. Impact
- لا تأثير على Backend
- لا entity جديدة
- مجرد UX feedback محلي على الموبايل

### Screen 05 — My Booking (Upcoming)
> Source: 20_0102_4s.jpg

#### A. Visual Summary
شاشة "My booking" — Tab: Upcoming (نشط) / Completed. قائمة عمودية لحجوزات المستخدم القادمة. كل كارد: صورة صغيرة، اسم النادي، موقع، تاريخ + يوم الأسبوع.

#### B. Functional Purpose
عرض الحجوزات النشطة والقادمة للمستخدم. التنقل لتفاصيل حجز محدد.

#### C. Primary Actor
Mobile User (مسجّل)

#### D. User Actions
- Tap على tab "Completed" → التبديل لشاشة التاريخ
- Tap على كارد → Booking Details Screen
- Scroll عمودي

#### E. Data Outputs
```
booking.id, venue.name, venue.primary_image,
club.area.name, booking_date (formatted), booking_day_of_week
```

#### F. States
- `upcoming` tab: حجوزات بـ status = `confirmed` + تاريخ مستقبلي
- `completed` tab: حجوزات بـ status = `completed` + تاريخ ماضي
- `empty`: لا حجوزات → رسالة تشجيعية + زر "Browse clubs"

#### G. Business Rules
- Upcoming = confirmed bookings with future date
- Tab "Completed" = completed + cancelled bookings (التاريخ حدد)
- الترتيب: الأقرب تاريخاً أولاً في Upcoming، الأحدث أولاً في Completed

#### H. API Impact
```
GET /api/v1/bookings?status=upcoming&page=1
GET /api/v1/bookings?status=completed&page=1
Response: paginated list with venue name, image, area, date
```

#### I. Mobile App Impact
- Bottom nav tab "Booking" → هذه الشاشة مباشرة
- Date format: "March 13, 2014" + "Friday" → يجب أن يكون format واضح في API أو يُحسب محلياً

---

### Screen 06 — History (Completed Bookings)
> Source: 21_0104_9s.jpg

#### A. Visual Summary
شاشة "History" — Tab: Completed (نشط). قائمة حجوزات منتهية. كل كارد يشبه My Booking لكن يضيف: **السعر المدفوع** (`$10.00`, `$15.00`...).

#### B. Functional Purpose
سجل الحجوزات المنتهية مع الأسعار المدفوعة. يخدم كـ receipt history للمستخدم.

#### C. Key Difference from My Booking Screen
- **السعر ظاهر على كل كارد** — هذا مهم: History = financial record
- يبدو أنها نفس شاشة My Booking لكن على tab "Completed" مع ظهور السعر

#### D. Data Outputs
```
booking.id, venue.name, club.area.name,
booking_date, booking_day_of_week,
booking.total_price, booking.currency
```

#### E. API Impact
```
GET /api/v1/bookings?status=completed&page=1
Response: + total_price, currency في كل booking
```

#### F. Assumptions
- History = completed + cancelled bookings معاً، أو completed فقط؟ (OQ-021 جديد)
- السعر يظهر فقط في Completed/History — ليس في Upcoming

---

### Screen 07 — Booking Details
> Source: 19_0098_9s.jpg

#### A. Visual Summary
شاشة "Booking details" — تفاصيل حجز واحد كاملة. تعرض:
- صورة الملعب (كبيرة أعلى)
- اسم النادي + أيقونة موقع + المدينة
- قسم Facilities: أيقونات صغيرة لـ Parking، Camera، Waiting room، Changing room
- Grid معلومات: Ground (Ground 01)، Booking Code (GR0175)، Date (15 June Monday)، Time (11:00 PM)
- قسم "Notify me" مع صور أشخاص (Theresa Webb, Esther Howard) — **مميزة غير واضحة**
- زر "Cancel" أحمر/كبير

#### B. Functional Purpose
عرض كل تفاصيل الحجز في مكان واحد. تمكين المستخدم من الإلغاء.

#### C. Primary Actor
Mobile User (صاحب الحجز فقط)

#### D. User Actions
| Action | Description |
|---|---|
| Tap Cancel | إلغاء الحجز (يظهر فقط إذا ضمن cancellation window) |
| View Facilities | عرض مرافق الملعب بصرياً |
| Tap Notify me (؟) | وظيفة غير واضحة — OQ-022 |

#### E. Data Outputs
```
booking.booking_code (GR0175 — unique human-readable code)
booking.date, booking.time (start_time)
venue.name (Ground 01)
club.name, club.city
venue.facilities/amenities[] (Parking, Camera, Waiting, Changing room)
```

#### F. New Fields Discovered
- **`booking.booking_code`** — رمز حجز قابل للقراءة (مثل GR0175). يجب إضافته لـ Booking entity.
- **`venue.facilities`** — يبدو أنها أيقونات محددة (Parking, Camera/CCTV, Waiting Room, Changing Room) — ليست نص حر.

#### G. Business Rules
- زر Cancel يظهر فقط إذا الحجز ضمن cancellation window المسموح
- بعد الإلغاء → redirect لـ My Bookings مع رسالة تأكيد
- Booking Code يجب أن يكون unique وقابل للاستخدام للبحث من الأدمن

#### H. Database Impact
- إضافة `booking_code` (unique string, indexed) على Booking entity
- `venue.facilities` يحتاج تعريف واضح — قائمة محددة أم JSON حر؟ (OQ-023)

#### I. API Impact
```
GET /api/v1/bookings/{id}
Response: full booking detail including booking_code, facilities, date, time

POST /api/v1/bookings/{id}/cancel
Auth: Required (owner only)
Body: { reason: optional }
```

#### J. Assumptions
- **PA-Screen07-001:** "Notify me" section حُذفت من التصميم — الإشعارات تُرسل تلقائياً عبر Firebase FCM بدون تدخل المستخدم. See LD-031.
- **PA-Screen07-002:** Booking Code يُولَّد تلقائياً عند إنشاء الحجز (مثل: prefix + random).
- **PA-Screen07-003:** Facilities هي مرافق ثابتة يضيفها الأدمن لكل ملعب.

#### K. Risks
- "Notify me" غير واضحة — إذا كانت invite feature تحتاج module كامل
- Cancellation button يجب أن يختفي أو يُعطَّل بعد الـ cancellation window

#### L. Open Questions Added
- **OQ-022:** ما وظيفة "Notify me" مع صور الأشخاص في Booking Details؟
- **OQ-023:** هل Facilities قائمة ثابتة (Parking, CCTV, Waiting Room, Changing Room) يحددها الأدمن، أم نص حر؟


---

## 6. Domain Entities

### Entity: User
- **Purpose:** Represents an end-user account in the system.
- **Important Fields:**
  - `id`
  - `phone_number` (unique, nullable if Google-only)
  - `phone_verified_at`
  - `google_id` (nullable)
  - `name`
  - `email` (nullable, not used for auth)
  - `avatar_url` (nullable)
  - `account_status`
  - `default_city_id` (FK → City — user's selected city)
  - `default_area_id` (FK → Area — user's selected area/neighborhood for content scoping, nullable)
  - `fcm_token` (nullable — Firebase Cloud Messaging device token, updated on each login)
  - `notifications_push_enabled` (boolean, default: true)
  - `notifications_sms_enabled` (boolean, default: true)
  - `notifications_reminders_enabled` (boolean, default: true)
  - `preferred_language` (enum: `ar`/`en`, default: `ar` — used for FCM notification language)
  - `fcm_platform` (enum: `mobile`/`web`, nullable — set when FCM token is updated)
  - `google2fa_secret` (VARCHAR 255, nullable, encrypted — 32-char TOTP secret, super admin only)
  - `google2fa_enabled_at` (nullable timestamp — when 2FA was activated)
  -- club_id REMOVED — club assignment handled via club_user pivot table (dynamic, multi-club)
  - `onboarding_completed_at`
  - `last_login_at`
  - `created_at`
- **Statuses:** `active` / `blocked` / `suspended` / `pending_profile_completion`
- **Relations:**
  - User has many Bookings
  - User has many SocialIdentities
  - User has many OTPChallenges
  - User has many Payments
  - User has one Wallet
  - User belongs to City (default_city_id)
  - User belongs to Area (default_area_id, nullable)
- **Notes:**
  - Phone number is the primary identifier for OTP users. Google ID is the identifier for Google users. Both can coexist on the same account.
  - `default_area_id` scopes all Club/Venue listing results for this user within their city.
  - User can change their city/area at any time from their profile.
  - If area not set, defaults to showing all clubs within the selected city.

---

### Entity: SocialIdentity
- **Purpose:** Links a user account to an external identity provider (Google).
- **Important Fields:**
  - `id`
  - `user_id`
  - `provider` (enum: `google`)
  - `provider_user_id`
  - `provider_email`
  - `provider_avatar`
  - `last_used_at`
- **Statuses:** `linked` / `revoked`
- **Relations:** SocialIdentity belongs to User

---

### Entity: OTPChallenge
- **Purpose:** Tracks OTP issuance and verification for phone authentication.
- **Important Fields:**
  - `id`
  - `user_id` (nullable — may not exist yet for new users)
  - `phone_number`
  - `code_hash`
  - `expires_at`
  - `consumed_at`
  - `attempts_count`
  - `resend_count`
  - `delivery_status`
  - `provider_reference`
- **Statuses:** `pending` / `delivered` / `verified` / `expired` / `failed` / `locked`
- **Relations:** OTPChallenge may belong to User

---

### Entity: City
- **Purpose:** Top-level geographic grouping. A City contains multiple Areas. Admin manages the City list.
- **Important Fields:**
  - `id`
  - `name_ar`
  - `name_en`
  - `is_active`
  - `sort_order`
- **Statuses:** `active` / `inactive`
- **Relations:**
  - City has many Areas
- **Notes:** Examples: دمشق، حلب، حمص، اللاذقية. Admin creates all cities from Dashboard.

---

### Entity: Area
- **Purpose:** A district or neighborhood within a City. This is the primary geographic scoping unit for content discovery. Clubs belong to Areas.
- **Important Fields:**
  - `id`
  - `city_id` (FK → City)
  - `name_ar`
  - `name_en`
  - `is_active`
  - `sort_order`
- **Statuses:** `active` / `inactive`
- **Relations:**
  - Area belongs to City
  - Area has many Clubs
- **Notes:** Examples: المزة، المالكي، كفرسوسة، باب توما. Granularity is neighborhood-level (حي/منطقة). Admin manages all areas.

---

### Entity: Club
- **Purpose:** The top-level sports facility entity. Represents a named sports club that owns and operates one or more physical venues. This is the primary entity users discover on the Home Screen.
- **Important Fields:**
  - `id`
  - `owner_id` (FK → ClubOwner)
  - `name_ar`
  - `name_en`
  - `description_ar`
  - `description_en`
  - `area_id` (FK → Area — neighborhood/district level)
  - `address`
  - `latitude`
  - `longitude`
  - `phone_number`
  - `logo_url`
  - `cover_image_url`
  - `is_featured` — controls appearance in "Popular Grounds" on Home Screen
  - `avg_rating` (decimal, cached — AVG of all published reviews across all club venues)
  - `reviews_count` (integer, cached — COUNT of published reviews across all club venues)
  - `price_from` (integer, cached — MIN VenuePricingTier.price across all active venues)
  - `amenities` (JSON array — club-level facilities e.g. ["Parking", "CCTV", "Waiting Room", "Changing Room"])
  - `status`
  - `approved_at` (nullable — if admin approval flow is required)
  - `created_at`
- **Statuses:** `active` / `inactive` / `pending_approval` / `suspended`
- **Relations:**
  - Club belongs to ClubOwner
  - Club belongs to Area
  - Club has many Venues
  - Club has many ClubImages
- **Notes:**
  - Club is what the user sees and taps on the Home Screen.
  - Geographic scoping is via `area_id` (neighborhood level) → `city_id` via Area.
  - Nearby search uses Club's `latitude`/`longitude` within the region.
  - `is_featured` drives the "Popular Grounds" section.
  - `avg_rating`, `reviews_count`, `price_from` are cached computed fields — updated via Observers.
  - `amenities` = club-level facilities (different from venue-level amenities).

---

### Entity: ClubOwner
- **Purpose:** Represents the business owner of one or more Clubs. Has read-only access to their clubs' statistics. Separate from mobile User entity.
- **Important Fields:**
  - `id`
  - `name`
  - `email` (login identifier — email+password auth, NOT OTP)
  - `password_hash`
  - `phone_number`
  - `status`
  - `approved_at` (nullable)
  - `last_login_at`
  - `created_at`
- **Statuses:** `active` / `inactive` / `pending_approval` / `suspended`
- **Relations:**
  - ClubOwner has many Clubs
  - ClubOwner has many ClubStaffMembers (through their clubs)
- **Notes:**
  - Read-only access: sees revenue stats, booking counts, occupancy rates.
  - Cannot edit venues, pricing, availability, or manage staff.
  - Admin can approve, suspend, or deactivate.

---

### Entity: ClubStaffMember
- **Purpose:** Represents a staff member assigned to a specific Club with a specific role. This covers Club Admin and Data Entry roles (not the owner).
- **Important Fields:**
  - `id`
  - `club_id` (FK → Club)
  - `name`
  - `email` (login identifier)
  - `password_hash`
  - `phone_number`
  - `role` (enum: `club_admin` / `club_data_entry`)
  - `fcm_token` (nullable — updated on Club Dashboard login, used for booking notifications)
  - `status`
  - `invited_by` (FK → ClubOwner or SuperAdmin)
  - `last_login_at`
  - `created_at`
- **Statuses:** `active` / `inactive` / `suspended`
- **Relations:**
  - ClubStaffMember belongs to Club
- **Notes:**
  - `club_admin`: Full control over their assigned club — venues, pricing, availability, staff, booking management.
  - `club_data_entry`: Can input/update venue data (name, images, schedules, pricing) — no access to financials, user data, or staff management.
  - Staff members are scoped to ONE club. Multi-club staff requires multiple records.
  - Created/invited by Club Owner or Super Admin.

---

### Entity: ClubImage
- **Purpose:** Stores gallery images for a Club.
- **Important Fields:**
  - `id`
  - `club_id`
  - `url`
  - `is_primary`
  - `sort_order`
- **Relations:** ClubImage belongs to Club

---

### Entity: SportCategory
- **Purpose:** Defines a type of sport available on the platform. Fully admin-managed.
- **Important Fields:**
  - `id`
  - `name_ar`
  - `name_en`
  - `slug`
  - `icon_url`
  - `image_url`
  - `sort_order`
  - `is_active`
  - `created_at`
- **Statuses:** `active` / `inactive`
- **Relations:**
  - SportCategory has many Venues
- **Notes:** No hardcoded categories. Admin creates all categories from Dashboard.

---

### Entity: Venue
- **Purpose:** Represents a specific physical court or field inside a Club. Belongs to exactly one Club. Supports one or more sport categories. This is the bookable unit — pricing and slot durations are configured here.
- **Important Fields:**
  - `id`
  - `club_id` (FK → Club)
  - `latitude` (decimal, nullable — optional specific location override)
  - `longitude` (decimal, nullable — optional specific location override)
  - `name_ar` (e.g., "ملعب كرة قدم مع شمسية")
  - `name_en`
  - `description_ar`
  - `description_en`
  - `size` (e.g., "7×7"، "5×5"، "full") — physical size of the court
  - `amenities` (JSON — e.g., ["شمسية", "إضاءة", "مقاعد"])
  - `avg_rating` (decimal, computed/cached — average of all published reviews from all users; used for PUBLIC display on venue cards and listings — NOT for My Grounds filter)
  - `reviews_count` (integer, cached)
  - `status`
  - `created_at`
- **Statuses:** `active` / `inactive` / `suspended`
- **Relations:**
  - Venue belongs to Club
  - Venue has many VenueSportCategories (pivot — multi-sport)
  - Venue has many SportCategories through VenueSportCategories
  - Venue has many VenueImages
  - Venue has many VenueAvailabilityRules
  - Venue has many VenuePricingTiers
  - Venue has many VenueAllowedDurations
  - Venue has many Bookings
- **Notes:**
  - Location defaults to parent Club's lat/lng. Venue can optionally override with its own lat/lng (nullable).
  - Pricing is NOT a flat field — it's defined by VenuePricingTier records (time-based).
  - Allowed booking durations are defined by VenueAllowedDuration records (admin-controlled).
  - `is_featured` moved to Club level — the Club is the featured discovery unit.

---

### Entity: VenueSportCategory (Pivot)
- **Purpose:** Links a venue to one or more sport categories. Enables multi-sport venues.
- **Important Fields:**
  - `id`
  - `venue_id`
  - `sport_category_id`
- **Relations:**
  - belongs to Venue
  - belongs to SportCategory
- **Notes:** Added based on visual evidence from Screen 01 (Home Screen) showing multiple sport icons per venue card.

---

### Entity: VenueImage
- **Purpose:** Stores media assets for a venue.
- **Important Fields:**
  - `id`
  - `venue_id`
  - `url`
  - `is_primary`
  - `sort_order`
- **Relations:** VenueImage belongs to Venue

---

### Entity: VenueAvailabilityRule
> ⚠️ REMOVED — Replaced by `venues.opening_hours JSON` column (spatie/opening-hours). No separate table. Opening hours stored directly on venue in spatie/opening-hours format.

---

### Entity: VenuePricingTier
- **Purpose:** Defines time-based pricing rules for a venue. A single venue can have multiple tiers covering different time windows within the day, each with its own price per slot duration.
- **Important Fields:**
  - `id`
  - `venue_id` (FK → Venue)
  - `name_ar` (e.g., "فترة صباحية"، "فترة مسائية")
  - `name_en`
  - `start_time` (e.g., 06:00)
  - `end_time` (e.g., 12:00)
  - `duration_minutes` (the slot duration this tier applies to — e.g., 90)
  - `price` (price for one slot of this duration in this time window)
  - `currency`
  - `is_active`
- **Statuses:** `active` / `inactive`
- **Relations:**
  - VenuePricingTier belongs to Venue
- **Notes:**
  - A venue may have multiple tiers: e.g., Tier 1 = 06:00–12:00 / 90 min / 50,000 SYP; Tier 2 = 12:00–22:00 / 90 min / 80,000 SYP.
  - A venue may also offer multiple durations: e.g., 45 min tier AND 90 min tier for the same time window.
  - Time windows across tiers for the same duration must not overlap.
  - When generating available slots, the system matches the slot start_time against the active tier for that time window.
  - Slot price is locked at booking creation time — never recalculated.

---

### Entity: VenueAllowedDuration
> ⚠️ REMOVED — No separate table needed. Allowed durations = `SELECT DISTINCT duration_minutes FROM venue_pricing_tiers WHERE venue_id = ? AND is_active = 1`. Eliminating this table removes redundancy.

---

### Entity: Booking
- **Purpose:** Represents a user's reservation of a venue for a specific date and time slot.
- **Important Fields:**
  - `id`
  - `user_id`
  - `venue_id`
  - `sport_category_id` (FK → SportCategory — which sport this booking is for)
  - `booking_code` (unique human-readable code, e.g. "GR0175" — auto-generated at creation, indexed)
  - `source` (enum: `mobile` / `manual`) — mobile = user booking, manual = dashboard booking
  - `manual_type` (enum: `external` / `blocked` — nullable, only for manual source) — external = real person booked offline, blocked = maintenance/closed
  - `manual_note` (nullable string — reason for manual block)
  - `reviewed_at` (nullable timestamp — set when user submits a review for this booking)
  - `booking_date`
  - `start_time`
  - `end_time`
  - `duration_minutes`
  - `total_price`
  - `currency`
  - `status`
  - `notes`
  - `cancelled_at`
  - `cancellation_reason`
  - `created_at`
- **Statuses:** `confirmed` / `cancelled` / `completed` / `no_show` / `failed` / `scheduled`
- **Notes on statuses:**
  - `confirmed` — paid and confirmed
  - `scheduled` — recurring booking created, awaiting payment (slot is reserved)
  - `cancelled` — cancelled by player or admin; if was paid → wallet refund applied
  - `completed` — booking date has passed, player attended
  - `no_show` — booking date passed, player didn't show
  - `failed` — payment failed, slot released, no booking persisted
- **Recurring fields:**
  - `is_recurring` (boolean, default: false)
  - `recurrence_pattern` (JSON nullable — e.g., `{"frequency":"weekly","day_of_week":5,"occurrences":8}`)
  - `recurrence_parent_id` (FK → bookings.id nullable — links to the first booking in a series)
  - `reminder_2h_sent_at` (timestamp nullable — prevents duplicate reminders)
  - `reminder_1h_sent_at` (timestamp nullable)
- **Relations:**
  - Booking belongs to User
  - Booking belongs to Venue
  - Booking belongs to SportCategory
  - Booking has one Payment
  - Booking has one Review (after completion)

---

### Entity: PaymentMethod
- **Purpose:** Stores available payment providers. Admin enables/disables each from Dashboard.
- **Important Fields:**
  - `id`
  - `name` (e.g., "Syriatel Cash", "MTN Cash", "Fatora", "SamaPay")
  - `provider_key` (enum: `syriatel_cash` / `mtn_cash` / `fatora` / `sama_pay`)
  - `flow_type` (enum: `otp` / `webview`)
  - `icon_url`
  - `is_active` (boolean — Admin toggles from Dashboard)
  - `sort_order`
- **Relations:** Payment belongs to PaymentMethod
- **Notes:** Mobile fetches active payment methods from `GET /api/v1/payment-methods`.

---

### Entity: Payment
- **Purpose:** Tracks payment transactions associated with bookings.
- **Important Fields:**
  - `id`
  - `booking_id`
  - `user_id`
  - `amount`
  - `currency`
  - `provider` (enum: `syriatel_cash` / `mtn_cash` / `fatora` / `sama_pay` / `wallet`)
  - `provider_transaction_id` (MTN: invoiceId; Syriatel: transactionId; Fatora: idTransaction)
  - `provider_reference` (MTN: guid; Syriatel: transactionId; Fatora/Sama: transactionReference)
  - `provider_meta` (JSON — stored state needed for confirmation step):
    - MTN: `{ guid, phone, invoiceId, operationNumber, sequence }`
    - Syriatel: `{ transaction_id }`
    - Fatora/SamaPay: `{ transaction_reference }`
  - `flow_type` (enum: `otp` / `webview`) — determines mobile UX
  - `status`
  - `initiated_at`
  - `completed_at`
  - `failed_at`
  - `provider_payload` (JSON — raw callback/response payload from provider)
  - `failure_reason`
- **Statuses:** `pending` / `processing` / `completed` / `failed` / `refunded` / `cancelled`
- **Relations:**
  - Payment belongs to Booking
  - Payment belongs to User

---

### Entity: AppPlatform
- **Purpose:** Represents a mobile platform (iOS, Android, Huawei, etc.) that the app is distributed on. Fully dynamic — Admin creates and manages platforms from Dashboard. No platform is hardcoded.
- **Important Fields:**
  - `id`
  - `platform_key` (unique slug — e.g., `ios`, `android`, `huawei`)
  - `platform_name` (display label — e.g., "iOS", "Android", "Huawei AppGallery")
  - `store_url` — official store page URL (nullable)
  - `direct_apk_url` — direct APK download link (nullable — Android-type platforms only)
  - `direct_apk_enabled` (boolean) — if true + `direct_apk_url` set → use APK link instead of store_url
  - `latest_version` — current latest version on this platform's store (semver, e.g., "1.3.0")
  - `minimum_required_version` — below this → forced update (`is_required: true`)
  - `optional_update_version` — this version gets optional update prompt (nullable)
  - `is_active` (boolean) — inactive platforms are ignored by startup endpoint
  - `updated_by` (FK → AdminUser)
  - `updated_at`
- **Statuses:** `active` / `inactive`
- **Relations:**
  - AppPlatform has many AppEnvironments
  - AppPlatform belongs to AdminUser (last editor)
- **Notes:**
  - Seeded with `ios` and `android` on first deployment.
  - Admin can add new platforms (e.g., `huawei`) without any code change.
  - Version comparison uses semver logic.
  - `direct_apk_enabled` only meaningful for non-iOS platforms.

---

### Entity: AppEnvironment
- **Purpose:** Represents a deployment environment (Live, Stage, Beta, etc.) for a specific platform. Each platform can have multiple environments. Only one environment is active per platform at a time.
- **Important Fields:**
  - `id`
  - `app_platform_id` (FK → AppPlatform)
  - `env_name` (e.g., "Live", "Stage", "Beta")
  - `base_url` — the API base URL for this environment
  - `is_active` (boolean) — only one active per platform; this is what gets returned in startup response
  - `created_by` (FK → AdminUser)
  - `updated_at`
- **Statuses:** `active` / `inactive`
- **Relations:**
  - AppEnvironment belongs to AppPlatform
- **Notes:**
  - DB-level or application-level constraint: only one `is_active = true` per `app_platform_id`.
  - Admin can add new environments (e.g., "Beta") at any time.
  - Switching active environment takes effect within 60 seconds (short TTL cache).

---

### Entity: SystemSetting
- **Purpose:** Stores all dynamic system configurations editable from the Admin Dashboard.
- **Important Fields:**
  - `id`
  - `key` (unique string identifier)
  - `value`
  - `type` (enum: `string` / `integer` / `boolean` / `json` / `text`)
  - `group` (e.g., `auth`, `booking`, `payment`, `notifications`, `app`)
  - `label` (human-readable name for admin UI)
  - `description`
  - `is_public` (whether mobile API can read this)
- **Notes:** This table powers all configurable behavior — OTP expiry, booking limits, cancellation windows, feature flags, etc.
  - Key examples: `otp_expiry_seconds`, `booking_cancellation_window_hours`, `my_grounds_min_rating` (default 3.0), `home_categories_limit`, `home_popular_limit`, `active_sms_provider` (syriatel|mtn|whatsapp), `payment_otp_resend_cooldown_seconds`, `slot_reservation_minutes` (default: 10), `cancellation_deduction_type` (percentage|flat), `cancellation_deduction_value`, `scheduled_booking_payment_window_hours`, `booking_reminder_hours` ([2,1])
  - Commission settings: `commission_type` (added|deducted), `commission_calculation` (flat|percentage), `commission_value`, `cancellation_commission_flat` (e.g., 25000 SYP)

---

### Entity: ContentPage
- **Purpose:** Stores dynamic static content pages (About Us, Privacy Policy, Terms of Service).
- **Important Fields:**
  - `id`
  - `slug` (unique: `about`, `privacy`, `terms`)
  - `title_ar`
  - `title_en`
  - `body_ar`
  - `body_en`
  - `is_active`
  - `updated_at`
- **Notes:** Fully editable from Admin Dashboard. Mobile fetches by slug.

---

### Entity: Wallet
- **Purpose:** Internal wallet for each player. Receives refunds from cancelled bookings. Can be used to pay for future bookings.
- **Important Fields:**
  - `id`
  - `user_id` (FK → User, unique — one wallet per player)
  - `balance` (decimal 10,2 — current balance in SYP)
  - `currency` (default: SYP)
  - `created_at`
- **Relations:**
  - Wallet belongs to User
  - Wallet has many WalletTransactions
- **Notes:** Balance is always computed from WalletTransactions sum for auditability, but cached in `balance` column for performance.

---

### Entity: WalletTransaction
- **Purpose:** Immutable ledger of all wallet credits and debits. Never deleted — append-only.
- **Important Fields:**
  - `id`
  - `wallet_id` (FK → Wallet)
  - `type` (enum: `credit` / `debit`)
  - `amount` (decimal 10,2 — always positive)
  - `balance_after` (decimal — snapshot of balance after this transaction)
  - `reason` (enum: `booking_refund` / `booking_payment` / `admin_adjustment`)
  - `reference_type` (morphable — Booking, etc.)
  - `reference_id`
  - `note` (nullable — admin note for manual adjustments)
  - `created_at`
- **Relations:**
  - WalletTransaction belongs to Wallet
- **Notes:** Append-only. Never update or delete. This is the source of truth for wallet balance.

---

### Entity: SlotReservation
- **Purpose:** Temporary soft-lock on a venue slot during payment flow. Released after 10 minutes if payment not completed.
- **Important Fields:**
  - `id`
  - `venue_id` (FK → Venue)
  - `user_id` (FK → User)
  - `payment_id` (FK → Payment — nullable, set when payment is initiated)
  - `booking_date` (date)
  - `start_time` (time)
  - `end_time` (time)
  - `duration_minutes` (integer)
  - `sport_category_id` (FK → SportCategory)
  - `reserved_until` (timestamp — expires after 10 minutes)
  - `created_at`
- **Statuses:** active (reserved_until > NOW()) / expired
- **Relations:**
  - SlotReservation belongs to Venue
  - SlotReservation belongs to User
- **Notes:**
  - Slot availability query must exclude slots with active reservation.
  - Laravel Scheduler expires old reservations every minute.
  - On payment success → reservation deleted + booking created.
  - TTL configurable: `slot_reservation_minutes` in Settings (default: 10).

---

### Entity: SavedVenue
- **Purpose:** Represents a venue explicitly saved/favourited by a user. Used in "My Grounds" Saved section.
- **Important Fields:**
  - `id`
  - `user_id` (FK → User)
  - `venue_id` (FK → Venue)
  - `created_at`
- **Statuses:** N/A — presence = saved, absence = not saved
- **Relations:**
  - SavedVenue belongs to User
  - SavedVenue belongs to Venue
- **Constraints:** Unique on (user_id, venue_id) — one save per venue per user
- **Notes:**
  - Shown in My Grounds regardless of venue rating.
  - Toggle: save → insert, unsave → delete.

---

### Entity: Review
- **Purpose:** Reviews are at CLUB level (LD-057). A player who completed >= 1 booking at any venue of a club can write ONE review for that club.
- **Important Fields:**
  - `id`
  - `user_id` (FK → User — always stored, never null)
  - `club_id` (FK → Club — LD-057: reviews at club level, not venue level)
  - `booking_id` (FK → Booking — qualifying proof booking)
  - `venue_hint` (VARCHAR 255 — snapshot of venue name at review time, shown even when anonymous)
  - `rating` (TINYINT 1-5 — integer, not decimal)
  - `body` (TEXT nullable — if provided, min 50 chars)
  - `is_anonymous` (boolean, default false)
  - `is_published` (boolean default true — Admin can hide)
  - `created_at`
- **Unique constraint:** (user_id, club_id) — one review per player per club
- **Statuses:** published / hidden
- **Relations:**
  - Review belongs to User
  - Review belongs to Club (NOT Venue)
  - Review belongs to Booking (eligibility proof)
- **Notes:**
  - `avg_rating` on Club = AVG of reviews.rating directly
  - `venue_hint` preserved as snapshot — venue name may change later
  - Admin always sees real identity regardless of is_anonymous

---

### Entity: Competition
- **Purpose:** Represents an admin-managed sports competition or tournament displayed in the Events tab.
- **Important Fields:**
  - `id`
  - `title_ar`
  - `title_en`
  - `description_ar`
  - `description_en`
  - `image_url`
  - `start_date`
  - `end_date`
  - `status`
  - `is_published`
  - `created_by` (FK → AdminUser)
  - `created_at`
- **Statuses:** `draft` / `published` / `archived`
- **Relations:** Competition belongs to AdminUser (creator)
- **Notes:**
  - Managed exclusively from `/admin/` Super Admin Dashboard.
  - Mobile app fetches published competitions from `GET /api/v1/events/competitions`.
  - No user interaction — display only.

---

### Entity: AdminUser
> ⚠️ REMOVED — Merged into `users` table (LD-051). Admin users are now regular User records with Spatie roles assigned (`super_admin`, `data_entry`, etc.). No separate table.

---

## 7. Relationships

### Authentication
- User has many OTPChallenges
- User has many SocialIdentities
- OTPChallenge may belong to User (nullable before account creation)
- SocialIdentity belongs to User

### Geographic Hierarchy
- City has many Areas
- Area belongs to City
- Area has many Clubs
- Club belongs to Area
- User belongs to City (default_city_id)
- User belongs to Area (default_area_id, optional)

### Club & Staff
- ClubOwner has many Clubs
- Club belongs to ClubOwner
- Club belongs to Area
- Club has many Venues
- Club has many ClubImages
- Club has many ClubStaffMembers

### Venue & Categories
- Venue belongs to Club
- Venue belongs to SportCategory (direct FK — one sport per venue, LD-054)
- SportCategory has many Venues
- Venue has many VenuePricingTiers
- Allowed durations derived from DISTINCT(VenuePricingTier.duration_minutes)
- Venue opening_hours stored as JSON on venue record (spatie/opening-hours)

### Bookings & Payments
- User has many Bookings
- Venue has many Bookings
- Booking belongs to User
- Booking belongs to Venue
- Booking belongs to SportCategory
- Booking has one Payment
- Booking has one Review (optional, after completion)
- Payment belongs to Booking
- Payment belongs to User

### Reviews
- Review belongs to Club (LD-057 — club level, not venue level)
- Review belongs to User
- Review belongs to Booking (eligibility proof)
- Club has many Reviews
- User has many Reviews
- Unique: (user_id, club_id) — one review per player per club

### Saved Venues
- User has many SavedVenues
- Venue has many SavedVenues
- SavedVenue belongs to User
- SavedVenue belongs to Venue

### Wallet
- User has one Wallet
- Wallet belongs to User
- Wallet has many WalletTransactions
- WalletTransaction belongs to Wallet
- WalletTransaction morphs to Booking (reference)

### Slot Reservations
- SlotReservation belongs to Venue
- SlotReservation belongs to User
- Venue has many SlotReservations (active)

### App Startup
- AppPlatform has many AppEnvironments
- AppPlatform belongs to AdminUser (last_updated_by)
- AppEnvironment belongs to AppPlatform
- Only one AppEnvironment can be active per AppPlatform at a time

### Content & Config
- SystemSetting is standalone (key-value store)
- ContentPage is standalone (slug-based)
- AdminUser is standalone (separate auth domain)

---

## 8. Admin Panel Requirements

### General Principles
- Web-based dashboard, accessible only to authenticated Admin users
- Every entity in the system must have a corresponding admin view
- All lists must support: search, filter, sort, pagination, export (CSV at minimum)
- All configurable values must be editable without code deployment

### Admin Sections Required

#### 8.1 Dashboard / Overview
- Total bookings today / this week / this month
- Revenue summary per payment provider
- Active venues count
- Registered users count
- Recent bookings feed
- Alerts: failed payments, cancelled bookings, pending reviews

#### 8.2 User Management
- List all users (search by name, phone, status)
- View user profile and booking history
- Block / suspend / reactivate user
- View linked social identities
- View OTP request logs (optional)

#### 8.3 Region Management
- Full CRUD for regions
- Set region name (Arabic + English)
- Set governorate
- Activate / deactivate region
- Sort order control
- View clubs count per region

#### 8.3b Sport Categories Management
- Full CRUD for sport categories
- Upload category icon and image
- Set display order
- Activate / deactivate category
- Slug auto-generation

#### 8.3c Club Approval Queue
- Dedicated view for clubs in `pending_approval` status
- Shows: club name, owner name, **owner phone number** (prominent), submission date, number of venues
- Admin actions: Approve / Reject (with rejection reason)
- Rejected clubs notify owner via SMS
- Approved clubs go live immediately on mobile app

#### 8.4 Club Management
- Full CRUD for clubs
- Assign club to region
- Assign / change club owner
- Upload logo and cover image + gallery images
- Set location (map picker with lat/lng)
- Activate / deactivate / suspend / approve club
- Mark club as featured (appears in Popular Grounds)
- View all venues nested under each club

#### 8.4b Venue Management (nested under Club)
- Full CRUD for venues within a club
- Assign one or more sport categories to venue (multi-sport)
- Upload venue images
- Set pricing per hour
- Set min/max booking duration
- Define weekly availability rules (per day of week: open_time, close_time)
- Set amenities
- Activate / deactivate venue

#### 8.5 Booking Management
- List all bookings (filter by venue, user, date, status, source: mobile/manual)
- View booking details
- Cancel booking with reason
- Manual status override
- Booking history per user or per venue

#### 8.5b Manual Booking (Club Dashboard — club_admin + club_data_entry)
- Calendar view of all slots for each venue
- Add manual booking: select date + time + duration + type (external/blocked) + note
- Edit/delete manual bookings
- Visual distinction between mobile bookings and manual bookings on calendar
- Manual bookings block corresponding slots immediately for mobile users

#### 8.6 Payment Management
- List all payment transactions
- Filter by provider, status, date range
- View raw provider payload
- Mark as manually reconciled (if needed)
- Refund initiation (TBD per provider capability)

#### 8.7 Content Management
- Edit static content pages (About, Privacy, Terms) — rich text editor, bilingual
- Manage banners / announcements (TBD after screen analysis)

#### 8.8 System Settings
- Edit all SystemSetting records grouped by category
- Toggle feature flags
- Set OTP expiry, resend cooldown, max attempts
- Set booking rules (min/max duration, cancellation window)
- Set payment provider configurations (API keys, endpoints — stored securely)

#### 8.9 Admin User Management
- Create / edit / deactivate admin accounts
- Create all club accounts (Club Owner, Club Admin, Data Entry) — no self-registration
- Assign roles via Spatie Permissions

#### 8.0 App Startup & Version Control (Super Admin Only)
- Dedicated section in `/admin/` — highest visibility, always first in sidebar

**Platform Manager:**
- List all platforms (iOS, Android, Huawei...) with status badges
- "Add Platform" button — fully DB-driven, no code change needed
- Per platform: platform_key, platform_name, is_active toggle

**Per-Platform Config Panel:**
- Environments sub-section: list environments (Live/Stage/Beta) with URLs, Add Environment button, active selector (one active at a time), visual indicators (Live=green/Stage=yellow/Beta=blue), red warning banner when active != Live
- Version Control sub-section: Latest Version, Minimum Required Version (with warning if > latest), Optional Update Version, Store URL, Direct APK URL (nullable), Direct APK Enabled toggle
- Last updated by + timestamp
- Changes take effect within 60 seconds


#### 8.10 Roles & Permissions Management (Spatie)
- Create / rename / delete roles
- Attach / detach permissions to roles dynamically
- View all permissions in the system
- Assign roles to admin users and club staff

#### 8.11 Reviews Management
- List all reviews (filter by venue, club, rating, date)
- Publish / hide individual reviews
- View reviewer + linked booking
- Bulk moderation tools

---

## 9. API Requirements

> All endpoints are under `/api/v1/` unless stated otherwise.
> All requests/responses use JSON.
> Authentication uses Bearer token in Authorization header.
> [DRAFT] tag means contract is not yet finalized.

---

### 9.0 App Startup API

#### Feature: App Startup [CONFIRMED]
- **Endpoint:** `POST /api/v1/app/startup`
- **Auth:** Public — called before any user authentication, on every app launch
- **Request:**
  ```json
  {
    "platform_key": "ios" | "android" | "huawei" | "<any-future-key>",
    "app_version": "1.2.0"
  }
  ```
- **Response:**
  ```json
  {
    "base_url": "https://live.example.com",
    "update": {
      "is_required": false,
      "is_optional": true,
      "latest_version": "1.3.0",
      "download_url": "https://apps.apple.com/app/id123456"
    },
    "app_settings": {
      "app_name": "SportBook",
      "maintenance_mode": false,
      "maintenance_message": null
    }
  }
  ```
- **Version Logic:**
  - `app_version < minimum_required_version` → `is_required: true`
  - `app_version >= minimum_required_version AND < latest_version` → `is_optional: true`
  - `app_version == latest_version` → both `false`
- **Download URL Logic:**
  - Default: return `store_url` of the platform
  - If `direct_apk_enabled = true` AND `direct_apk_url` is set → return `direct_apk_url` instead
- **Validation:**
  - `platform_key` required, must match an active AppPlatform record
  - `app_version` required, valid semver format (x.y.z)
  - If `platform_key` not found or inactive → return 404 with clear error
- **Performance:** Queries: 1 row from AppPlatform + 1 row from AppEnvironment. Response < 50ms.
- **Caching:** Max 60-second TTL — environment switches must propagate quickly.
- **Notes:**
  - ONLY hardcoded URL in the Flutter app — everything else is dynamic after this call.
  - Controlled entirely from `/admin/` Dashboard — Super Admin only.
  - Each platform is fully independent — adding Huawei requires zero code change.

---

### 9.1 Authentication APIs

#### Feature: Request Phone OTP [DRAFT]
- **Endpoint:** `POST /api/v1/auth/otp/request`
- **Auth:** Public
- **Request:**
  ```json
  {
    "phone_number": "+963XXXXXXXXX",
    "device_id": "optional-string"
  }
  ```
- **Response:**
  ```json
  {
    "verification_request_id": "uuid",
    "expires_in_seconds": 120,
    "resend_after_seconds": 60,
    "masked_destination": "+963*****XXX"
  }
  ```
- **Validation:** Phone required, valid format, rate limited per IP + phone
- **Notes:** OTP code is never returned in response. Delivered via SMS only.

---

#### Feature: Verify Phone OTP [DRAFT]
- **Endpoint:** `POST /api/v1/auth/otp/verify`
- **Auth:** Public
- **Request:**
  ```json
  {
    "verification_request_id": "uuid",
    "otp_code": "123456",
    "device_id": "optional-string"
  }
  ```
- **Response:**
  ```json
  {
    "access_token": "...",
    "refresh_token": "...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "auth_outcome": "login | registration",
    "requires_profile_completion": true,
    "next_step": "profile_completion | home",
    "user": { ... }
  }
  ```
- **Validation:** Request ID valid + unexpired, OTP 5 digits, attempt limits enforced

---

#### Feature: Resend Phone OTP [DRAFT]
- **Endpoint:** `POST /api/v1/auth/otp/resend`
- **Auth:** Public
- **Request:** `{ "verification_request_id": "uuid" }`
- **Response:** Same shape as request OTP response
- **Validation:** Cooldown enforced, max resend count enforced

---

#### Feature: Google Sign-In [DRAFT]
- **Endpoint:** `POST /api/v1/auth/google`
- **Auth:** Public
- **Request:**
  ```json
  {
    "id_token": "google-id-token",
    "device_id": "optional-string"
  }
  ```
- **Response:** Same shape as OTP verify response + `onboarding_prefill` with name, email fields
- **Validation:** Token verified server-side against Google

---

#### Feature: Token Refresh [DRAFT]
- **Endpoint:** `POST /api/v1/auth/refresh`
- **Auth:** Public (with refresh token)
- **Request:** `{ "refresh_token": "..." }`
- **Response:** New access_token + expires_in

---

#### Feature: Logout [DRAFT]
- **Endpoint:** `POST /api/v1/auth/logout`
- **Auth:** Required
- **Action:** Invalidate current token / session

---

### 9.2 User Profile APIs

#### Feature: Get Current User [DRAFT]
- **Endpoint:** `GET /api/v1/me`
- **Auth:** Required
- **Response:** Full user profile + profile completion state

#### Feature: Update Profile [DRAFT]
- **Endpoint:** `PUT /api/v1/me`
- **Auth:** Required
- **Request:** name, email (optional), avatar (optional)

#### Feature: Add Phone Number (Google Users) [DRAFT]
- **Endpoint:** `POST /api/v1/me/phone/request`
- **Auth:** Required
- **Request:** `{ "phone_number": "+963XXXXXXXXX" }`
- **Response:** `{ "verification_request_id": "uuid", "expires_in_seconds": 120 }`
- **Notes:** Sends OTP to new number. Same OTP flow as auth.

#### Feature: Verify + Save Phone Number [DRAFT]
- **Endpoint:** `POST /api/v1/me/phone/verify`
- **Auth:** Required
- **Request:** `{ "verification_request_id": "uuid", "otp_code": "123456" }`
- **Response:** `{ "phone_number": "+963...", "phone_verified_at": "..." }`
- **Notes:** On success → saves phone_number + phone_verified_at on User record.

---

### 9.3 Sport Categories APIs

#### Feature: List Cities [DRAFT]
- **Endpoint:** `GET /api/v1/cities`
- **Auth:** Public
- **Response:** `[ { "id", "name_ar", "name_en" } ]`
- **Notes:** Fetched on Profile Completion + Area selector. Cacheable.

#### Feature: List Areas by City [DRAFT]
- **Endpoint:** `GET /api/v1/cities/{city_id}/areas`
- **Auth:** Public
- **Response:** `[ { "id", "name_ar", "name_en" } ]`
- **Notes:** Fetched when user selects city. Used in Filter Sheet and Profile Completion.

#### Feature: Get Price Range [DRAFT]
- **Endpoint:** `GET /api/v1/clubs/price-range`
- **Auth:** Public
- **Query Params:** `city_id` (required)
- **Response:** `{ "min_price": 10000, "max_price": 500000, "currency": "SYP" }`
- **Notes:** Used to set slider bounds in Filter Sheet. Re-fetched every time Filter Sheet opens.

#### Feature: List Active Categories [DRAFT]
- **Endpoint:** `GET /api/v1/categories`
- **Auth:** Public
- **Response:** Ordered list of active categories with icon, image, slug
- **Notes:** Mobile fetches this on app load. Lightweight response only.

---

### 9.4 Venue APIs

#### Feature: List Regions [DRAFT]
- **Endpoint:** `GET /api/v1/regions`
- **Auth:** Public
- **Response:** `[ { "id", "name_ar", "name_en", "governorate_ar", "governorate_en" } ]`
- **Notes:** Mobile fetches this on startup or during region selection. Lightweight, cacheable.

---

#### Feature: List Clubs [DRAFT]
- **Endpoint:** `GET /api/v1/clubs`
- **Auth:** Public
- **Query Params:**
  - `q` — full-text search across club name, venue name, region name, sport name
  - `sport_category_id` — filter by sport (can be comma-separated for multiple)
  - `region_id` — defaults to user's `default_region_id` if authenticated; required if guest
  - `price_min` / `price_max` — price per hour range (applied at venue level)
  - `rating_min` — minimum club rating (e.g., 4.0)
  - `featured` — boolean, returns only featured clubs (used for Popular Grounds section)
  - `lat` / `lng` — for nearby sorting within region
  - `page` / `per_page`
- **Response:**
  ```json
  {
    "data": [
      {
        "id", "name", "address", "region": { "id", "name" },
        "logo_url", "cover_image_url", "rating", "is_featured",
        "sports": [ { "id", "name", "icon_url" } ],
        "price_from",
        "distance_km"
      }
    ],
    "meta": { "current_page", "per_page", "total", "last_page" }
  }
  ```
- **Notes:**
  - `price_from` = minimum price across all venues in this club
  - `sports` = union of all sport categories across all venues in this club
  - `distance_km` = only present when `lat`/`lng` provided

#### Feature: List Venues [DRAFT]
- **Endpoint:** `GET /api/v1/clubs/{club_id}/venues`
- **Auth:** Public
- **Query Params:** `sport_category_id`, `page`, `per_page`
- **Response:** Paginated list of venues inside a specific club, with their sport tags and pricing

#### Feature: Get Club Detail [DRAFT]
- **Endpoint:** `GET /api/v1/clubs/{id}`
- **Auth:** Public
- **Response:**
  ```json
  {
    "id", "name", "description", "address", "region": { "id", "name" },
    "logo_url", "cover_image_url", "phone_number",
    "rating", "latitude", "longitude",
    "images": [],
    "venues": [
      {
        "id", "name", "sports": [], "price_per_hour",
        "min_duration_minutes", "amenities": [], "images": []
      }
    ]
  }
  ```
- **Notes:** Returns Club with all its nested Venues. Mobile uses this to show Club detail screen and let user pick a Venue to book.

#### Feature: Get Venue Available Slots [DRAFT]
- **Endpoint:** `GET /api/v1/clubs/{club_id}/venues/{venue_id}/slots`
- **Auth:** Public
- **Query Params:** `date`, `sport_category_id`
- **Response:**
  ```json
  {
    "date": "2025-01-15",
    "venue_id": "uuid",
    "sport_category_id": "uuid",
    "slots": [
      { "start_time": "08:00", "end_time": "09:00", "is_available": true, "price": 50000 }
    ]
  }
  ```
- **Notes:** `sport_category_id` required for multi-sport venues to determine correct slot availability.

---

### 9.5 Booking APIs

#### Feature: Create Booking [DRAFT]
- **Endpoint:** `POST /api/v1/bookings`
- **Auth:** Required
- **Request:** venue_id, date, start_time, end_time, notes
- **Response:** Booking record + payment initiation data

#### Feature: List My Bookings [DRAFT]
- **Endpoint:** `GET /api/v1/bookings`
- **Auth:** Required
- **Query Params:** `status`, `page`, `per_page`

#### Feature: Get Booking Detail [DRAFT]
- **Endpoint:** `GET /api/v1/bookings/{id}`
- **Auth:** Required

#### Feature: Cancel Booking [DRAFT]
- **Endpoint:** `POST /api/v1/bookings/{id}/cancel`
- **Auth:** Required
- **Request:** `{ "reason": "..." }`

---

### 9.4b My Grounds APIs

#### Feature: Get My Grounds [DRAFT]
- **Endpoint:** `GET /api/v1/me/grounds`
- **Auth:** Required
- **Response:**
  ```json
  {
    "grounds": [
      {
        "venue_id": "uuid",
        "venue_name": "ملعب كرة قدم",
        "venue_image": "https://...",
        "club_name": "نادي الفيحاء",
        "avg_rating": 4.2,
        "type": "saved" | "recent",
        "last_booking_duration_minutes": 90,
        "saved_at": "2025-01-10T..."
      }
    ]
  }
  ```
- **Logic:**
  - Fetch saved venues (all, regardless of rating) → `type: "saved"`
  - Fetch distinct venues from user's bookings JOIN reviews: WHERE (no review exists OR user's own review.rating >= threshold) → `type: "recent"`
  - NOTE: filter is on user's personal review rating — NOT venue's global avg_rating
  - Merge, deduplicate (saved wins), sort: saved first then recent by last booking date
  - `threshold` from SystemSettings key `my_grounds_min_rating` (default: 3.0)
- **Notes:** No pagination needed initially — list is per-user and naturally limited

#### Feature: Save Venue [DRAFT]
- **Endpoint:** `POST /api/v1/venues/{venue_id}/save`
- **Auth:** Required
- **Action:** Insert SavedVenue record
- **Response:** `{ "saved": true }`

#### Feature: Unsave Venue [DRAFT]
- **Endpoint:** `DELETE /api/v1/venues/{venue_id}/save`
- **Auth:** Required
- **Action:** Delete SavedVenue record
- **Response:** `{ "saved": false }`

---

### 9.5b Reviews APIs

#### Feature: Submit Review [CONFIRMED]
- **Endpoint:** `POST /api/v1/bookings/{booking_id}/review`
- **Auth:** Required (booking owner only)
- **Request:**
  ```json
  {
    "rating": 4.5,
    "body": "نص المراجعة الاختياري...",
    "is_anonymous": false
  }
  ```
- **Validation:**
  - `rating` required, decimal between 1.0 and 5.0
  - `body` optional — if provided: min 50 chars, max 1000 chars, UTF-8
  - booking must be `completed` + belong to authenticated user
  - no existing review for this booking
- **Notes:** `is_anonymous = true` → public display hides name/avatar; real user_id always stored

#### Feature: Get Venue Reviews [DRAFT]
- **Endpoint:** `GET /api/v1/clubs/{club_id}/venues/{venue_id}/reviews`
- **Auth:** Public
- **Query Params:** `page`, `per_page`
- **Response:** Paginated reviews with user name, avatar, rating, body, created_at

---

### 9.6 Payment APIs

#### Feature: Initiate Payment [CONFIRMED — two flows]
- **Endpoint:** `POST /api/v1/payments/initiate`
- **Auth:** Required
- **Request:**
  ```json
  {
    "booking_id": "uuid",
    "provider": "mtn_cash | syriatel_cash | fatora | sama_pay",
    "phone_number": "+963XXXXXXXXX"  // required for MTN + Syriatel only
  }
  ```
- **Response (MTN / Syriatel — OTP flow):**
  ```json
  {
    "payment_id": "uuid",
    "flow": "otp",
    "provider": "mtn_cash",
    "message": "تم إرسال رمز التحقق",
    "requires_otp_confirmation": true
  }
  ```
- **Response (Fatora / SamaPay — WebView flow):**
  ```json
  {
    "payment_id": "uuid",
    "flow": "webview",
    "provider": "fatora",
    "hosted_url": "https://tecom.albaraka.com.sy:8433/.../completeTransaction",
    "requires_otp_confirmation": false
  }
  ```
- **Notes:** `flow` field tells mobile which UX to show — OTP screen or WebView.

#### Feature: Confirm Payment OTP [CONFIRMED — MTN + Syriatel only]
- **Endpoint:** `POST /api/v1/payments/{id}/confirm`
- **Auth:** Required
- **Request:** `{ "otp_code": "123456" }`
- **Action:**
  - MTN: hashes OTP → calls `confirmPayment(guid, hashedOtp, phone, invoiceId, operationNumber)`
  - Syriatel: calls `paymentConfirmation(otp, transactionId)`
- **Response:** `{ "status": "completed", "booking_status": "confirmed" }`

#### Feature: Resend Payment OTP [CONFIRMED — Syriatel only]
- **Endpoint:** `POST /api/v1/payments/{id}/resend-otp`
- **Auth:** Required
- **Action:** Calls Syriatel `resendOTP(transactionId)`
- **Notes:** MTN Cash payment OTP supports a pseudo-resend by calling `initiatePayment()` again with the same `invoiceId` + same `phone` + incremented `sequence` param. This generates a new `guid` (format: `invoiceId-sequence`) and triggers a new OTP from MTN — no new invoice needed. Backend must store current `sequence` in `provider_meta` and increment on resend.

#### Feature: Payment Callback (Fatora / SamaPay) [CONFIRMED]
- **Endpoint:** `POST /api/v1/payments/callback/fatora` and `POST /api/v1/payments/callback/sama_pay`
- **Auth:** None — public endpoint, verified by payload signature or transactionReference lookup
- **Action:** Parse `transactionStat` + `idTransaction` → update Payment status → update Booking status
- **Response to Fatora:** `{"responseCode":"OK"}` on success, `{"responseCode":"KO"}` on failure
- **Notes:** This is a server-to-server callback — mobile does NOT call this

#### Feature: Get Payment Status [DRAFT]
- **Endpoint:** `GET /api/v1/payments/{id}/status`
- **Auth:** Required
- **Notes:** Mobile polls this after WebView closes to check if Fatora/SamaPay callback was received

---

### 9.7 Events APIs

#### Feature: Get Today's Matches [DRAFT]
- **Endpoint:** `GET /api/v1/events/matches/today`
- **Auth:** Public
- **Response:**
  ```json
  {
    "date": "2025-03-31",
    "matches": [
      {
        "id": "external_id",
        "competition": { "name": "La Liga", "emblem_url": "..." },
        "home_team": { "name": "Real Madrid", "crest_url": "..." },
        "away_team": { "name": "Barcelona", "crest_url": "..." },
        "kick_off": "20:00",
        "status": "SCHEDULED | LIVE | FINISHED",
        "score": { "home": null, "away": null }
      }
    ],
    "cached_at": "2025-03-31T17:55:00Z"
  }
  ```
- **Notes:**
  - Our backend proxies and caches football-data.org response for 5 minutes.
  - Mobile NEVER calls football-data.org directly.
  - External source: `football-data.org` free tier.

#### Feature: Get Competitions [DRAFT]
- **Endpoint:** `GET /api/v1/events/competitions`
- **Auth:** Public
- **Response:** List of published competitions ordered by start_date — image, title, dates, description.

#### Feature: Get Upcoming Matches [DRAFT]
- **Endpoint:** `GET /api/v1/events/matches/upcoming`
- **Auth:** Public
- **Query Params:** `days` (default: 7)
- **Response:** Same shape as today's matches + `match_date` field, grouped by date
- **Cache:** 30 minutes — Laravel file cache

---

### 9.8 Content APIs

#### Feature: Get Content Page [DRAFT]
- **Endpoint:** `GET /api/v1/content/{slug}`
- **Auth:** Public
- **Slugs:** `about`, `privacy`, `terms`
- **Response:** `{ "title_ar", "title_en", "body_ar", "body_en" }`

#### Feature: Get App Settings [DRAFT]
- **Endpoint:** `GET /api/v1/settings`
- **Auth:** Public (filtered to is_public=true only)
- **Response:** Key-value map of public settings
- **Notes:** Mobile uses this on startup to get feature flags, app config, etc.

---

## 10. Mobile Integration Notes

> This team does not build the mobile app. These notes are design constraints to ensure our APIs serve the mobile team correctly.

### 10.1 General Principles
- All list endpoints must support pagination (page + per_page)
- All responses must include only necessary fields — no over-fetching
- Enum values must be consistent and documented (mobile team needs these as constants)
- Error responses must be structured and machine-readable (error codes, not just messages)
- Language support: API should support `Accept-Language: ar` / `en` header for bilingual content

### 10.2 Key Mobile Needs Per Module
| Module | Mobile Need |
|---|---|
| App Startup | First call on splash screen — returns base_url, update policy, app settings |
| Auth | Clear next_step field after auth, prefill data from Google |
| Regions/Cities | Fetch cities on Profile Completion; areas lazy-loaded by city; both cached |
| Categories | Lightweight list on app load, icons/images URLs; cached |
| Clubs | Paginated + filterable list scoped by region; primary discovery surface |
| Venues | Nested under Club detail; not a standalone listing |
| Slots | Real-time per venue+date+sport; never cached |
| Bookings | My bookings list with status, cancellation support |
| Payments | Two UX flows: (1) OTP input screen for MTN/Syriatel, (2) WebView for Fatora/SamaPay. Poll `/payments/{id}/status` after WebView closes. |
| Content | Static pages by slug, public settings on startup |

### 10.3 Offline / Performance Considerations
- **App Startup:** First call on every launch — must be < 50ms. Short TTL cache (60s max).
- Categories list: cacheable (rarely changes)
- App settings: cacheable with TTL
- Venue list: paginated, NOT cached (availability changes)
- Slots: real-time, never cached
- Bookings: always fresh

### 10.4 Error Contract (Standard)
All API errors must follow:
```json
{
  "success": false,
  "error": {
    "code": "MACHINE_READABLE_CODE",
    "message": "Human readable message",
    "details": { }
  }
}
```

### 10.5 Enums / Constants Mobile Will Need
- `auth_outcome`: `login`, `registration`
- `region_id`: integer — user always has one active region
- `next_step`: `home`, `profile_completion`
- `booking_status`: `pending`, `confirmed`, `cancelled`, `completed`, `no_show`
- `payment_status`: `pending`, `processing`, `completed`, `failed`, `refunded`, `cancelled`
- `payment_provider`: `syriatel_cash`, `mtn_cash`, `fatora`, `sama_pay`
- `account_status`: `active`, `blocked`, `suspended`, `pending_profile_completion`
- Error codes requiring mobile action: `PHONE_REQUIRED` (show add phone prompt), `OTP_INVALID`, `OTP_EXPIRED`, `OTP_LOCKED`, `SLOT_UNAVAILABLE`, `PAYMENT_FAILED`

---

## 11. Validation and Business Rules

### Auth Rules
- OTP length: **5 digits** (confirmed)
- OTP channel selection: Backend checks WhatsApp availability via Baileys first. If available → player chooses channel. If not → SMS sent immediately.
- `otp_challenges.channel` records which channel was used for audit and debugging.
- OTP expiry: configurable via SystemSetting key `otp_expiry_seconds` (default: 120)
- OTP resend cooldown: configurable via SystemSetting key `otp_resend_cooldown_seconds` (default: 60)
- Max OTP resend attempts: configurable via SystemSetting key `otp_max_resend` (default: 3)
- Max OTP verify attempts: configurable via SystemSetting key `otp_max_attempts` (default: 5 → then lock)
- Rate limiting: per phone number + per IP
- OTP code: stored as hash (bcrypt or sha256) — never plain text in DB
- Countdown timer on mobile driven by `resend_after_seconds` from API — never hardcoded
- Error codes: `OTP_INVALID`, `OTP_EXPIRED`, `OTP_MAX_ATTEMPTS`, `OTP_LOCKED`, `REQUEST_NOT_FOUND`
- Google auth verification: Firebase JWT via Google JWKS endpoint (no kreait) — `FirebaseAuthService` class

### General Data Rules
- All text fields that accept Arabic content must use `utf8mb4` collation (confirmed from Write a Review screen — Arabic RTL text shown)
- `booking_code` format: prefix (e.g., "GR") + zero-padded number or random alphanumeric — unique + indexed

### History Rules
- History tab = bookings WHERE status IN (`completed`, `cancelled`) ordered by date DESC
- "Write a review" appears only on `completed` bookings
- Cancelled bookings in History must visually distinguish from completed (badge, color, strikethrough)

### Cached Computed Fields — Update Strategy (Observers)
- `Club.avg_rating` + `Club.reviews_count`: updated by `ReviewObserver` on create/hide/delete
- `Club.price_from`: updated by `VenuePricingTierObserver` on create/update/delete
- `Venue.avg_rating` + `Venue.reviews_count`: updated by `ReviewObserver` on create/hide/delete
- All Observers dispatch updates via database queue job — not synchronous

### My Grounds Rating Filter — Two Distinct Rating Concepts
- **User's personal rating** (`reviews.rating` WHERE `reviews.user_id = current_user`) → used ONLY for My Grounds Recent filter
- **Venue public avg_rating** (`venues.avg_rating` = AVG of all published reviews) → used for public venue cards, listings, search ranking
- These two are completely separate — never mix them

### Booking Rules
- **SLOT_RESERVATION:** Upon payment initiation, a SlotReservation is created (10-min TTL). Slot availability checks exclude active reservations. On payment success → reservation deleted + booking created.
- **SCHEDULED_BOOKING:** Recurring bookings created with `scheduled` status. Each requires separate manual payment within configured window. Cancellation of unpaid scheduled booking = free (no wallet deduction).
- **WALLET_PAYMENT:** Player can pay using wallet balance. `payments.provider = wallet`. Wallet must have sufficient balance.
- **CANCELLATION_WINDOW:** Cancellation blocked if `NOW() > booking.start_datetime - 30 minutes`. Threshold configurable via BookingSettings (`cancellation_min_minutes_before`, default: 30).
- **CANCELLATION_CONFIRMATION:** Player must type "cancel" / "الغاء" / "إلغاء" (case-insensitive, hamza-insensitive) to confirm cancellation of a paid booking.
- **VACANCY_NOTIFICATION:** On successful cancellation → `VenueAvailableNotificationJob` dispatched → notifies saved/nearby players within same area + sport preference.
- **REFUND_TO_WALLET:** Cancellation of paid booking → refund = `total_price - deduction`. Deduction configured by Admin (flat or percentage). Goes to player's Wallet.
- **PHONE_REQUIRED:** Booking creation is blocked if `user.phone_number IS NULL` → backend returns error code `PHONE_REQUIRED`. Mobile shows prompt to add phone number.
- **NO PENDING STATE:** Booking is created ONLY after payment confirmation — no slot hold without payment.
- **Race condition protection:** Use `SELECT FOR UPDATE` / DB transaction when creating booking to prevent double-booking.
- **Manual bookings:** Club Dashboard can add manual/blocked bookings — these block slots for mobile users.
- A venue slot cannot be double-booked (any source: mobile or manual)
- Booking duration must be in the venue's VenueAllowedDurations set — no free duration input
- Bookings can only be created for future dates
- Cancellation window: configurable via SystemSetting (e.g., must cancel at least X hours before)
- A user cannot have two bookings with overlapping times (TBD)
- For multi-sport venues: `sport_category_id` is required at booking time
- Slot price is calculated from the matching VenuePricingTier at booking creation and locked — never recalculated

### Pricing Rules
- Each venue has VenuePricingTiers: time window (start_time → end_time) + duration + price
- Tiers for the same duration must not have overlapping time windows
- If no tier covers a time window, that time window is not bookable
- Price shown to user = price from the tier matching the slot's start_time and selected duration
- Price is stored on the Booking record at creation time

### Payment Rules
- A booking must be paid to be confirmed
- Payment must be initiated within X minutes of booking creation (configurable)
- Unpaid bookings expire automatically and release the slot
- Each payment attempt is logged separately

---

## 12. Permissions and Roles

### Mobile User — Full (phone verified)
- view: categories, clubs, venues, venue slots, content pages, public settings
- create: booking, payment, own profile, reviews
- update: own profile, cancel own booking
- delete: saved venues

### Mobile User — Limited (Google user, no phone yet)
- view: everything (same as full)
- create: own profile only — **CANNOT create bookings or payments**
- Blocked actions return: `PHONE_REQUIRED` error code

### Club Owner (صاحب النادي — Read Only)
- view: own clubs statistics, revenue reports, booking counts, occupancy rates
- **Cannot:** edit venues, pricing, availability, manage staff, view user data

### Club Admin (أدمن النادي)
- create: venues under own club, staff members for own club
- view: own club full detail, own venues, own bookings, own revenue
- update: own club profile, own venues, pricing tiers, availability rules, staff roles
- delete: own venues (restricted if active bookings), own staff members
- **Cannot:** view other clubs, access system settings, manage app users

### Club Data Entry (مدخل البيانات)
- create: venues under own club
- view: own club venues and schedules
- update: venue info, images, pricing tiers, availability rules
- **Cannot:** view financials, manage staff, access bookings, manage users

### Admin (Super Admin)
- Full access to all entities and all operations
- Includes: approve/suspend ClubOwner accounts, override any booking, manage all settings

### Admin Staff (TBD — roles to be defined after Dashboard screens)
- Scoped access based on role assignment

### Public / Guest
- view: categories, clubs, venues, content pages, public settings
- No booking or payment capabilities without authentication

---

## 13. Open Questions

### Critical — Must Answer Before Schema
- **OQ-027 [RESOLVED]:** Club staff = email+password, no 2FA. Super Admin = email+password+mandatory 2FA. Password reset via SMS OTP. Credentials sent via SMS on account creation. See LD-052.
- **OQ-028 [RESOLVED]:** Time-based pricing: day_type (weekday/weekend/friday/specific) + time range (start_time→end_time) + price per duration. See LD-055.
- **OQ-029 [RESOLVED]:** Cancellation allowed anytime EXCEPT < 30 minutes before booking. Threshold configurable. On cancel → vacancy notification to nearby players. See LD-056.
- **OQ-030 [RESOLVED]:** Sanctum token = 30 days expiry. No refresh token flow. On expiry → player re-authenticates via OTP (quick). Dashboard = session-based via Inertia.
- **OQ-031 [RESOLVED]:** No multi-sport per venue (LD-054). Sport is auto-selected from venue.sport_category_id. Player never sees sport selector.

### Reviews
- **OQ-032 [RESOLVED]:** Reviews at club level. Player must have >= 1 completed booking at any venue of that club. One review per (player, club). See LD-057.

### Logout
- **OQ-033 [RESOLVED]:** Logout always sets fcm_token = NULL. See LD-058.



- **OQ-001 [RESOLVED]:** Will Venue Owners have their own login/portal, or does Admin manage all venues directly?
  → **RESOLVED:** Both. Club Owner has a scoped account. Admin has full override. See LD-010.
- **OQ-001b [RESOLVED]:** Does the Club Owner portal need to be a separate web app, or a restricted section of the same Admin Dashboard?
  → **RESOLVED:** Two separate dashboards. `/admin/*` for Super Admin. `/club/*` for Club staff (owner/admin/data_entry). Different layouts, different URL prefixes, different auth scopes. See LD-020.
- **OQ-002:** Is there a rating/review system for venues?
- **OQ-003:** Is cash payment supported in addition to electronic payment?
- **OQ-004:** What is the cancellation refund policy per provider?
- **OQ-005:** Will push notifications be required? If yes, which provider (FCM, etc.)?
- **OQ-006:** Is multi-city support needed from day one or is it single-city?
- **OQ-007:** What is the currency? SYP only, or multi-currency?
- **OQ-008:** Should the slot booking be per-hour only or flexible (e.g., 30-minute increments)?
- **OQ-009:** Account linking — if a user signs in with Google and later tries phone OTP with the same linked number, what happens?
- **OQ-010:** Will Admin users have different role levels (Super Admin vs Staff)?
- **OQ-011 [RESOLVED]:** Does a single venue support multiple sport categories?
  → **RESOLVED:** Yes. Multi-sport pivot model confirmed. See LD-009.
- **OQ-012 [RESOLVED]:** What is the "Event" tab in Bottom Navigation?
  → **RESOLVED:** Two sections: (1) Our Competitions — admin-managed content. (2) Today's Matches — from football-data.org free API. Display only, no user interaction. See LD-022.
- **OQ-013 [NEW — from Screen 01]:** Should the Home Screen use a single aggregated API endpoint or separate endpoints per section?
- **OQ-014 [NEW — from Screen 01]:** Is the greeting text ("Good morning") computed on mobile or returned from the API?
- **OQ-015 [RESOLVED]:** Does Admin need to approve a new Club before it goes live?
  → **RESOLVED:** Yes — manual approval required. Admin contacts club owner by phone before approving. See LD-021.
- **OQ-016 [RESOLVED]:** Can a Club Owner register themselves, or does Admin create their account manually?
  → **RESOLVED:** All accounts (Club Owner, Club Admin, Data Entry) are created exclusively by the Super Admin from the `/admin/` Dashboard. No self-registration for any club role. See LD-023.
- **OQ-017 [RESOLVED]:** Does the Club have its own pricing, or is pricing set per Venue?
  → **RESOLVED:** Pricing is per Venue, via time-based VenuePricingTiers. Each venue has its own price tiers. See LD-016.
- **OQ-018 [RESOLVED]:** What is the granularity of the geographic unit?
  → **RESOLVED:** Two-level: City (مدينة) → Area/Neighborhood (منطقة/حي). Example: دمشق → المزة. See LD-015.
- **OQ-019 [RESOLVED]:** Is "Rating" a feature in V1?
  → **RESOLVED:** Yes — Rating/Reviews are confirmed. Details (per Club or Venue, who can review) to be extracted from screens. See Screen 04 analysis.
- **OQ-020 [NEW — from Search model]:** Should search results rank/sort by relevance score, or just return all matches unranked?
- **OQ-021 [RESOLVED]:** Does History show completed only or also cancelled?
  → **RESOLVED:** Both completed + cancelled bookings appear in History tab. See LD-033.
- **OQ-022 [RESOLVED]:** What is the "Notify me" section in Booking Details?
  → **RESOLVED:** Removed from design. Notifications are automatic via Firebase FCM. See LD-031.
- **OQ-023 [RESOLVED]:** Are Facilities a fixed list or JSON?
  → **RESOLVED:** JSON — free-form per venue. Admin enters facilities freely per venue. See LD-034.
- **OQ-024 [RESOLVED]:** Does Write a Review include star rating?
  → **RESOLVED:** Yes — rating (stars) required, body optional (min 50 if provided), + anonymous mode toggle. See LD-030.
- **OQ-025 [RESOLVED]:** What is "My grounds" in Profile?
  → **RESOLVED:** Mixed list: Recent (from bookings, filtered by user's personal rating >= 3.0) + Saved (manual, always shown). See LD-035.
- **OQ-026 [RESOLVED]:** Is phone number required for Google Sign-In users during Profile Completion?
  → **RESOLVED:** Optional at registration. Required only at booking time. Backend returns PHONE_REQUIRED error if user tries to book without phone. See LD-036.
  → **RESOLVED:** Mixed list of Recent (from bookings, filtered by avg_rating >= 3.0) + Saved (manual favourites, always shown). Each item carries type flag. See LD-035 and Screen 12.

---

## 14. Risks and Edge Cases

- **R-001:** SMS provider dependency — if telecom API is not finalized, OTP auth is blocked. Mitigation: Abstract SMS sender behind interface, use mock for development.
- **R-002:** Double-booking race condition — two users booking the same slot simultaneously. Mitigation: Database-level locking or optimistic concurrency on slot reservation.
- **R-003:** Payment provider failures — provider is down or callback never arrives. Mitigation: Webhook retry mechanism, manual reconciliation tools in Admin.
- **R-004:** Unpaid booking slot lock — if user starts booking but doesn't pay, slot is held. Mitigation: Configurable hold timeout + automatic release job.
- **R-005:** Multi-language content gaps — if Admin only fills Arabic, English response may be empty. Mitigation: Fallback logic (use AR if EN is empty).
- **R-006:** Google token expiry or revocation mid-session. Mitigation: Token refresh flow, clear session on invalid token.
- **R-007:** User has no region set — Club listing returns nothing and user sees empty screen. Mitigation: Force region selection during onboarding; never skip it.
- **R-008:** Full-text search across 4 tables can be slow at scale. Mitigation: Use database full-text indexes from day one; plan for Meilisearch/Algolia integration if needed.
- **R-009:** Price filter applies at Venue level but Clubs are the display unit — a Club may have venues in different price ranges. Mitigation: Store `price_from` (min venue price) as a computed/denormalized field on Club for fast filtering.
- **R-010:** football-data.org rate limit (10 req/min on free tier) — if many users open Events tab simultaneously, direct calls would exceed limit. Mitigation: Backend cache layer (5-min TTL) ensures only 1 call per 5 minutes regardless of user count.
- **R-011:** football-data.org free tier covers 12 competitions only — if we need more leagues in the future, paid plan required. Mitigation: Document this constraint clearly; design the proxy layer to support API key swap without code changes.
- **R-013:** App Startup endpoint is the most critical endpoint — if it goes down, ALL mobile users see a broken splash screen. Mitigation: Dedicated health check, minimal DB dependency (2 rows only), short cache layer, separate monitoring alert.
- **R-014:** If Admin accidentally sets `minimum_required_version` higher than the current store version, ALL users get a forced update loop with no available update. Mitigation: Dashboard validation — warn Admin if `minimum_required_version > latest_version`.
- **R-015:** If `active_environment = stage` is accidentally left on for production users, all mobile API calls go to staging server. Mitigation: Prominent red warning banner in Dashboard when stage mode is active.
- **R-016:** MTN `private.pem` key must be stored securely — if exposed, any request can be forged. Mitigation: Store in `storage/keys/` outside web root, never in version control, mounted via Docker secret.
- **R-017:** Fatora/SamaPay callback URL must be publicly accessible — if behind firewall, callbacks never arrive. Mitigation: Ensure callback endpoint is whitelisted and publicly reachable.
- **R-018:** MTN does not support OTP resend — if user misses OTP, must restart payment entirely. Mitigation: Make this clear in mobile UI ("لم تستلم الرمز؟ ابدأ من جديد").
- **R-023:** Wallet balance inconsistency — if WalletTransaction is created but `wallet.balance` column not updated (crash between operations). Mitigation: Always compute balance from WalletTransactions sum; `balance` column is a cache only. Add a `wallet:reconcile` command.
- **R-024:** Scheduled booking slot is reserved but player never pays — slots permanently blocked. Mitigation: `ExpireUnpaidScheduledBookings` scheduler job + configurable payment window.
- **R-025:** WhatsApp OTP via Baileys (unofficial) — account may be banned by Meta without notice. Baileys may break on WhatsApp protocol updates. Mitigation: WhatsApp is optional — SMS is always the fallback. System degrades gracefully if WhatsApp service goes down.
- **R-026:** Account linking via OTP could be exploited — attacker tries to link someone else's number to their Google account. Mitigation: OTP is sent to the phone owner — only the real owner can complete linking.
- **R-019:** `payment_meta` JSON stores sensitive data (phone, guid) — encrypt at rest or treat as sensitive. Mitigation: Laravel encryption on the `provider_meta` column.
- **R-020:** football-data.org down → matches section fails. Mitigation: Show last cached response with timestamp; competitions section stays independent.
- **R-021:** football-data.org free tier = 12 competitions only. Mitigation: Document this limit; cache aggressively.
- **R-022:** LIVE match status needs frequent refresh. Mitigation: Pull-to-refresh instead of WebSocket — avoids complexity.
- **R-012:** Club approval creates a manual bottleneck — if many clubs register simultaneously, admin is overwhelmed. Mitigation: Approval queue UI must be excellent; consider SMS notification to admin when new club is submitted.

---


---

## 16. Packages Analysis & Schema Impact

> تحليل كل package وتأثيرها على الـ Schema والمعمارية — Senior-based decisions.
> كل قرار مبني على الـ docs الرسمية وليس على assumptions.

---

### Package 01 — Laravel 13
- **Schema Impact:** None مباشر — لكن يتطلب PHP 8.2+
- **Senior Note:** Laravel 13 يأتي مع Sanctum مدمج للـ API auth — نستخدمه بدل Passport.

---

### Package 02 — spatie/laravel-permission
- **Schema Impact:** ينشئ 5 جداول تلقائياً:
  ```
  roles               (id, name, guard_name, created_at, updated_at)
  permissions         (id, name, guard_name, created_at, updated_at)
  model_has_roles     (role_id, model_type, model_id)
  model_has_permissions (permission_id, model_type, model_id)
  role_has_permissions (permission_id, role_id)
  ```
- **Multiple Guards:** نحن عندنا 3 guards منفصلة:
  - `api` — للاعب (User model)
  - `admin` — للـ Super Admin (AdminUser model)
  - `club` — لـ Club Staff (ClubStaffMember model)
- **Senior Note:** كل guard له `roles` و`permissions` منفصلة في نفس الجداول — الفصل يكون بـ `guard_name` column. هذا يعني نقدر نستخدم package واحد للثلاثة.

---

### Package 03 — spatie/laravel-translatable
- **Schema Impact:** مهم جداً — يغير طريقة تصميم كل الـ text columns.
  - بدل `name_ar` + `name_en` كـ separate columns
  - نستخدم `name` واحد من نوع `json`
  - يُخزَّن: `{"ar": "نادي الفيحاء", "en": "Al Fayha Club"}`
- **Migration example:**
  ```php
  $table->json('name');        // بدل name_ar + name_en
  $table->json('description'); // بدل description_ar + description_en
  ```
- **Querying:** `Club::where('name->ar', 'نادي الفيحاء')->get()` ✅ يشتغل مع MySQL JSON
- **Senior Note:** هذا يبسّط الـ Schema بشكل كبير — نحذف كل الـ `_ar` / `_en` duplicates. الـ API يرجع القيمة بناءً على `Accept-Language` header تلقائياً.
- **Affected Tables:** clubs, venues, sport_categories, cities, areas, competitions, content_pages, venue_pricing_tiers, payment_methods, app_platforms, app_environments

---

### Package 04 — spatie/laravel-medialibrary (v11)
- **Schema Impact:** ينشئ جدول واحد:
  ```
  media (
    id, model_type, model_id,
    uuid, collection_name, name, file_name,
    mime_type, disk, conversions_disk,
    size, manipulations, custom_properties,
    generated_conversions, responsive_images,
    order_column, created_at, updated_at
  )
  ```
- **Impact الكبير:** كل الـ image columns تُحذف من جداولنا وتُستبدل بـ Media Library:
  - ❌ `clubs.logo_url`, `clubs.cover_image_url`
  - ❌ `venues.primary_image_url`
  - ❌ `sport_categories.icon_url`, `sport_categories.image_url`
  - ❌ `users.avatar_url`
  - ❌ `competitions.image_url`
  - كل هذه تصير collections في `media` table
- **Collections per Model:**
  - Club: `logo`, `cover`, `gallery`
  - Venue: `cover`, `gallery`
  - SportCategory: `icon`, `image`
  - User: `avatar`
  - Competition: `image`
  - AppPlatform: ليس مطلوباً (URLs فقط)
- **Senior Note:** `InteractsWithMedia` trait على كل model + `registerMediaCollections()` method. الـ disk = `public` (local storage — LD-040).

---

### Package 05 — spatie/laravel-sluggable
- **Schema Impact:** نضيف `slug` column (unique, indexed) على:
  - `sport_categories` → slug مثل `football`, `basketball`
  - `clubs` → slug مثل `al-fayha-club`
  - `content_pages` → slug مثل `about`, `privacy`, `terms`
- **Migration:**
  ```php
  $table->string('slug')->unique()->index();
  ```
- **Senior Note:** Auto-generated من `name` field. مع translatable، نولّد الـ slug من اللغة الإنجليزية.

---

### Package 06 — spatie/laravel-data
- **Schema Impact:** لا جداول جديدة — لكن يؤثر على المعمارية.
- **الاستخدامات في مشروعنا:**
  1. **API Resources:** بدل Laravel Resources التقليدية — `ClubData`, `VenueData`, `BookingData`
  2. **Request Validation:** بدل Form Requests — strongly typed
  3. **Eloquent Casting:** لتخزين structured data في JSON columns:
     - `Payment.provider_meta` → `MtnPaymentMeta::class` / `SyriatelPaymentMeta::class`
     - `Venue.amenities` → `VenueAmenitiesData::class`
  4. **Settings casting** مع laravel-settings
- **Senior Note:** يحل محل API Resources + Form Requests + JSON casting في آن واحد. أقوى وأنظف.

---

### Package 07 — spatie/opening-hours
- **Schema Impact:** مهم جداً على تصميم جدول الـ availability.
- **الفكرة:** بدل `venue_availability_rules` table بـ rows منفصلة لكل يوم، نخزّن الـ opening hours كـ JSON على الـ venue مباشرة.
- **Migration:**
  ```php
  // على جدول venues
  $table->json('opening_hours')->nullable();
  // يُخزَّن:
  // {
  //   "monday": ["08:00-14:00", "16:00-22:00"],
  //   "tuesday": ["08:00-22:00"],
  //   "wednesday": [],
  //   "exceptions": {
  //     "2025-12-25": []
  //   }
  // }
  ```
- **Usage:**
  ```php
  $openingHours = OpeningHours::create($venue->opening_hours);
  $openingHours->isOpenAt(new DateTime('2025-06-15 14:00:00')); // true/false
  $openingHours->nextOpen(new DateTime('now')); // متى يفتح
  ```
- **Schema Change:** يحذف `venue_availability_rules` table كاملاً ويستبدله بـ JSON column على venues.
- **Senior Note:** هذا أفضل بكثير — بدل 7 rows لكل venue (يوم لكل row)، نخزّن كل شيء في JSON واحد. الـ opening-hours package يتعامل معه بشكل احترافي.
- **Exceptions support:** يدعم استثناءات لأيام محددة (إجازات، أعياد) — مفيد جداً.

---

### Package 08 — spatie/laravel-settings
- **Schema Impact:** ينشئ جدول واحد:
  ```
  settings (id, group, name, locked, payload, created_at, updated_at)
  ```
- **يستبدل:** `system_settings` table الذي كنا نخططه — package يديره بشكل أفضل.
- **الاستخدام:**
  ```php
  class OtpSettings extends Settings {
      public int $expiry_seconds = 120;
      public int $resend_cooldown = 60;
      public int $max_attempts = 5;
      public static function group(): string { return 'otp'; }
  }

  class BookingSettings extends Settings {
      public int $cancellation_window_hours = 2;
      public float $my_grounds_min_rating = 3.0;
      public static function group(): string { return 'booking'; }
  }

  class CommissionSettings extends Settings {
      public string $commission_type = 'added';         // 'added' | 'deducted'
      public string $commission_calculation = 'flat';   // 'flat' | 'percentage'
      public int $commission_value = 5000;              // SYP flat or percentage points
      public int $cancellation_commission_flat = 25000; // SYP kept on cancellation
      public static function group(): string { return 'commission'; }
  }
  ```
- **Senior Note:** أفضل بكثير من key-value table — strongly typed, grouped, cacheable.

---

### Package 09 — spatie/laravel-sortable
- **Schema Impact:** نضيف `order_column` (unsignedInteger) على:
  - `sport_categories` — ترتيب عرض الـ categories
  - `payment_methods` — ترتيب عرض طرق الدفع
  - `app_platforms` — ترتيب العرض
- **Migration:**
  ```php
  $table->unsignedInteger('order_column')->default(0);
  ```
- **Senior Note:** يضيف `Buildable` trait + `ordered()` scope تلقائياً.

---

### Package 10 — spatie/laravel-searchable
- **Schema Impact:** لا جداول جديدة.
- **الاستخدام:** يوفر unified search interface عبر multiple models:
  ```php
  $results = (new Search())
      ->registerModel(Club::class, ['name->ar', 'name->en'])
      ->registerModel(Venue::class, ['name->ar', 'name->en'])
      ->search($query);
  ```
- **Senior Note:** مفيد للـ Dashboard search — لكن للـ Mobile API سنستخدم custom MySQL FULLTEXT query لأن الـ searchable package محدود مع JSON columns + Arabic text. نستخدم الاثنين: searchable للـ Dashboard، custom query للـ Mobile.
- **⚠️ تحذير Senior:** `laravel-searchable` يعمل بـ LIKE queries — ليس FULLTEXT. مع JSON columns وعربي، الأداء محدود. للـ Mobile search ننشئ FULLTEXT indexes يدوياً.

---

### Package 11 — spatie/laravel-google-calendar
- **Schema Impact:** لا جداول جديدة.
- **الاستخدام المقترح:** مزامنة الحجوزات مع Google Calendar للاعب أو لأدمن النادي.
- **⚠️ Senior Assessment:** هذه Package تحتاج Google OAuth credentials لكل مستخدم — معقدة للإعداد ومتطلباتها كثيرة (OAuth consent screen، Google API credentials).
- **توصية:** اعتبرها **Phase 2** اختيارية. الـ Core booking system لا يحتاجها. إذا أردت "أضف للتقويم" بعد الحجز، يمكن توليد `.ics` file بدل Google Calendar API.
- **Schema Impact إذا استُخدمت:** `google_calendar_event_id` nullable column على `bookings`.

---

### Package 12 — spatie/simple-excel
- **Schema Impact:** لا جداول.
- **الاستخدام:** Export تقارير الحجوزات والإيرادات من Dashboard:
  ```php
  SimpleExcelWriter::create('bookings.xlsx')
      ->addRows($bookings->map(fn($b) => [
          'كود الحجز' => $b->booking_code,
          'النادي' => $b->venue->club->name,
          'التاريخ' => $b->booking_date,
          'المبلغ' => $b->total_price,
      ]));
  ```
- **Senior Note:** أبسط وأخف من Laravel Excel (maatwebsite). مناسب جداً لحجمنا.

---

### Google Maps Backend
- **الحاجة:** لا نحتاج Google Maps package على Backend.
- **السبب:**
  - Haversine formula في MySQL تكفي للـ distance calculation
  - Leaflet على Dashboard — لا backend dependency
  - Mobile team يستخدم Google Maps SDK مباشرة
- **إذا احتجنا geocoding** (تحويل عنوان نصي لإحداثيات): نستخدم `Google Maps Geocoding API` مباشرة عبر HTTP call بسيط — لا package مطلوب.
- **توصية Senior:** لا تضف package للـ maps على backend. أبقِ الـ backend بعيداً عن أي dependency خرائط.

---


---

### Package 13 — laravel/telescope (dev only)
- **Install:** `composer require laravel/telescope --dev`
- **Purpose:** Debug assistant — monitors requests, DB queries, jobs, exceptions, FCM calls, OTP sends, scheduled commands.
- **Schema Impact:** ينشئ `telescope_entries` + `telescope_entries_tags` tables — على local/staging فقط. لا تأثير على production schema.
- **Config:** `config/telescope.php` → تفعيل فقط إذا `APP_ENV=local`
- **Senior Note:** بدونها تشتغل عمياً. أول شيء تثبّته بعد Laravel.

---

### Package 14 — spatie/laravel-backup
- **Install:** `composer require spatie/laravel-backup`
- **Purpose:** Backup تلقائي للـ DB (mysqldump) + الـ files (storage/) على جدول زمني.
- **Schema Impact:** لا جداول — يشغّل mysqldump مباشرة.
- **Config:**
  ```php
  // config/backup.php
  'source' => [
      'databases' => ['mysql'],
      'files' => [ base_path() ],
  ],
  'destination' => [
      'disks' => ['local'], // local storage — LD-040
  ],
  ```
- **Schedule:** `$schedule->command('backup:run')->daily()->at('02:00');`
- **Senior Note:** Production بدون backup = مخاطرة غير مقبولة. يضاف من اليوم الأول.

---

### Package 15 — laravel/sanctum
- **Install:** مدمج في Laravel 13 — `php artisan sanctum:install`
- **Purpose:** Token-based API authentication للاعب (mobile) + session auth للـ Dashboard (Inertia).
- **Schema Impact:** `personal_access_tokens` table (auto-created).
- **Multiple Guards:**
  ```php
  // config/auth.php
  'guards' => [
      'api'   => ['driver' => 'sanctum', 'provider' => 'users'],
      'admin' => ['driver' => 'sanctum', 'provider' => 'admin_users'],
      'club'  => ['driver' => 'sanctum', 'provider' => 'club_staff'],
  ],
  ```
- **Token Abilities:** نستخدم abilities لتمييز الـ tokens:
  - `user-api` — للاعب
  - `admin-api` — للـ Super Admin
  - `club-api` — لـ Club Staff
- **Senior Note:** `tokenCan('admin-api')` في middleware يمنع الوصول غير المصرّح.

---

### Package 16 — spatie/laravel-query-builder
- **Install:** `composer require spatie/laravel-query-builder`
- **Purpose:** يحوّل API query params لـ Eloquent queries بشكل نظيف وآمن — بدل if/else يدوية.
- **Schema Impact:** لا جداول.
- **Usage في مشروعنا:**
  ```php
  // GET /api/v1/clubs?filter[area_id]=5&filter[sport]=football&sort=-avg_rating&include=venues
  $clubs = QueryBuilder::for(Club::class)
      ->allowedFilters([
          'name',
          AllowedFilter::exact('area_id'),
          AllowedFilter::scope('sport', 'whereSport'),
          AllowedFilter::range('price_from'),
          AllowedFilter::exact('avg_rating'),
      ])
      ->allowedSorts(['avg_rating', 'price_from', 'created_at'])
      ->allowedIncludes(['venues', 'area', 'city'])
      ->paginate();
  ```
- **Senior Note:** يوفر عليك كتابة عشرات الـ conditional query scopes. الـ Mobile API يستفيد منه مباشرة.

---

### Package 17 — spatie/laravel-activitylog
- **Install:** `composer require spatie/laravel-activitylog`
- **Purpose:** يسجّل كل تغيير على الـ models تلقائياً — من غيّر، متى، ماذا تغيّر (قديم → جديد).
- **Schema Impact:** ينشئ `activity_log` table:
  ```
  activity_log (
    id, log_name, description,
    subject_type, subject_id,
    causer_type, causer_id,
    properties (json — old/new values),
    created_at, updated_at
  )
  ```
- **Usage في مشروعنا:**
  ```php
  // على Club model:
  use Spatie\Activitylog\Traits\LogsActivity;
  use Spatie\Activitylog\LogOptions;

  class Club extends Model {
      use LogsActivity;
      public function getActivitylogOptions(): LogOptions {
          return LogOptions::defaults()
              ->logOnly(['name', 'status', 'is_featured'])
              ->logOnlyDirty()
              ->dontSubmitEmptyLogs();
      }
  }
  ```
- **Dashboard Use:** Admin يشوف history كل تغيير على Club/Venue/Booking — من غيّر الحالة ومتى.
- **Causers:** `causer` = AdminUser أو ClubStaffMember — يُعرف من الـ authenticated user.
- **Models to log:** Club, Venue, Booking, Payment, SportCategory, PaymentMethod, AppPlatform
- **Senior Note:** لا تـ log كل شيء — فقط الـ fields المهمة. `logOnlyDirty()` ضروري لتجنب spam.

---

### Package 18 — propaganistas/laravel-phone
- **Install:** `composer require propaganistas/laravel-phone`
- **Purpose:** Validation rule قوية لأرقام الهاتف مع دعم country codes.
- **Schema Impact:** لا جداول.
- **Usage:**
  ```php
  // في Request validation:
  'phone_number' => ['required', 'phone:SY'],      // سوري فقط
  'phone_number' => ['required', 'phone:SY,LB,JO'], // سوري أو لبناني أو أردني

  // Normalization:
  phone('+963912345678', 'SY')->formatE164(); // "+963912345678"
  ```
- **Senior Note:** يمنع أرقام وهمية من الوصول لخدمة OTP — خط دفاع مهم.

---

### Package 19 — spatie/laravel-model-states
- **Install:** `composer require spatie/laravel-model-states`
- **Purpose:** State machine للـ Booking وPayment — يمنع الانتقالات غير الصحيحة بشكل صريح.
- **Schema Impact:** لا جداول — يعمل على columns الـ status الموجودة.
- **Usage:**
  ```php
  // States تُعرَّف كـ classes:
  class Confirmed extends BookingState {}
  class Cancelled extends BookingState {}
  class Completed extends BookingState {}

  // Transitions:
  Confirmed::class => [Cancelled::class],   // مسموح
  Completed::class => [],                    // لا يمكن التغيير
  // Cancelled → Confirmed = exception تلقائي ✅

  // في الكود:
  $booking->status->transitionTo(Cancelled::class);
  ```
- **Booking states:** `confirmed`, `cancelled`, `completed`, `no_show`, `failed`
- **Payment states:** `pending`, `processing`, `completed`, `failed`, `refunded`
- **Senior Note:** بدون state machine، ممكن يحصل `cancelled → confirmed` بالخطأ. هذا يمنعه بشكل معماري.

## ملخص تأثير الـ Packages على Schema

### جداول تُضاف تلقائياً بواسطة الـ packages:
| Package | الجداول المضافة | Environment |
|---|---|---|
| laravel/sanctum | personal_access_tokens | production |
| laravel-permission | roles, permissions, model_has_roles, model_has_permissions, role_has_permissions | production |
| laravel-medialibrary | media | production |
| laravel-settings | settings | production |
| laravel-activitylog | activity_log | production |
| Laravel Queue | jobs, failed_jobs, job_batches | production |
| laravel/telescope | telescope_entries, telescope_entries_tags | local/staging only |

### تغييرات على الـ Schema بسبب الـ packages:

**spatie/laravel-translatable:**
- كل `name_ar` + `name_en` → `name` (json)
- كل `description_ar` + `description_en` → `description` (json)
- كل `title_ar` + `title_en` → `title` (json)
- كل `body_ar` + `body_en` → `body` (json)

**spatie/laravel-medialibrary:**
- حذف: logo_url, cover_image_url, icon_url, image_url, avatar_url, primary_image_url
- كلها تنتقل لـ `media` table

**spatie/opening-hours:**
- حذف: `venue_availability_rules` table كاملاً
- إضافة: `venues.opening_hours` (json column)

**spatie/laravel-settings:**
- حذف: `system_settings` table (الذي كنا نخططه)
- إضافة: `settings` table (تُديره الـ package)

**spatie/laravel-sluggable:**
- إضافة: `slug` column على sport_categories, clubs, content_pages

**spatie/laravel-sortable:**
- إضافة: `order_column` على sport_categories, payment_methods, app_platforms

**spatie/laravel-model-states:**
- لا جداول — يضيف state machine على `bookings.status` و `payments.status`
- يحمي من الانتقالات غير الصحيحة بشكل معماري

**propaganistas/laravel-phone:**
- لا جداول — validation rule فقط على حقول phone_number

**spatie/laravel-query-builder:**
- لا جداول — يحوّل query params لـ Eloquent queries

**spatie/laravel-activitylog:**
- إضافة: `activity_log` table على كل الـ models المهمة

### القائمة الكاملة للـ Packages (النهائية)
```
# Core
laravel/sanctum
spatie/laravel-permission
spatie/laravel-data

# Content & Media
spatie/laravel-translatable
spatie/laravel-medialibrary
spatie/laravel-sluggable

# Business Logic
spatie/laravel-settings
spatie/opening-hours
spatie/laravel-sortable
spatie/laravel-model-states
spatie/laravel-query-builder

# Monitoring & Ops
spatie/laravel-activitylog
spatie/laravel-backup
spatie/simple-excel

# Utilities
propaganistas/laravel-phone
spatie/laravel-searchable

# Dev Only
laravel/telescope --dev

# Deferred (Phase 2)
spatie/laravel-google-calendar
```

## 15. Next Recommended Step

**قرارات لا تزال مطلوبة:**

1. **OQ-021** — History tab: completed فقط أم completed + cancelled؟
2. **OQ-022** — ما وظيفة "Notify me" في Booking Details؟
3. **OQ-023** — Facilities: قائمة ثابتة من الأدمن أم free-text لكل ملعب؟
4. **DB Choice** — MySQL أم PostgreSQL؟ (PostgreSQL أفضل للـ geospatial queries مع PostGIS)
5. **Cache/Queue** — Redis مؤكد؟ (مطلوب للـ jobs: unpaid booking expiry، SMS queue، football API cache)

**الشاشات المتبقية:**
- Booking Flow: Slot Selection + Confirmation + Payment — لم تُحلَّل بعد
- Settings Screen — لم تُحلَّل بعد
- Event Screen (Tab) — لم تُحلَّل بعد

**الخطوات التقنية التالية:**

1. **ارفع ملفات SMS وملفات الدفع** على Project Knowledge
2. أجب على **DB + Redis** (MySQL/MariaDB + Redis مؤكد؟)
3. بعد تحليل باقي الشاشات: **نرسم Database Schema الكامل**
4. بعد Schema: **Docker Compose + Laravel init + Spatie + Firebase setup**
