# Database Schema — Production Design
> Sports Venue Booking Platform
> Designed by: Senior DB Architect Review
> MySQL 8.0+ | utf8mb4_unicode_ci | InnoDB

---

## Migration Order (Dependency-safe)

```
1.  countries            [seeded from dr5hn — لا تعديل يدوي]
2.  states               [seeded from dr5hn — محافظات]
3.  cities               [seeded from dr5hn — مدن/مناطق]
22.  users
22.  social_identities
23.  otp_challenges
22.  sport_categories           [Events tab only]
23.  venue_categories           [NEW — generic bookable space categories]
22.  clubs
23.  club_user
10. venues
11. venue_pricing_tiers
12. commission_configs         [NEW — platform commission rules]
13. bookings
14. slot_reservations
15. payments
16. wallets
17. wallet_transactions
18. reviews
19. saved_venues
20. payment_methods
21. settlements                [NEW — platform pays club]
22. settlement_items           [NEW — booking-level settlement line items]
23. app_platforms
24. app_environments
25. content_pages
26. competitions
27. personal_access_tokens    [Sanctum]
28. jobs / failed_jobs        [Queue]
29. media                     [Medialibrary]
30. settings                  [Laravel-settings]
31. activity_log              [Activitylog]
32. roles/permissions/*       [Spatie permissions]
```

---

## ⚠️ One Open Question Before First Migration

**✅ All questions resolved.** `club_user` pivot replaces `users.club_id` — dynamic multi-club assignment from Admin Dashboard (LD-060).

---

## Tables

---

### `countries`
> مصدر البيانات: dr5hn/countries-states-cities-database (ODbL) — seeded فقط، لا يُعدَّل يدوياً
> الأدمن يُفعّل/يوقف فقط — لا إضافة أو تعديل للبيانات الجغرافية يدوياً
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
                -- يُحتفظ بنفس id من مصدر dr5hn لتسهيل إعادة seeding
iso2            CHAR(2) NOT NULL UNIQUE      -- 'SY', 'LB', 'JO'
iso3            CHAR(3) NOT NULL UNIQUE      -- 'SYR', 'LBN', 'JOR'
name            VARCHAR(150) NOT NULL        -- 'Syria' (EN — المصدر إنجليزي)
name_ar         VARCHAR(150) NULL            -- 'سوريا' (يُضاف يدوياً للدول المفعّلة)
phone_code      VARCHAR(10) NOT NULL         -- '+963'
capital         VARCHAR(150) NULL
currency        VARCHAR(10) NULL             -- 'SYP'
latitude        DECIMAL(10,8) NULL
longitude       DECIMAL(11,8) NULL
is_active       TINYINT(1) NOT NULL DEFAULT 0  -- Admin يُفعّل الدول التي يريدها
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:** UNIQUE `iso2`, UNIQUE `iso3`, `is_active`
**Notes:**
- Seeded من dr5hn SQL dump — لا يُعدَّل يدوياً
- Admin يُفعّل سوريا (وأي دولة مستقبلاً) من Dashboard
- `phone_code` يُستخدم في Mobile لعرض country code selector

---

### `states`
> = المحافظات / الولايات (المستوى الثاني) — مصدر: dr5hn
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
country_id      BIGINT UNSIGNED NOT NULL FK → countries(id) RESTRICT
name            VARCHAR(150) NOT NULL        -- 'Damascus', 'Aleppo'
name_ar         VARCHAR(150) NULL            -- 'دمشق', 'حلب'
state_code      VARCHAR(20) NULL             -- 'DM', 'AL'
latitude        DECIMAL(10,8) NULL
longitude       DECIMAL(11,8) NULL
is_active       TINYINT(1) NOT NULL DEFAULT 0  -- Admin يُفعّل المحافظات المطلوبة
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:** `country_id`, `is_active`, `(country_id, is_active)`
**Notes:** Seeded من dr5hn. Admin يختار المحافظات النشطة فقط.

---

### `cities`
> = المدن والمناطق (المستوى الثالث) — مصدر: dr5hn
> يحلّ محلّ جدول `areas` القديم
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
state_id        BIGINT UNSIGNED NOT NULL FK → states(id) RESTRICT
name            VARCHAR(150) NOT NULL        -- 'Mazzeh', 'Malki'
name_ar         VARCHAR(150) NULL            -- 'المزة', 'المالكي'
latitude        DECIMAL(10,8) NULL
longitude       DECIMAL(11,8) NULL
is_active       TINYINT(1) NOT NULL DEFAULT 0  -- Admin يُفعّل المدن/المناطق المطلوبة
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:** `state_id`, `is_active`, `(state_id, is_active)`
**Notes:**
- Seeded من dr5hn — 153,765+ مدينة
- لسوريا: Admin يُفعّل المدن والأحياء الفعلية الخاصة بالمشروع
- `clubs.city_id` يشير لهذا الجدول (المدينة/المنطقة الدقيقة)

---

### `users`
```sql
id                          BIGINT UNSIGNED PK AUTO_INCREMENT
name                        VARCHAR(255) NULL              -- nullable until profile complete
email                       VARCHAR(255) NULL UNIQUE
phone_number                VARCHAR(20) NULL UNIQUE        -- E.164 format, nullable for Google users
phone_verified_at           TIMESTAMP NULL
country_code                VARCHAR(10) NULL DEFAULT '+963'
firebase_uid                VARCHAR(128) NULL UNIQUE       -- Google Sign-In
firebase_provider           VARCHAR(50) NULL               -- 'google.com', 'apple.com'
password                    VARCHAR(255) NULL              -- nullable for players (OTP only); set for dashboard users
-- club_id REMOVED — replaced by club_user pivot table (dynamic multi-club assignment)
default_city_id             BIGINT UNSIGNED NULL FK → world_cities(id) SET NULL
default_area_id             BIGINT UNSIGNED NULL FK → world_states(id) SET NULL
fcm_token                   TEXT NULL                      -- cleared on logout (LD-058)
fcm_platform                ENUM('mobile','web') NULL      -- for notification formatting
account_status              ENUM('active','blocked','suspended','pending_profile_completion') NOT NULL DEFAULT 'pending_profile_completion'
onboarding_completed_at     TIMESTAMP NULL
notifications_push_enabled  TINYINT(1) NOT NULL DEFAULT 1
notifications_sms_enabled   TINYINT(1) NOT NULL DEFAULT 1
notifications_reminders_enabled TINYINT(1) NOT NULL DEFAULT 1
preferred_language          ENUM('ar','en') NOT NULL DEFAULT 'ar'
google2fa_secret            VARCHAR(255) NULL              -- encrypted TOTP secret, Base32 encoded, 20 random bytes → 32 chars. Custom TotpService. Super admin only.
google2fa_enabled_at        TIMESTAMP NULL                 -- when 2FA was activated
last_login_at               TIMESTAMP NULL
deleted_at                  TIMESTAMP NULL                  -- soft delete for account deactivation
created_at                  TIMESTAMP NULL
updated_at                  TIMESTAMP NULL
```
**Indexes:**
- UNIQUE: `email`, `phone_number`, `firebase_uid`
- `account_status`
- `default_city_id`
- `(account_status, default_city_id)` — listing players by city
- `deleted_at` (partial — for soft delete queries)
**Notes:**
- Players: password=null, club_id=null
- Club staff: password set, club assignment via club_user pivot
- Super admin: password set, google2fa_secret set, club_id=null
- DO NOT use separate admin/club tables (LD-051)
- `phone_number` UNIQUE constraint — one account per phone
- Club assignment is via `club_user` pivot — a staff member can be assigned to multiple clubs dynamically from Admin Dashboard.

---

### `social_identities`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
user_id         BIGINT UNSIGNED NOT NULL FK → users(id) CASCADE
provider        VARCHAR(50) NOT NULL    -- 'google', 'apple'
provider_uid    VARCHAR(255) NOT NULL
provider_email  VARCHAR(255) NULL
provider_meta   JSON NULL               -- raw claims snapshot
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:** UNIQUE `(provider, provider_uid)`, `user_id`
**Notes:** One user can have multiple social identities (Google + Apple). Used for account linking (LD-047).

---

### `otp_challenges`
```sql
id                      BIGINT UNSIGNED PK AUTO_INCREMENT
uuid                    CHAR(36) NOT NULL UNIQUE   -- used as verification_request_id externally (non-guessable)
phone_number            VARCHAR(20) NOT NULL
code_hash               VARCHAR(255) NOT NULL   -- sha256 hash, never plain text
channel                 ENUM('sms','whatsapp') NOT NULL DEFAULT 'sms'
expires_at              TIMESTAMP NOT NULL
consumed_at             TIMESTAMP NULL
attempts_count          TINYINT UNSIGNED NOT NULL DEFAULT 0
resend_count            TINYINT UNSIGNED NOT NULL DEFAULT 0
delivery_status         ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending'
ip_address              VARCHAR(45) NULL         -- for rate limiting audit
created_at              TIMESTAMP NULL
```
**Indexes:** UNIQUE `uuid`, `phone_number`, `expires_at`, `(phone_number, consumed_at, expires_at)`
**Notes:**
- No `updated_at` — append-only audit trail
- No soft delete — let records expire naturally
- Add a scheduled cleanup: delete consumed/expired > 24h
- `uuid` is the `verification_request_id` returned to mobile — non-sequential, non-guessable

---

### `venue_categories`
> Single canonical definition. Generic category for any bookable space. (LD-063)
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            JSON NOT NULL           -- {"ar":"ملاعب رياضية","en":"Sports Venues"}
slug            VARCHAR(100) NOT NULL UNIQUE
type            ENUM('sports','hall','court','outdoor','other') NOT NULL DEFAULT 'sports'
is_active       TINYINT(1) NOT NULL DEFAULT 1
order_column    INT UNSIGNED NOT NULL DEFAULT 0  -- [spatie/sortable]
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:** UNIQUE `slug`, `is_active`, `type`, `order_column`
**Notes:**
- Replaces `sport_categories` as the venue categorization system (LD-063)
- `sport_categories` still exists but ONLY for the Events tab (football matches display)
- Admin adds any category: "صالات أفراح", "ملاعب كرة قدم", "قاعات مؤتمرات"
- `type = sports` → booking UI shows sport context
- `type != sports` → booking UI shows generic context (no sport references)
**Media collections:** `icon`, `cover` — via spatie/medialibrary

---

### `clubs`
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
owner_id            BIGINT UNSIGNED NULL FK → users(id) SET NULL  -- primary owner (display/contact only) — access via club_user pivot
city_id             BIGINT UNSIGNED NOT NULL FK → cities(id) RESTRICT
                         -- cities = المناطق/المدن من مصدر dr5hn
name                JSON NOT NULL       -- {"ar":"نادي الفيحاء","en":"Al Fayha Club"}
slug                VARCHAR(255) NOT NULL UNIQUE   -- [spatie/sluggable]
description         JSON NULL
address             VARCHAR(500) NULL
phone_number        VARCHAR(20) NULL
latitude            DECIMAL(10,8) NOT NULL          -- required per GA-014
longitude           DECIMAL(11,8) NOT NULL
amenities           JSON NULL           -- club-level: ["Parking","CCTV","Waiting Room"]
status              ENUM('pending_approval','active','inactive','suspended','rejected') NOT NULL DEFAULT 'pending_approval'
rejection_reason    TEXT NULL
is_featured         TINYINT(1) NOT NULL DEFAULT 0   -- appears in "Popular Grounds"
avg_rating          DECIMAL(3,2) NULL               -- CACHED from reviews, updated by Observer
reviews_count       INT UNSIGNED NOT NULL DEFAULT 0  -- CACHED
price_from          INT UNSIGNED NULL               -- CACHED from venue_pricing_tiers (SYP)
approved_at         TIMESTAMP NULL
approved_by         BIGINT UNSIGNED NULL FK → users(id) SET NULL
deleted_at          TIMESTAMP NULL                  -- soft delete
created_at          TIMESTAMP NULL
updated_at          TIMESTAMP NULL
```
**Indexes:**
- UNIQUE `slug`
- `city_id`, `owner_id`, `status`
- `is_featured`
- `avg_rating` — for sort by rating filter
- `price_from` — for price range filter
- `(city_id, status, is_featured)` — home screen popular query
- `(latitude, longitude)` — for Haversine distance queries
- `deleted_at`
**Media collections:** `logo`, `cover`, `gallery` — via spatie/medialibrary

---

### `club_user`
```sql
id          BIGINT UNSIGNED PK AUTO_INCREMENT
club_id     BIGINT UNSIGNED NOT NULL FK → clubs(id) CASCADE
user_id     BIGINT UNSIGNED NOT NULL FK → users(id) CASCADE
created_at  TIMESTAMP NULL
updated_at  TIMESTAMP NULL
```
**Indexes:** UNIQUE `(club_id, user_id)`, `user_id`
**Notes:**
- Replaces `users.club_id` single FK — allows dynamic multi-club assignment (LD-060)
- Super Admin assigns/removes staff from clubs via Admin Dashboard
- Query "which clubs does this staff member manage?": `SELECT club_id FROM club_user WHERE user_id = ?`
- Query "who are the staff of this club?": `SELECT user_id FROM club_user WHERE club_id = ?`
- Spatie permission check still applies — role scoped per guard, club scoping via this pivot
- When sending FCM to club admins: `JOIN club_user ON users.id = club_user.user_id WHERE club_user.club_id = ?`

---

### `venues`
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
club_id             BIGINT UNSIGNED NOT NULL FK → clubs(id) CASCADE
category_id         BIGINT UNSIGNED NULL FK → venue_categories(id) SET NULL  -- nullable; generic category (LD-063)
name                JSON NOT NULL       -- {"ar":"الملعب الرئيسي","en":"Main Ground"}
description         JSON NULL
size                VARCHAR(50) NULL    -- "7×7", "5×5", "full"
amenities           JSON NULL           -- venue-level: ["Lighting","Shaded","Changing Room"]
opening_hours       JSON NULL           -- spatie/opening-hours format
latitude            DECIMAL(10,8) NULL  -- optional override of club location
longitude           DECIMAL(11,8) NULL
avg_rating          DECIMAL(3,2) NULL   -- CACHED — venue quality display only
reviews_count       INT UNSIGNED NOT NULL DEFAULT 0
price_from          INT UNSIGNED NULL   -- CACHED from venue_pricing_tiers
status              ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active'
order_column        INT UNSIGNED NOT NULL DEFAULT 0  -- display order within club
deleted_at          TIMESTAMP NULL
created_at          TIMESTAMP NULL
updated_at          TIMESTAMP NULL
```
**Indexes:**
- `club_id`, `category_id`, `status`
- `(club_id, status, order_column)` — venue listing within a club
- `deleted_at`
**Media collections:** `cover`, `gallery` — via spatie/medialibrary
**Notes:**
- `category_id` FK → venue_categories (not sport_categories). sport_categories is ONLY for Events tab.
- No `venue_availability_rules` table — `opening_hours` JSON handles it (spatie/opening-hours)
- No `venue_allowed_durations` table — derived from DISTINCT duration_minutes in venue_pricing_tiers

---

### `venue_pricing_tiers`
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
venue_id            BIGINT UNSIGNED NOT NULL FK → venues(id) CASCADE
name                JSON NOT NULL       -- {"ar":"فترة صباحية","en":"Morning Rate"}
day_type            ENUM('all_days','weekday','weekend','friday','specific_day') NOT NULL DEFAULT 'all_days'
specific_day        ENUM('saturday','sunday','monday','tuesday','wednesday','thursday','friday') NULL
start_time          TIME NOT NULL       -- "08:00:00"
end_time            TIME NOT NULL       -- "14:00:00"
duration_minutes    SMALLINT UNSIGNED NOT NULL  -- 45, 60, 90, 120
price               INT UNSIGNED NOT NULL        -- SYP, no decimals
is_active           TINYINT(1) NOT NULL DEFAULT 1
order_column        INT UNSIGNED NOT NULL DEFAULT 0  -- [spatie/sortable]
created_at          TIMESTAMP NULL
updated_at          TIMESTAMP NULL
```
**Indexes:**
- `venue_id`, `is_active`
- `(venue_id, is_active, day_type, start_time, end_time)` — slot price resolution query
- `(venue_id, is_active, duration_minutes)` — allowed durations query
**Constraints:**
- `end_time > start_time` — CHECK constraint
- No overlapping tiers per venue+day_type+duration — enforced at application layer (not DB-level due to complexity)
**Syria Calendar Note:**
- `weekday` = Sunday through Thursday (الأحد → الخميس)
- `weekend` = Friday + Saturday (الجمعة + السبت)
- `friday` = Friday only (special pricing common in Syria)
- This must be documented in `TierMatchingService` code explicitly
**Notes:**
- `duration_minutes` replaces the removed `VenueAllowedDuration` entity
- Allowed durations = `SELECT DISTINCT duration_minutes FROM venue_pricing_tiers WHERE venue_id=? AND is_active=1`
- `price` stored as INT (SYP has no decimals in practice for venue booking context)

---

### `commission_configs`
> Platform commission rules — configurable per global/club/venue scope (LD-061)
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
scope               ENUM('global','club','venue') NOT NULL DEFAULT 'global'
club_id             BIGINT UNSIGNED NULL FK → clubs(id) CASCADE
venue_id            BIGINT UNSIGNED NULL FK → venues(id) CASCADE
commission_type     ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed'
commission_value    INT UNSIGNED NOT NULL DEFAULT 0
                    -- fixed: SYP amount (e.g. 5000 = 5,000 SYP)
                    -- percentage: basis points (e.g. 500 = 5.00%)
-- apply_as: REMOVED. Commission is ALWAYS deducted from club. Player always pays venue_price.
cancellation_fee    INT UNSIGNED NOT NULL DEFAULT 0
                    -- fixed SYP amount platform keeps when booking is cancelled (not refunded to player)
is_active           TINYINT(1) NOT NULL DEFAULT 1
effective_from      DATE NOT NULL DEFAULT (CURRENT_DATE)
note                TEXT NULL
created_by          BIGINT UNSIGNED NULL FK → users(id) SET NULL
created_at          TIMESTAMP NULL
updated_at          TIMESTAMP NULL
```
**Indexes:**
- `scope`, `is_active`, `effective_from`
- `club_id`, `venue_id`
- `(scope, venue_id, is_active)` — specific config lookup
**Resolution (most specific wins):**
```
1. WHERE scope='venue' AND venue_id=X AND is_active=1 ORDER BY effective_from DESC LIMIT 1
2. WHERE scope='club'  AND club_id=X  AND is_active=1 ORDER BY effective_from DESC LIMIT 1
3. WHERE scope='global'               AND is_active=1 ORDER BY effective_from DESC LIMIT 1
```
**Notes:**
- Snapshot is copied to `bookings.*` at booking creation — config changes don't affect past bookings
- `cancellation_fee` is separate from `cancellation_deduction` in BookingSettings
  - `cancellation_deduction` = total amount deducted from player's refund (goes to platform)
  - `cancellation_fee` here = subset of that deduction that is platform's commission on the cancelled booking

---

### `bookings`
```sql
id                      BIGINT UNSIGNED PK AUTO_INCREMENT
user_id                 BIGINT UNSIGNED NULL FK → users(id) SET NULL  -- nullable: manual bookings may have no user
venue_id                BIGINT UNSIGNED NOT NULL FK → venues(id) RESTRICT
sport_category_id       BIGINT UNSIGNED NULL FK → sport_categories(id) SET NULL  -- NULL for non-sports bookings (LD-063). Only set when venue.category.type = sports
booking_code            VARCHAR(20) NOT NULL UNIQUE    -- e.g. "SP001234"
source                  ENUM('mobile','manual') NOT NULL DEFAULT 'mobile'
manual_type             ENUM('external','blocked') NULL   -- only if source=manual
manual_note             TEXT NULL
status                  ENUM('confirmed','scheduled','cancelled','completed','no_show','failed') NOT NULL
booking_date                DATE NOT NULL
start_time                  TIME NOT NULL
end_time                    TIME NOT NULL
starts_at                   DATETIME NOT NULL
ends_at                     DATETIME NOT NULL
duration_minutes            SMALLINT UNSIGNED NOT NULL
venue_price                 INT UNSIGNED NOT NULL DEFAULT 0  -- original venue price snapshot (from pricing tier)
commission_amount           INT UNSIGNED NOT NULL DEFAULT 0  -- platform commission snapshot
commission_type             ENUM('fixed','percentage') NULL  -- snapshot: type of commission calc (fixed SYP or percentage)
total_price                 INT UNSIGNED NOT NULL DEFAULT 0  -- what player actually paid
club_payout_amount          INT UNSIGNED NOT NULL DEFAULT 0  -- what club is owed
cancellation_commission     INT UNSIGNED NOT NULL DEFAULT 0  -- platform keeps this on cancellation
currency                    VARCHAR(3) NOT NULL DEFAULT 'SYP'
notes                   TEXT NULL
cancelled_at            TIMESTAMP NULL
cancellation_reason     TEXT NULL
cancelled_by            BIGINT UNSIGNED NULL FK → users(id) SET NULL
is_recurring            TINYINT(1) NOT NULL DEFAULT 0
recurrence_pattern      JSON NULL           -- {"frequency":"weekly","day_of_week":5,"occurrences":8}
recurrence_parent_id    BIGINT UNSIGNED NULL FK → bookings(id) SET NULL
reminder_2h_sent_at     TIMESTAMP NULL
reminder_1h_sent_at     TIMESTAMP NULL
deposit_amount           INT UNSIGNED NOT NULL DEFAULT 0
                         -- 0 = full payment. >0 = amount paid as deposit.
deposit_status           ENUM('none','paid') NOT NULL DEFAULT 'none'
remaining_amount         INT UNSIGNED NOT NULL DEFAULT 0
                         -- amount player owes at venue upon arrival
remaining_status         ENUM('none','due_on_arrival','confirmed','waived') NOT NULL DEFAULT 'none'
remaining_confirmed_at   TIMESTAMP NULL
remaining_confirmed_by   BIGINT UNSIGNED NULL FK → users(id) SET NULL
reviewed_at              TIMESTAMP NULL     -- set when player submits a review for this booking
created_at              TIMESTAMP NULL
updated_at              TIMESTAMP NULL
```
**Indexes:**
- UNIQUE `booking_code`
- `user_id`, `venue_id`, `status`
- `booking_date` — scheduler queries
- `starts_at` — cancellation window check: `starts_at - INTERVAL 30 MINUTE > NOW()`
- `(venue_id, booking_date, status)` — slot availability check — CRITICAL INDEX
- `(venue_id, booking_date, start_time, end_time, status)` — slot overlap check
- `(status, booking_date, reminder_2h_sent_at)` — reminder scheduler query
- `recurrence_parent_id`
- `source` — filter manual vs mobile
**Notes:**
- No soft delete — status='cancelled' serves that purpose
- `user_id` SET NULL (not RESTRICT) — in case user is soft-deleted
- `total_price` = what player pays. `club_payout_amount` = what club receives. `commission_amount` = platform revenue.
- All three are snapshots at booking creation — never recalculated
- `commission_type` ('fixed'|'percentage') is snapshot of the commission calculation method at booking time (audit trail)
- For manual/blocked bookings: all amounts = 0
- CRITICAL: slot overlap detection must be enforced at DB level with a transaction

---

### `slot_reservations`
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
venue_id            BIGINT UNSIGNED NOT NULL FK → venues(id) CASCADE
user_id             BIGINT UNSIGNED NOT NULL FK → users(id) CASCADE
booking_date        DATE NOT NULL
start_time          TIME NOT NULL
end_time            TIME NOT NULL
duration_minutes    SMALLINT UNSIGNED NOT NULL
category_id         BIGINT UNSIGNED NULL FK → venue_categories(id) SET NULL
reserved_until      TIMESTAMP NOT NULL          -- NOW() + 10 minutes
created_at          TIMESTAMP NULL
```
**Indexes:**
- UNIQUE `(venue_id, booking_date, start_time)` — prevents duplicate slot reservations at DB level
- `(venue_id, booking_date, reserved_until)` — availability check excludes active reservations
- `reserved_until` — scheduler cleanup
- `user_id` — one active reservation per user check
**Notes:**
- No `updated_at` — created once, deleted on success/expiry
- Scheduler: `DELETE FROM slot_reservations WHERE reserved_until < NOW()` — every minute
- Application enforces: one active reservation per user at a time
- UNIQUE `(venue_id, booking_date, start_time)` — DB-level protection against concurrent reservations (LD-044)

---

### `payments`
```sql
id                      BIGINT UNSIGNED PK AUTO_INCREMENT
booking_id              BIGINT UNSIGNED NOT NULL FK → bookings(id) RESTRICT
user_id                 BIGINT UNSIGNED NOT NULL FK → users(id) RESTRICT
amount                  INT UNSIGNED NOT NULL               -- SYP
currency                VARCHAR(3) NOT NULL DEFAULT 'SYP'
provider                ENUM('syriatel_cash','mtn_cash','fatora','sama_pay','wallet') NOT NULL
flow_type               ENUM('otp','webview','internal') NOT NULL  -- internal for wallet payments
status                  ENUM('pending','processing','completed','failed','refunded','cancelled') NOT NULL DEFAULT 'pending'
provider_transaction_id VARCHAR(255) NULL                  -- external reference
provider_reference      VARCHAR(255) NULL                  -- guid/transactionId
provider_meta           JSON NULL                           -- encrypted: {guid,phone,invoiceId,operationNumber,sequence}
provider_payload        JSON NULL                           -- raw callback payload from provider
initiated_at            TIMESTAMP NULL
completed_at            TIMESTAMP NULL
failed_at               TIMESTAMP NULL
failure_reason          TEXT NULL
created_at              TIMESTAMP NULL
updated_at              TIMESTAMP NULL
```
**Indexes:**
- `booking_id` — NOT UNIQUE (multiple payment attempts per booking are allowed on failure/retry)
- UNIQUE `(booking_id, status)` WHERE status='completed' — enforced at application layer, not DB unique constraint
- `user_id`, `status`, `provider`
- `provider_transaction_id` — callback lookup
- `(status, created_at)` — reporting queries
**CRITICAL NOTE:** Do NOT put UNIQUE on `booking_id` alone — players can retry payments after failure. Application must enforce: no new payment initiation if a `completed` payment already exists for this booking.
**Security:** `provider_meta` should be encrypted at rest using Laravel's `encrypted` cast.

---

### `settlements`
> Records of money paid by platform to clubs/venues
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
club_id             BIGINT UNSIGNED NOT NULL FK → clubs(id) RESTRICT
status              ENUM('draft','pending','completed','cancelled') NOT NULL DEFAULT 'draft'
period_from         DATE NOT NULL          -- settlement covers bookings from this date
period_to           DATE NOT NULL          -- to this date
total_gross         INT UNSIGNED NOT NULL DEFAULT 0   -- sum of gross_amount of included bookings
total_commission    INT UNSIGNED NOT NULL DEFAULT 0   -- sum of commission_amount
total_cancellation_fees INT UNSIGNED NOT NULL DEFAULT 0
net_payable         INT UNSIGNED NOT NULL DEFAULT 0   -- what platform owes club = total_gross - total_commission - total_cancellation_fees
paid_amount         INT UNSIGNED NOT NULL DEFAULT 0   -- actual amount paid
payment_method      VARCHAR(100) NULL       -- "bank transfer", "cash", etc.
payment_reference   VARCHAR(255) NULL       -- bank transfer ID, receipt number
note                TEXT NULL
settled_by          BIGINT UNSIGNED NULL FK → users(id) SET NULL
settled_at          TIMESTAMP NULL
created_at          TIMESTAMP NULL
updated_at          TIMESTAMP NULL
```
**Indexes:**
- `club_id`, `status`
- `(club_id, period_from, period_to)` — overlap check
- `settled_at`
**Notes:**
- Created manually by Super Admin from Dashboard when paying club
- `settled_at` set when status → completed
- Club Dashboard shows their settlements (read-only)

---

### `settlement_items`
> Line items linking bookings to a settlement
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
settlement_id   BIGINT UNSIGNED NOT NULL FK → settlements(id) CASCADE
booking_id      BIGINT UNSIGNED NOT NULL FK → bookings(id) RESTRICT
gross_amount    INT UNSIGNED NOT NULL   -- snapshot from booking
commission_amount INT UNSIGNED NOT NULL
venue_amount    INT UNSIGNED NOT NULL
created_at      TIMESTAMP NULL
```
**Indexes:**
- `settlement_id`
- UNIQUE `(settlement_id, booking_id)` — no double-counting
- `booking_id` — check if booking already settled

---

### `payment_methods`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            JSON NOT NULL       -- {"ar":"سيريتل كاش","en":"Syriatel Cash"}
provider_key    ENUM('syriatel_cash','mtn_cash','fatora','sama_pay') NOT NULL UNIQUE
flow_type       ENUM('otp','webview') NOT NULL
is_active       TINYINT(1) NOT NULL DEFAULT 1
order_column    INT UNSIGNED NOT NULL DEFAULT 0  -- [spatie/sortable]
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:** UNIQUE `provider_key`, `is_active`, `order_column`
**Notes:** Seeded with 4 records. Admin toggles `is_active`. No soft delete.
**Media collections:** `icon` — via spatie/medialibrary

---

### `wallets`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
user_id         BIGINT UNSIGNED NOT NULL UNIQUE FK → users(id) CASCADE
balance         INT NOT NULL DEFAULT 0              -- SYP, cached sum (can go negative only via bug)
currency        VARCHAR(3) NOT NULL DEFAULT 'SYP'
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:** UNIQUE `user_id`
**Notes:**
- `balance` is a cached field — source of truth is `wallet_transactions` sum
- Created automatically when user is created (via Observer or seeder)
- CHECK CONSTRAINT: `balance >= 0` — prevents negative balance at DB level
- Balance recalculation: `SELECT SUM(CASE WHEN type='credit' THEN amount ELSE -amount END) FROM wallet_transactions WHERE wallet_id=?`

---

### `wallet_transactions`
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
wallet_id           BIGINT UNSIGNED NOT NULL FK → wallets(id) RESTRICT
type                ENUM('credit','debit') NOT NULL
amount              INT UNSIGNED NOT NULL        -- always positive, SYP
balance_after       INT NOT NULL                -- snapshot after this transaction
reason              ENUM('booking_refund','booking_payment','admin_adjustment','cancellation_deduction') NOT NULL
reference_type      VARCHAR(100) NULL           -- 'App\Models\Booking'
reference_id        BIGINT UNSIGNED NULL        -- polymorphic reference
note                TEXT NULL                   -- admin note for manual adjustments
created_by          BIGINT UNSIGNED NULL FK → users(id) SET NULL   -- who triggered this
created_at          TIMESTAMP NULL
```
**Indexes:**
- `wallet_id` — all transactions for a wallet
- `(wallet_id, created_at)` — sorted transaction history
- `(reference_type, reference_id)` — find transactions linked to a booking
**Notes:**
- APPEND-ONLY. Never UPDATE or DELETE rows in this table.
- No `updated_at` — immutable records
- `balance_after` enables point-in-time balance reconstruction without SUM
- Reconciliation: `SUM` of all transactions should equal `wallets.balance`

---

### `reviews`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
user_id         BIGINT UNSIGNED NOT NULL FK → users(id) RESTRICT
club_id         BIGINT UNSIGNED NOT NULL FK → clubs(id) RESTRICT
booking_id      BIGINT UNSIGNED NOT NULL FK → bookings(id) RESTRICT
rating          DECIMAL(2,1) NOT NULL   -- 1.0 to 5.0
body            TEXT NULL               -- min 50 chars if provided (app-level validation)
is_anonymous    TINYINT(1) NOT NULL DEFAULT 0
venue_hint      VARCHAR(255) NULL       -- "Venue name at time of booking" — shown even when anonymous
is_published    TINYINT(1) NOT NULL DEFAULT 1
published_at    TIMESTAMP NULL
hidden_at       TIMESTAMP NULL
hidden_by       BIGINT UNSIGNED NULL FK → users(id) SET NULL
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:**
- UNIQUE `(user_id, club_id)` — one review per player per club
- `club_id`, `is_published`
- `(club_id, is_published, created_at)` — reviews listing
- `booking_id` — eligibility lookup and reverse lookup
- `rating` — aggregate calculations
**Notes:**
- No soft delete — `is_published = 0` is the moderation mechanism
- `venue_hint` is a denormalized snapshot — not a FK

---

### `saved_venues`
```sql
id          BIGINT UNSIGNED PK AUTO_INCREMENT
user_id     BIGINT UNSIGNED NOT NULL FK → users(id) CASCADE
venue_id    BIGINT UNSIGNED NOT NULL FK → venues(id) CASCADE
created_at  TIMESTAMP NULL
```
**Indexes:** UNIQUE `(user_id, venue_id)`, `venue_id`
**Notes:** No `updated_at`. Pivot table — users save/unsave venues.

---

### `settlements`
> Records of money owed by platform to clubs. Admin creates + marks paid. (LD-062)
```sql
id                      BIGINT UNSIGNED PK AUTO_INCREMENT
club_id                 BIGINT UNSIGNED NOT NULL FK → clubs(id) RESTRICT
period_from             DATE NOT NULL
period_to               DATE NOT NULL
total_bookings          INT UNSIGNED NOT NULL DEFAULT 0   -- count of confirmed/completed bookings in period
total_venue_price       INT UNSIGNED NOT NULL DEFAULT 0   -- SUM(venue_price) — original venue prices
total_commission        INT UNSIGNED NOT NULL DEFAULT 0   -- SUM(commission_amount) — platform's cut
total_cancellation_fees INT UNSIGNED NOT NULL DEFAULT 0   -- SUM(cancellation_commission) — fees kept on cancellations
net_payable             INT UNSIGNED NOT NULL DEFAULT 0   -- SUM(club_payout_amount) — what platform owes club
paid_amount             INT UNSIGNED NOT NULL DEFAULT 0   -- actual amount transferred (may differ from net_payable)
status                  ENUM('draft','pending','completed','cancelled') NOT NULL DEFAULT 'draft'
payment_method          VARCHAR(100) NULL                 -- "bank_transfer", "cash", "syriatel_cash"
payment_reference       VARCHAR(255) NULL                 -- bank receipt, transfer ID
note                    TEXT NULL
settled_by              BIGINT UNSIGNED NULL FK → users(id) SET NULL
settled_at              TIMESTAMP NULL
created_by              BIGINT UNSIGNED NULL FK → users(id) SET NULL
created_at              TIMESTAMP NULL
updated_at              TIMESTAMP NULL
```
**Indexes:**
- `club_id`, `status`
- `(club_id, period_from, period_to)` — uniqueness check (no overlapping periods)
- `settled_at`
**Notes:**
- Super Admin creates settlement → selects date range → system auto-calculates totals from `settlement_items`
- Admin enters `payment_reference` when actually paying → marks `completed`
- Club Dashboard: read-only view of all their settlements + current unsettled balance

---

### `settlement_items`
> Line items: each confirmed/completed booking linked to a settlement (LD-062)
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
settlement_id       BIGINT UNSIGNED NOT NULL FK → settlements(id) CASCADE
booking_id          BIGINT UNSIGNED NOT NULL FK → bookings(id) RESTRICT
venue_price         INT UNSIGNED NOT NULL   -- snapshot from booking
commission_amount   INT UNSIGNED NOT NULL   -- snapshot from booking
club_payout_amount  INT UNSIGNED NOT NULL   -- snapshot from booking
cancellation_comm   INT UNSIGNED NOT NULL DEFAULT 0
created_at          TIMESTAMP NULL
```
**Indexes:**
- `settlement_id`
- UNIQUE `(settlement_id, booking_id)` — no double-counting
- `booking_id` — check if booking is already in a settlement

---

### `app_platforms`
```sql
id                      BIGINT UNSIGNED PK AUTO_INCREMENT
platform_key            VARCHAR(50) NOT NULL UNIQUE    -- 'ios', 'android', 'huawei'
name                    JSON NOT NULL                   -- {"ar":"أندرويد","en":"Android"}
latest_version          VARCHAR(20) NOT NULL            -- semver
minimum_required_version VARCHAR(20) NOT NULL           -- semver
store_url               VARCHAR(500) NULL
direct_apk_url          VARCHAR(500) NULL               -- nullable, Android-type only
direct_apk_enabled      TINYINT(1) NOT NULL DEFAULT 0
is_active               TINYINT(1) NOT NULL DEFAULT 1
order_column            INT UNSIGNED NOT NULL DEFAULT 0
last_updated_by         BIGINT UNSIGNED NULL FK → users(id) SET NULL
created_at              TIMESTAMP NULL
updated_at              TIMESTAMP NULL
```
**Indexes:** UNIQUE `platform_key`, `is_active`

---

### `app_environments`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
platform_id     BIGINT UNSIGNED NOT NULL FK → app_platforms(id) CASCADE
name            VARCHAR(50) NOT NULL    -- 'Live', 'Stage', 'Beta'
base_url        VARCHAR(500) NOT NULL
is_active       TINYINT(1) NOT NULL DEFAULT 0
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:** `platform_id`, `(platform_id, is_active)`
**Constraint:** Application enforces only ONE active environment per platform at a time. Consider a DB-level unique partial index if MySQL version supports it.

---

### `content_pages`
```sql
id          BIGINT UNSIGNED PK AUTO_INCREMENT
slug        VARCHAR(100) NOT NULL UNIQUE    -- 'about', 'privacy', 'terms', 'help'
title       JSON NOT NULL                   -- {"ar":"عن التطبيق","en":"About"}
body        JSON NOT NULL                   -- {"ar":"<html>...","en":"<html>..."} — rich text
is_active   TINYINT(1) NOT NULL DEFAULT 1
created_at  TIMESTAMP NULL
updated_at  TIMESTAMP NULL
```
**Indexes:** UNIQUE `slug`, `is_active`
**Notes:** No soft delete. These are system pages — deactivate, don't delete.

---

### `competitions`
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
title           JSON NOT NULL       -- {"ar":"بطولة الصيف","en":"Summer Championship"}
description     JSON NULL
start_date      DATE NOT NULL
end_date        DATE NULL
is_published    TINYINT(1) NOT NULL DEFAULT 0
published_at    TIMESTAMP NULL
created_at      TIMESTAMP NULL
updated_at      TIMESTAMP NULL
```
**Indexes:** `is_published`, `(is_published, start_date)`
**Media collections:** `image` — via spatie/medialibrary

---

## Package-Generated Tables (do not manually create)

```
world_countries         [nnjeim/world — seeded from international data]
world_states            [nnjeim/world — states/provinces/governorates]
world_cities            [nnjeim/world — cities and districts]
media                   [spatie/medialibrary]
settings                [spatie/laravel-settings]
activity_log            [spatie/laravel-activitylog]
personal_access_tokens  [laravel/sanctum]
roles                   [spatie/laravel-permission]
permissions             [spatie/laravel-permission]
model_has_roles         [spatie/laravel-permission]
model_has_permissions   [spatie/laravel-permission]
role_has_permissions    [spatie/laravel-permission]
jobs                    [Laravel Queue]
failed_jobs             [Laravel Queue]
job_batches             [Laravel Queue]
```

---

## Dropped Entities (Package replacements)

```
cities                    → world_cities (nnjeim/world package) + is_active column
areas                     → world_states (nnjeim/world package) + is_active column
venue_availability_rules  → venues.opening_hours JSON (spatie/opening-hours)
venue_allowed_durations   → DISTINCT duration_minutes FROM venue_pricing_tiers
venue_sport_categories    → venues.category_id direct FK (LD-054, LD-061)
club_images               → media table (spatie/medialibrary)
venue_images              → media table (spatie/medialibrary)
admin_users               → users table with Spatie roles (LD-051)
club_staff_members        → users table with Spatie roles + club_user pivot (LD-051, LD-060)
system_settings           → settings table (spatie/laravel-settings)
sport_categories          → venue_categories (LD-061: generalized for scalability)
```


---

# 5. Concurrency & Transaction Analysis

## Critical Race Conditions

### Race 1 — Double Booking (HIGHEST RISK)
Two players hit "Pay" for the same slot at the same time.

**Current mitigation:** SlotReservation (10min lock)
**Problem:** SlotReservation is created at payment initiation, not at slot selection. The window between slot display and payment initiation is unprotected.

**Solution — DB-level enforcement:**
```sql
-- When creating a booking (inside transaction):
SELECT id FROM bookings
WHERE venue_id = ? AND booking_date = ? AND status NOT IN ('cancelled','failed')
AND (
  (start_time < ? AND end_time > ?)  -- new slot start overlaps existing
  OR (start_time < ? AND end_time > ?)  -- new slot end overlaps existing
)
FOR UPDATE;  -- lock the rows

-- If rows returned → abort, slot taken
-- If no rows → safe to insert booking
```
This must run inside `DB::transaction()`. The `FOR UPDATE` prevents concurrent inserts.

### Race 2 — Wallet Double-Spend
Player initiates two payments simultaneously using wallet balance.

**Solution:**
```sql
-- Inside transaction:
SELECT balance FROM wallets WHERE id = ? FOR UPDATE;
-- Check balance >= amount
-- Insert wallet_transaction
-- UPDATE wallets SET balance = balance - amount WHERE id = ? AND balance >= amount
-- Check affected rows = 1, else rollback
```
Never rely on application-level balance check. Always use `FOR UPDATE`.

### Race 3 — Slot Reservation Expiry Race
Scheduler expires a slot_reservation at the same moment payment is being confirmed.

**Solution:** Payment confirmation checks:
1. Find slot_reservation by id WHERE reserved_until > NOW()
2. If found → proceed
3. If NOT found → check if already a booking exists (idempotency)
4. If neither → return SLOT_EXPIRED error

### Race 4 — Fatora/SamaPay Callback Replay
Provider sends callback twice for same transaction.

**Solution:** Idempotency key on `payments.provider_transaction_id`:
```sql
UPDATE payments SET status='completed', completed_at=NOW()
WHERE provider_transaction_id = ? AND status = 'pending'
-- Check affected rows = 1; if 0 → already processed, return OK anyway
```

---

# 6. Package Recommendations

## ADOPT (confirmed)

### spatie/laravel-permission
**Why needed:** Dynamic role/permission system. All routes protected per LD-053.
**Native Laravel:** Laravel gates exist but have no DB-backed dynamic role management.
**Verdict:** ADOPT. Standard for this use case.
**Source:** https://spatie.be/docs/laravel-permission/v6/introduction

### spatie/laravel-translatable
**Why needed:** Bilingual AR/EN content stored in JSON columns. Eliminates _ar/_en column pairs.
**Native Laravel:** No built-in multilingual model support.
**Verdict:** ADOPT. Reduces column count significantly.
**Risk:** MySQL JSON column indexing is limited — use `->where('name->ar', ...)` syntax for filtering; avoid full-text JSON searches.
**Source:** https://spatie.be/docs/laravel-translatable/v6/introduction

### spatie/laravel-medialibrary
**Why needed:** Replaces all image URL columns with a single polymorphic `media` table. Handles file storage, conversions, collections.
**Native Laravel:** Laravel has Filesystem but no model-attached media management.
**Verdict:** ADOPT. Significant schema simplification.
**Risk:** Every image URL access requires an extra query or eager-loading. Must use `->load('media')` consistently.
**Source:** https://spatie.be/docs/laravel-medialibrary/v11/introduction

### spatie/opening-hours
**Why needed:** Replaces `venue_availability_rules` table. Stores weekly schedule + exceptions in JSON. Provides isOpenAt(), nextOpen() etc.
**Native Laravel:** Nothing equivalent.
**Verdict:** ADOPT. Eliminates a whole table.
**Risk:** Stored in JSON — cannot query "all venues open at 14:00 on Friday" in SQL. Must load and check in PHP. Acceptable for venue-count scale (hundreds, not millions).
**Source:** https://github.com/spatie/opening-hours

### spatie/laravel-settings
**Why needed:** Replaces key-value `system_settings` table with strongly typed PHP classes. Grouped, cacheable, type-safe.
**Native Laravel:** No equivalent. Config files are not DB-backed.
**Verdict:** ADOPT. Typed settings are maintainable settings.
**Source:** https://spatie.be/docs/laravel-settings/v3/introduction

### spatie/laravel-activitylog
**Why needed:** Audit trail for admin actions on clubs, venues, bookings.
**Native Laravel:** No equivalent.
**Verdict:** ADOPT. Required for any system where admins modify data.
**Source:** https://spatie.be/docs/laravel-activitylog/v4/introduction

### spatie/laravel-query-builder
**Why needed:** Clean filter/sort/include parsing from query params for API endpoints.
**Native Laravel:** Manual if/else filter chains — error-prone and verbose.
**Verdict:** ADOPT.
**Source:** https://spatie.be/docs/laravel-query-builder/v5/introduction

### spatie/laravel-model-states
**Why needed:** Enforce valid state transitions on Booking and Payment.
**Native Laravel:** No state machine. Without this, `cancelled → confirmed` is possible in code.
**Verdict:** ADOPT. Booking state bugs are business-critical.
**Source:** https://spatie.be/docs/laravel-model-states/v2/introduction

### propaganistas/laravel-phone
**Why needed:** Validate and normalize Syrian phone numbers (+963).
**Native Laravel:** No phone validation. regex alone is insufficient.
**Verdict:** ADOPT.
**Source:** https://github.com/Propaganistas/Laravel-Phone

### spatie/laravel-backup
**Why needed:** Daily automated DB + file backup. Production safety minimum.
**Verdict:** ADOPT.

### laravel/telescope (dev only)
**Verdict:** ADOPT for dev/staging. Never enable in production.

## AVOID / REJECT

### 2FA — Custom TotpService (No Package — RFC 6238 + PHP Native)
**Decision:** No external package. Custom `TotpService` class built directly on PHP native functions.
**Why no package:** Same principle as Firebase auth — use the official standard directly.
**PHP native functions used (all built-in, no extension needed):**
- `hash_hmac('sha1', $data, $key, true)` — RFC 2104 HMAC
- `random_bytes(20)` — cryptographically secure secret generation
- `pack('N', ...)` / `unpack(...)` — binary encoding
**Algorithm (RFC 6238):**
```php
// App/Services/TotpService.php
class TotpService
{
    private const STEP = 30;
    private const DIGITS = 6;

    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = (int) floor(time() / self::STEP);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->generate($secret, $timestamp + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    private function generate(string $secret, int $timestamp): string
    {
        $key  = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timestamp); // 64-bit big-endian
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xF;
        $code = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
            ( ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);
        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public function getQrCodeUrl(string $appName, string $email, string $secret): string
    {
        return 'otpauth://totp/'
            . rawurlencode($appName) . ':' . rawurlencode($email)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($appName)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    // Base32 encode/decode per RFC 3548 — straightforward bit manipulation
    private function base32Encode(string $data): string { ... }
    private function base32Decode(string $data): string { ... }
}
```
**Schema impact:** Two columns on `users`:
- `google2fa_secret` VARCHAR(255) NULL encrypted
- `google2fa_enabled_at` TIMESTAMP NULL
**Source:** RFC 6238 — https://www.rfc-editor.org/rfc/rfc6238 | PHP manual — https://www.php.net/manual/en/function.hash-hmac.php
**Verdict:** ADOPT custom implementation. Zero dependencies, full control, RFC-compliant.

### spatie/laravel-sluggable
**Assessment:** Adds `slug` column auto-generation. Needed for clubs and sport_categories.
**Verdict:** ADOPT — lightweight, no schema risk.

### spatie/laravel-sortable
**Assessment:** Adds `order_column` management. Needed for categories, payment_methods.
**Verdict:** ADOPT — lightweight.

### spatie/laravel-searchable
**Assessment:** Uses LIKE queries. Does not support MySQL FULLTEXT.
**For Dashboard search:** Accept it — dashboard search volumes are low.
**For Mobile search:** Write custom FULLTEXT query manually. spatie/laravel-searchable is not adequate for Arabic full-text search with JSON columns.
**Verdict:** OPTIONAL for dashboard. AVOID for mobile API search.

### spatie/laravel-data
**Assessment:** Replaces API Resources + Form Requests with typed Data objects.
**Verdict:** ADOPT — but enforce discipline. Do not use it as an excuse to skip validation. Every Data class must have explicit `#[Rule]` attributes.

### spatie/laravel-google-calendar
**Verdict:** DEFERRED (Phase 2). Do not add now.

---

# 7. What to Lock vs What to Keep Dynamic

## LOCK in code (never in DB)
- Payment provider names and their flow types (OTP vs WebView) — these are architectural
- Booking status enum values — changing these breaks the state machine
- OTP code format (5 digits, sha256 hash) — changing this breaks active OTP challenges
- Guard names ('web', 'api') — changing these breaks all Sanctum tokens

## KEEP in settings table (spatie/laravel-settings)
- OTP expiry seconds, resend cooldown, max attempts
- Booking cancellation window (30 minutes threshold)
- Slot reservation TTL (10 minutes)
- Cancellation deduction type + value
- Active SMS provider (syriatel | mtn)
- WhatsApp OTP enabled toggle
- my_grounds_min_rating (3.0)
- Booking reminder hours ([2, 1])
- Scheduled booking payment window hours
- Football API cache TTL

## KEEP in DB (admin-managed)
- Sport categories (dynamic per LD-005)
- Payment methods active/inactive toggle
- App platforms and environments
- Content pages (About, Privacy, Terms)
- Roles and permissions
- Competitions

---

# 8. Final Senior Verdict

## Is the schema production-safe?
**Yes, with the corrections noted above.** The core design is sound.

## Top 10 Risks

1. **Wallet balance race condition** — must use `FOR UPDATE` on every debit operation, no exceptions
2. **Double booking window** — slot_reservation helps but the booking insert itself needs `FOR UPDATE` check
3. **Fatora callback replay** — no idempotency key enforced at DB level currently
4. **provider_meta plain text** — payment credentials (phone, guid) stored in JSON without encryption
5. **`users.password` nullable confusion** — OTP players have null password; dashboard users have password; no DB constraint prevents this from being mixed up
6. **`club_id` on users** — if a club_staff member's club is deleted, the FK is SET NULL silently; application must handle this
7. **opening_hours JSON** — no validation at DB level; corrupted JSON will silently break slot generation
8. **wallet_transactions immutability** — must be enforced at application layer; no DB-level enforcement prevents UPDATE/DELETE
9. **activity_log growth** — with activitylog enabled on many models and heavy usage, this table can grow to millions of rows quickly. Add periodic archival or pruning strategy.
10. **`venue_pricing_tiers` overlap** — overlapping tiers for same venue+day+duration cause unpredictable pricing. Must be validated at application layer before insert/update.

## Top 10 Improvements

1. Encrypt `payments.provider_meta` using Laravel's `encrypted` cast
2. Add CHECK CONSTRAINT `wallets.balance >= 0` at DB level
3. Create a `WalletService` class that is the ONLY path to modify wallet balance
4. Add `booking_date` + `start_time` + `end_time` composite index on `bookings` — this is the hottest query
5. Add `SELECT FOR UPDATE` in booking creation transaction explicitly in code
6. Create a `PaymentIdempotencyKey` mechanism: store `(user_id, venue_id, booking_date, start_time)` in a temp table/cache before payment initiation
7. Add `canceled_at` index on `slot_reservations` to speed up the scheduler cleanup
8. Define a `WalletReconcileCommand` that verifies `wallets.balance == SUM(wallet_transactions)` — run weekly
9. Add database-level UNIQUE constraint on `payments.booking_id` — one payment per booking only
10. Add a `reviews` purging policy — hidden reviews older than 2 years can be archived

