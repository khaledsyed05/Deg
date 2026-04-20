# Implementation Specification
## دق احجزلي — Daq Ehjezly
> Version: 1.0 — Production Release Contract
> Date: 2026-04-04
> Status: CANONICAL — Engineering Execution Document

---

# 1. Document Purpose

This document is the execution contract for the Daq Ehjezly platform. It is implementation-oriented, internally consistent, and free from contradictions.

The architecture document (`product_architecture_master.md`) remains the strategic reference layer and is NOT replaced by this document. When these documents conflict, **this implementation spec takes precedence** because it was produced by resolving all contradictions found across all source documents. The `contradiction_resolution_log.md` documents every resolved conflict.

Engineering teams must build from this spec. They may reference the architecture document for business context, but must not implement from it directly where this spec defines the same topic.

---

# 2. Scope of This Implementation Spec

## In Scope — Production Release

- Player mobile API (Flutter app consumption)
- Super Admin Dashboard (`/admin/*`)
- Club Dashboard (`/club/*`)
- Authentication: OTP (SMS + WhatsApp/Baileys) + Google Sign-In
- Venue booking lifecycle (standard only — recurring is Phase 2)
- Four payment providers: MTN Cash, Syriatel Cash, Fatora, SamaPay + internal wallet
- Commission model and settlement system
- Notification system (FCM — mobile + PWA)
- Internal wallet with refund on cancellation
- Review system at club level
- Events tab (competitions + football matches proxy)
- App startup / version control API
- Geographic model: City → Area → Club → Venue
- Venue categories (generic, scalable beyond sports)

## Explicitly Out of Scope — Not in This Release

- User-facing PWA (deferred to Phase 2)
- Apple Sign-In (not mentioned in confirmed decisions)
- Cash payment at venue (no confirmed integration)
- Multi-currency support (SYP only)
- Customer support / ticketing module
- Automated bank payout (manual settlement only)

---

# 3. Canonical Product Model

Daq Ehjezly is a venue-booking marketplace operating in Syria. It connects players seeking bookable spaces with clubs/venue operators. The platform takes a commission on each transaction and settles with clubs periodically via offline bank transfer.

## Bounded Domains

| Domain | Core Entities | Description |
|--------|--------------|-------------|
| Identity & Auth | users, social_identities, otp_challenges | Single users table for all actors |
| Geography | cities, areas | 2-level hierarchy scoping club discovery |
| Catalog | clubs, venues, venue_categories, venue_pricing_tiers | What can be booked |
| Booking | bookings, slot_reservations | The core transaction |
| Payment | payments, payment_methods, wallets, wallet_transactions | Money flow |
| Commission & Settlement | commission_configs, settlements, settlement_items | Platform revenue |
| Content | content_pages, competitions, sport_categories | Display/editorial |
| Configuration | app_platforms, app_environments, settings | System control |
| Reviews | reviews | Club-level reputation |
| Notifications | FCM tokens on users | Push delivery |

---

# 4. Actor Model and Access Boundaries

## 4.1 Player (Mobile User)

**Auth:** Phone OTP (SMS or WhatsApp) OR Google Sign-In. No email/password.
**Guard:** `api` (Laravel Sanctum token).
**Token TTL:** 30 days. Re-authenticates via OTP on expiry. No refresh token.

**Can do:**
- Browse cities, areas, clubs, venues, categories (including as guest/unauthenticated)
- Search and filter clubs
- View slot availability and pricing
- Book a venue (requires verified phone number)
- Pay via any active payment method or wallet
- Create recurring (scheduled) bookings
- Cancel own confirmed bookings (within allowed window)
- View booking history (upcoming + history)
- Save/unsave venues
- Write one review per club (requires completed booking at that club)
- View and use wallet balance
- Update own profile
- View content pages, competitions, football matches

**Cannot do:**
- Book without a verified phone number (`PHONE_REQUIRED` error)
- Book past dates
- Cancel within 30 minutes of booking start
- Access any dashboard route
- View other players' data

**Limited state (Google user, no phone):**
- Can browse everything
- Cannot book, pay, or write reviews
- All blocked actions return `PHONE_REQUIRED`

---

## 4.2 Super Admin

**Auth:** Email + password + mandatory TOTP 2FA (Google Authenticator).
**Guard:** `web`. Session-based via Inertia.
**2FA:** Custom `TotpService` (RFC 6238, PHP native, no package). 6-digit code. ±1 window.

**Can do:** Everything across the entire system without restriction.

**Specific exclusive capabilities:**
- Approve / reject clubs
- Create all dashboard user accounts (club staff, other admins)
- Assign staff to clubs via `club_user` pivot
- Configure commission rules (global, club-level, venue-level)
- Generate and mark settlements
- Manage app platforms and environments
- Manage roles and permissions via Spatie
- Access revenue and financial reports for the entire platform
- Toggle payment methods, SMS providers, WhatsApp OTP
- Moderate reviews (publish/hide)

---

## 4.3 Club Admin

**Auth:** Email + password. No 2FA.
**Guard:** `web`.
**Scope:** Only clubs they are assigned to via `club_user` pivot.

**Can do (within assigned clubs only):**
- Edit club profile
- Full CRUD on venues within club
- Set opening hours per venue
- Define pricing tiers per venue
- Add manual/blocked bookings (external or maintenance)
- View all bookings for own venues
- Cancel player bookings for own venues
- Manage club staff (invite, remove, change roles)
- View revenue reports and booking summaries for own club
- View settlement history (read-only)
- View commission breakdown per booking

**Cannot do:**
- Access any other club's data
- Change system settings
- Manage app users (players)
- Create or modify commission configs
- Mark settlements as paid

---

## 4.4 Club Owner

**Auth:** Email + password. No 2FA.
**Guard:** `web`.
**Scope:** Clubs assigned to them via `club_user` pivot (read-only financial view).

**Can do:**
- View club statistics (booking counts, occupancy, revenue)
- View settlement history for own clubs
- View current unsettled balance
- View commission breakdown

**Cannot do:**
- Edit venues, pricing, or availability
- Manage staff
- Add manual bookings
- Access player data

---

## 4.5 Club Data Entry

**Auth:** Email + password. No 2FA.
**Guard:** `web`.

**Can do (within assigned clubs only):**
- Create and edit venues
- Upload venue images
- Set opening hours and pricing tiers
- Add manual/blocked bookings

**Cannot do:**
- View financials or settlements
- Manage staff
- Cancel player bookings
- Access any player data

---

## 4.6 Guest (Unauthenticated)

- View: categories, clubs, venues, content pages, competitions, football matches
- Cannot book, pay, save venues, or write reviews

---

# 5. Canonical Domain Entities

## 5.1 users

**Purpose:** Single identity table for ALL actors — players, club staff, super admin.
**Ownership:** Self-owned. Created by player on first auth, or by Super Admin for dashboard users.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(255) NULL | Nullable until onboarding complete |
| email | VARCHAR(255) NULL UNIQUE | Optional for players; required for dashboard users |
| phone_number | VARCHAR(20) NULL UNIQUE | E.164. Nullable for Google-only players |
| phone_verified_at | TIMESTAMP NULL | Set after OTP verification |
| firebase_uid | VARCHAR(128) NULL UNIQUE | Google Sign-In UID |
| password | VARCHAR(255) NULL | NULL for players; set for dashboard users |
| default_state_id | BIGINT UNSIGNED NULL FK → states SET NULL | المحافظة |
| default_area_id | BIGINT UNSIGNED NULL FK → areas | |
| fcm_token | TEXT NULL | Cleared on logout |
| fcm_platform | ENUM('mobile','web') NULL | |
| account_status | ENUM('active','blocked','suspended','pending_profile_completion') | Default: pending_profile_completion |
| onboarding_completed_at | TIMESTAMP NULL | |
| notifications_push_enabled | TINYINT(1) | Default 1 |
| notifications_sms_enabled | TINYINT(1) | Default 1 |
| notifications_reminders_enabled | TINYINT(1) | Default 1 |
| preferred_language | ENUM('ar','en') | Default 'ar' |
| google2fa_secret | VARCHAR(255) NULL | Encrypted. Super admin only. |
| google2fa_enabled_at | TIMESTAMP NULL | Super admin only. |
| last_login_at | TIMESTAMP NULL | |
| deleted_at | TIMESTAMP NULL | Soft delete |

**Role separation:** Entirely via Spatie Permissions (`roles`, `model_has_roles`). No separate tables.

---

## 5.2 countries

**مصدر البيانات:** `dr5hn/countries-states-cities-database` (ODbL) — Seeded مرة واحدة. لا تعديل يدوي.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | نفس id من المصدر |
| iso2 | CHAR(2) UNIQUE | 'SY', 'LB' |
| iso3 | CHAR(3) UNIQUE | 'SYR', 'LBN' |
| name | VARCHAR(150) | English — من المصدر |
| name_ar | VARCHAR(150) NULL | يُضاف يدوياً للدول المفعّلة |
| phone_code | VARCHAR(10) | '+963' — يُستخدم في Mobile |
| capital | VARCHAR(150) NULL | |
| currency | VARCHAR(10) NULL | 'SYP' |
| latitude | DECIMAL(10,8) NULL | |
| longitude | DECIMAL(11,8) NULL | |
| is_active | TINYINT(1) DEFAULT 0 | Admin يُفعّل فقط |

---

## 5.3 states (المحافظات)

**مصدر البيانات:** `dr5hn/countries-states-cities-database` — Seeded مرة واحدة.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | نفس id من المصدر |
| country_id | BIGINT UNSIGNED FK → countries RESTRICT | |
| name | VARCHAR(150) | English — من المصدر |
| name_ar | VARCHAR(150) NULL | |
| state_code | VARCHAR(20) NULL | |
| latitude | DECIMAL(10,8) NULL | |
| longitude | DECIMAL(11,8) NULL | |
| is_active | TINYINT(1) DEFAULT 0 | Admin يُفعّل المحافظات المطلوبة |

---

## 5.4 cities (المدن / المناطق)

**مصدر البيانات:** `dr5hn/countries-states-cities-database` — Seeded مرة واحدة.
**يحلّ محلّ:** جدول `areas` القديم.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | نفس id من المصدر |
| state_id | BIGINT UNSIGNED FK → states RESTRICT | |
| name | VARCHAR(150) | English — من المصدر |
| name_ar | VARCHAR(150) NULL | |
| latitude | DECIMAL(10,8) NULL | |
| longitude | DECIMAL(11,8) NULL | |
| is_active | TINYINT(1) DEFAULT 0 | Admin يُفعّل المدن/المناطق المطلوبة |

---

## 5.4 venue_categories

**Purpose:** Generic categories for bookable spaces. Replaces `sport_categories` for venue classification. Supports scalability to non-sports venues.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| name | JSON NOT NULL | `{"ar":"ملاعب رياضية","en":"Sports Venues"}` |
| slug | VARCHAR(100) UNIQUE | Auto-generated from name.en |
| type | ENUM('sports','hall','court','outdoor','other') | Determines booking UI context |
| is_active | TINYINT(1) | |
| order_column | INT UNSIGNED | |

**Note:** `sport_categories` table exists separately and is used ONLY for the Events tab (football match display). It has NO relationship to venues.

---

## 5.5 clubs

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| owner_id | BIGINT UNSIGNED NULL FK → users SET NULL | Primary contact only |
| city_id | BIGINT UNSIGNED NOT NULL FK → cities RESTRICT | يشير للمدينة/المنطقة من dr5hn |
| name | JSON NOT NULL | |
| slug | VARCHAR(255) UNIQUE | |
| description | JSON NULL | |
| address | VARCHAR(500) NULL | |
| phone_number | VARCHAR(20) NULL | |
| latitude | DECIMAL(10,8) NOT NULL | Required |
| longitude | DECIMAL(11,8) NOT NULL | Required |
| amenities | JSON NULL | Array of strings |
| status | ENUM('pending_approval','active','inactive','suspended','rejected') | Default: pending_approval |
| rejection_reason | TEXT NULL | |
| is_featured | TINYINT(1) | Default 0 |
| avg_rating | DECIMAL(3,2) NULL | CACHED from reviews. Updated by ReviewObserver. |
| reviews_count | INT UNSIGNED | CACHED. Updated by ReviewObserver. |
| price_from | INT UNSIGNED NULL | CACHED. Min of venue pricing tiers. SYP. |
| approved_at | TIMESTAMP NULL | |
| approved_by | BIGINT UNSIGNED NULL FK → users SET NULL | |
| deleted_at | TIMESTAMP NULL | |

**Lifecycle:** `pending_approval` → (Admin approves) → `active` OR (Admin rejects) → `rejected`. Admin can suspend active clubs.

---

## 5.6 club_user (pivot)

**Purpose:** Dynamic multi-club staff assignment. One staff member can manage multiple clubs.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| club_id | BIGINT UNSIGNED FK → clubs CASCADE | |
| user_id | BIGINT UNSIGNED FK → users CASCADE | |
| UNIQUE(club_id, user_id) | | |

---

## 5.7 venues

**Purpose:** A bookable space inside a club. One category. One set of pricing tiers. Slots computed dynamically.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| club_id | BIGINT UNSIGNED FK → clubs CASCADE | |
| category_id | BIGINT UNSIGNED NULL FK → venue_categories SET NULL | Single category (LD-054 + LD-063) |
| name | JSON NOT NULL | |
| description | JSON NULL | |
| size | VARCHAR(50) NULL | e.g., "7×7", "full" |
| amenities | JSON NULL | Array of strings |
| opening_hours | JSON NULL | spatie/opening-hours format |
| latitude | DECIMAL(10,8) NULL | Optional override of club location |
| longitude | DECIMAL(11,8) NULL | |
| avg_rating | DECIMAL(3,2) NULL | Kept for future use. Not computed from reviews (which are at club level). NULL by default. |
| reviews_count | INT UNSIGNED | Default 0. Not updated by ReviewObserver. |
| price_from | INT UNSIGNED NULL | CACHED. Min active tier price. SYP. |
| status | ENUM('active','inactive','suspended') | Default: active |
| order_column | INT UNSIGNED | Display order within club |
| deleted_at | TIMESTAMP NULL | |

---

## 5.8 venue_pricing_tiers

**Purpose:** Time-based pricing rules. A venue has multiple tiers covering different day/time combinations.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| venue_id | BIGINT UNSIGNED FK → venues CASCADE | |
| name | JSON NOT NULL | e.g., `{"ar":"فترة صباحية","en":"Morning Rate"}` |
| day_type | ENUM('all_days','weekday','weekend','friday','specific_day') | weekday = Sun–Thu; weekend = Fri+Sat (Syria calendar) |
| specific_day | ENUM('saturday','sunday','monday','tuesday','wednesday','thursday','friday') NULL | Used when day_type = specific_day |
| start_time | TIME NOT NULL | |
| end_time | TIME NOT NULL | CHECK: end_time > start_time |
| duration_minutes | SMALLINT UNSIGNED NOT NULL | Allowed booking duration for this tier |
| price | INT UNSIGNED NOT NULL | SYP |
| is_active | TINYINT(1) | Default 1 |
| order_column | INT UNSIGNED | |

**Syria calendar:** weekday = Sunday–Thursday. weekend = Friday + Saturday. friday = Friday only (special pricing).

**Allowed durations derivation:** `SELECT DISTINCT duration_minutes FROM venue_pricing_tiers WHERE venue_id=? AND is_active=1`. No separate table.

**Tier resolution:** For a given slot, find tier WHERE `day_type` matches the date's day classification AND `start_time <= slot_start_time < end_time` AND `duration_minutes = requested_duration` AND `is_active = 1`. If no tier → slot is not bookable.

---

## 5.9 bookings

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| user_id | BIGINT UNSIGNED NULL FK → users SET NULL | NULL for blocked/maintenance manual bookings |
| venue_id | BIGINT UNSIGNED NOT NULL FK → venues RESTRICT | |
| category_id | BIGINT UNSIGNED NULL FK → venue_categories SET NULL | Snapshot from venue.category_id at creation |
| booking_code | VARCHAR(20) UNIQUE | e.g., "DQ001234" |
| source | ENUM('mobile','manual') | Default: mobile |
| manual_type | ENUM('external','blocked') NULL | Only when source=manual |
| manual_note | TEXT NULL | |
| status | ENUM('scheduled','confirmed','cancelled','completed','no_show','failed') | See lifecycle §8 |
| booking_date | DATE NOT NULL | |
| start_time | TIME NOT NULL | |
| end_time | TIME NOT NULL | |
| starts_at | DATETIME NOT NULL | Computed: booking_date + start_time. Used for window checks. |
| ends_at | DATETIME NOT NULL | Computed: booking_date + end_time. |
| duration_minutes | SMALLINT UNSIGNED NOT NULL | |
| venue_price | INT UNSIGNED NOT NULL DEFAULT 0 | Snapshot: original venue pricing tier price |
| commission_amount | INT UNSIGNED NOT NULL DEFAULT 0 | Platform commission snapshot |
| commission_type | ENUM('fixed','percentage') NULL | Snapshot of config at booking time |
| apply_as | ENUM('added','deducted') NULL | Snapshot |
| total_price | INT UNSIGNED NOT NULL DEFAULT 0 | What player actually pays |
| club_payout_amount | INT UNSIGNED NOT NULL DEFAULT 0 | What club receives |
| cancellation_commission | INT UNSIGNED NOT NULL DEFAULT 0 | Platform keeps this on cancellation |
| currency | VARCHAR(3) | Default 'SYP' |
| notes | TEXT NULL | |
| cancelled_at | TIMESTAMP NULL | |
| cancellation_reason | TEXT NULL | |
| cancelled_by | BIGINT UNSIGNED NULL FK → users SET NULL | |
| is_recurring | TINYINT(1) | Default 0 |
| recurrence_pattern | JSON NULL | `{"frequency":"weekly","day_of_week":5,"occurrences":8}` |
| recurrence_parent_id | BIGINT UNSIGNED NULL FK → bookings SET NULL | Links occurrences to parent |
| reminder_2h_sent_at | TIMESTAMP NULL | Prevents duplicate reminders |
| reminder_1h_sent_at | TIMESTAMP NULL | |
| reviewed_at | TIMESTAMP NULL | Set when player submits review |

---

## 5.10 slot_reservations

**Purpose:** 10-minute soft lock on a slot during payment flow. Prevents double-booking.

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| venue_id | BIGINT UNSIGNED FK → venues CASCADE | |
| user_id | BIGINT UNSIGNED FK → users CASCADE | |
| booking_date | DATE NOT NULL | |
| start_time | TIME NOT NULL | |
| end_time | TIME NOT NULL | |
| duration_minutes | SMALLINT UNSIGNED | |
| category_id | BIGINT UNSIGNED NULL FK → venue_categories SET NULL | |
| reserved_until | TIMESTAMP NOT NULL | NOW() + configurable minutes (default 10) |
| UNIQUE(venue_id, booking_date, start_time) | | DB-level concurrency protection |

---

## 5.11 payment_methods

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| name | JSON NOT NULL | |
| provider_key | ENUM('syriatel_cash','mtn_cash','fatora','sama_pay') UNIQUE | |
| flow_type | ENUM('otp','webview') | |
| is_active | TINYINT(1) | Admin toggles |
| order_column | INT UNSIGNED | |

Seeded with 4 records. `wallet` is not a payment_method record — it is handled internally.

---

## 5.12 payments

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| booking_id | BIGINT UNSIGNED FK → bookings RESTRICT | NOT UNIQUE — multiple attempts allowed |
| user_id | BIGINT UNSIGNED FK → users RESTRICT | |
| amount | INT UNSIGNED NOT NULL | SYP. Total amount charged (= booking.total_price) |
| currency | VARCHAR(3) | Default 'SYP' |
| provider | ENUM('syriatel_cash','mtn_cash','fatora','sama_pay','wallet') | |
| flow_type | ENUM('otp','webview','internal') | internal for wallet |
| status | ENUM('pending','processing','completed','failed','refunded','cancelled') | Default: pending |
| provider_transaction_id | VARCHAR(255) NULL | External ref |
| provider_reference | VARCHAR(255) NULL | guid / transactionId |
| provider_meta | JSON NULL | Encrypted. Provider-specific state for confirm step. |
| provider_payload | JSON NULL | Raw callback from provider |
| initiated_at | TIMESTAMP NULL | |
| completed_at | TIMESTAMP NULL | |
| failed_at | TIMESTAMP NULL | |
| failure_reason | TEXT NULL | |

**Constraint:** At most ONE `completed` payment per `booking_id`. Enforced at application layer: new payment initiation is blocked if a completed payment exists for the booking.

---

## 5.13 wallets

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| user_id | BIGINT UNSIGNED UNIQUE FK → users CASCADE | One per player |
| balance | INT NOT NULL DEFAULT 0 | CACHED. Source of truth = wallet_transactions SUM. |
| currency | VARCHAR(3) | Default 'SYP' |

**CHECK CONSTRAINT:** `balance >= 0`. Created automatically when player user is created.

---

## 5.14 wallet_transactions

**Append-only ledger. Never UPDATE or DELETE.**

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| wallet_id | BIGINT UNSIGNED FK → wallets RESTRICT | |
| type | ENUM('credit','debit') | |
| amount | INT UNSIGNED NOT NULL | Always positive |
| balance_after | INT NOT NULL | Snapshot after transaction |
| reason | ENUM('booking_refund','booking_payment','admin_adjustment','cancellation_deduction') | |
| reference_type | VARCHAR(100) NULL | 'App\Models\Booking' |
| reference_id | BIGINT UNSIGNED NULL | |
| note | TEXT NULL | Admin note for adjustments |
| created_by | BIGINT UNSIGNED NULL FK → users SET NULL | |

---

## 5.15 commission_configs

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| scope | ENUM('global','club','venue') | |
| club_id | BIGINT UNSIGNED NULL FK → clubs CASCADE | NULL if scope=global |
| venue_id | BIGINT UNSIGNED NULL FK → venues CASCADE | NULL if scope!=venue |
| commission_type | ENUM('fixed','percentage') | fixed = SYP amount; percentage = basis points (500 = 5.00%) |
| commission_value | INT UNSIGNED NOT NULL DEFAULT 0 | |
-- apply_as removed. Commission is always deducted from club. |
| cancellation_fee | INT UNSIGNED NOT NULL DEFAULT 0 | Fixed SYP platform keeps on cancellation |
| is_active | TINYINT(1) | |
| effective_from | DATE NOT NULL | |
| note | TEXT NULL | |
| created_by | BIGINT UNSIGNED NULL FK → users SET NULL | |

**Resolution order (most specific wins):**
1. venue-level: `scope='venue' AND venue_id=X AND is_active=1 ORDER BY effective_from DESC LIMIT 1`
2. club-level: `scope='club' AND club_id=X AND is_active=1 ORDER BY effective_from DESC LIMIT 1`
3. global: `scope='global' AND is_active=1 ORDER BY effective_from DESC LIMIT 1`

---

## 5.16 settlements

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| club_id | BIGINT UNSIGNED FK → clubs RESTRICT | |
| period_from | DATE NOT NULL | |
| period_to | DATE NOT NULL | |
| total_bookings | INT UNSIGNED | Count of included bookings |
| total_venue_price | INT UNSIGNED | SUM(venue_price) |
| total_commission | INT UNSIGNED | SUM(commission_amount) — platform's cut |
| total_cancellation_fees | INT UNSIGNED | SUM(cancellation_commission) |
| net_payable | INT UNSIGNED | SUM(club_payout_amount) — what platform owes club |
| paid_amount | INT UNSIGNED DEFAULT 0 | Actual amount transferred |
| status | ENUM('draft','pending','completed','cancelled') | Default: draft |
| payment_method | VARCHAR(100) NULL | "bank_transfer", "cash" |
| payment_reference | VARCHAR(255) NULL | Bank receipt, transfer ID |
| note | TEXT NULL | |
| settled_by | BIGINT UNSIGNED NULL FK → users SET NULL | |
| settled_at | TIMESTAMP NULL | |
| created_by | BIGINT UNSIGNED NULL FK → users SET NULL | |

---

## 5.17 settlement_items

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| settlement_id | BIGINT UNSIGNED FK → settlements CASCADE | |
| booking_id | BIGINT UNSIGNED FK → bookings RESTRICT | |
| venue_price | INT UNSIGNED | Snapshot |
| commission_amount | INT UNSIGNED | Snapshot |
| club_payout_amount | INT UNSIGNED | Snapshot |
| cancellation_comm | INT UNSIGNED DEFAULT 0 | |
| UNIQUE(settlement_id, booking_id) | | Prevents double-counting |

---

## 5.18 reviews

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| user_id | BIGINT UNSIGNED FK → users RESTRICT | |
| club_id | BIGINT UNSIGNED FK → clubs RESTRICT | Reviews target clubs, not venues |
| booking_id | BIGINT UNSIGNED FK → bookings RESTRICT | Eligibility proof |
| rating | DECIMAL(2,1) NOT NULL | 1.0–5.0 |
| body | TEXT NULL | Min 50 chars if provided |
| is_anonymous | TINYINT(1) DEFAULT 0 | Hides name; shows venue_hint |
| venue_hint | VARCHAR(255) NULL | Venue name snapshot — shown even when anonymous |
| is_published | TINYINT(1) DEFAULT 1 | Admin can hide |
| hidden_at | TIMESTAMP NULL | |
| hidden_by | BIGINT UNSIGNED NULL FK → users SET NULL | |
| UNIQUE(user_id, club_id) | | One review per player per club |

---

## 5.19 saved_venues

| Field | Type |
|-------|------|
| id | BIGINT UNSIGNED PK |
| user_id | BIGINT UNSIGNED FK → users CASCADE |
| venue_id | BIGINT UNSIGNED FK → venues CASCADE |
| UNIQUE(user_id, venue_id) | |

---

## 5.20 otp_challenges

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| uuid | CHAR(36) UNIQUE | Used as `verification_request_id` externally |
| phone_number | VARCHAR(20) NOT NULL | |
| code_hash | VARCHAR(255) NOT NULL | SHA-256 hash — never plain text |
| channel | ENUM('sms','whatsapp') DEFAULT 'sms' | |
| expires_at | TIMESTAMP NOT NULL | |
| consumed_at | TIMESTAMP NULL | |
| attempts_count | TINYINT UNSIGNED DEFAULT 0 | |
| resend_count | TINYINT UNSIGNED DEFAULT 0 | |
| delivery_status | ENUM('pending','sent','failed') DEFAULT 'pending' | |
| ip_address | VARCHAR(45) NULL | |

Append-only. No `updated_at`. Scheduler cleans records > 24h old.

---

## 5.21 social_identities

| Field | Type | Notes |
|-------|------|-------|
| id | BIGINT UNSIGNED PK | |
| user_id | BIGINT UNSIGNED FK → users CASCADE | |
| provider | VARCHAR(50) | 'google' |
| provider_uid | VARCHAR(255) | |
| provider_email | VARCHAR(255) NULL | |
| provider_meta | JSON NULL | Raw claims snapshot |
| UNIQUE(provider, provider_uid) | | |

---

## 5.22 app_platforms + app_environments

`app_platforms`: platform_key (unique slug), name (JSON), latest_version, minimum_required_version, store_url, direct_apk_url (nullable), direct_apk_enabled, is_active, order_column, last_updated_by.

`app_environments`: platform_id FK, name, base_url, is_active. Only one active environment per platform (application-enforced).

---

## 5.23 content_pages

slug (unique), title (JSON), body (JSON — rich HTML), is_active.
Slugs: `about`, `privacy`, `terms`, `help`.

---

## 5.24 competitions

Admin-managed. title (JSON), description (JSON), start_date, end_date, is_published.
Media: single `image` collection via medialibrary.

---

## 5.25 sport_categories

**EVENTS TAB ONLY.** Not used for venue categorization.
id, name (JSON), slug (unique), order_column, is_active.
Used exclusively in `GET /api/v1/events/matches/today` response for league display.

---

# 6. Canonical Relationships

| From | To | Type | FK Behavior | Notes |
|------|----|------|-------------|-------|
| areas | cities | many → one | RESTRICT | Area cannot exist without city |
| clubs | areas | many → one | RESTRICT | Club must have area |
| club_user | clubs | many → one | CASCADE | Staff assignments deleted when club deleted |
| club_user | users | many → one | CASCADE | Assignments deleted when user deleted |
| venues | clubs | many → one | CASCADE | Venues deleted when club deleted |
| venues | venue_categories | many → one | SET NULL | Nullable category |
| venue_pricing_tiers | venues | many → one | CASCADE | |
| bookings | users | many → one | SET NULL | Preserved if user soft-deleted |
| bookings | venues | many → one | RESTRICT | Cannot delete venue with bookings |
| bookings | venue_categories | many → one | SET NULL | Snapshot |
| bookings | bookings (parent) | many → one (self) | SET NULL | Recurring series |
| slot_reservations | venues | many → one | CASCADE | |
| slot_reservations | users | many → one | CASCADE | |
| payments | bookings | many → one | RESTRICT | |
| payments | users | many → one | RESTRICT | |
| wallets | users | one → one | CASCADE | |
| wallet_transactions | wallets | many → one | RESTRICT | Append-only |
| reviews | users | many → one | RESTRICT | |
| reviews | clubs | many → one | RESTRICT | |
| reviews | bookings | many → one | RESTRICT | |
| saved_venues | users + venues | pivot | CASCADE both | |
| commission_configs | clubs | many → one | CASCADE | NULL if global |
| commission_configs | venues | many → one | CASCADE | NULL if not venue-scoped |
| settlements | clubs | many → one | RESTRICT | |
| settlement_items | settlements | many → one | CASCADE | |
| settlement_items | bookings | many → one | RESTRICT | |
| social_identities | users | many → one | CASCADE | |
| otp_challenges | (standalone) | — | — | Not FK to users |
| app_environments | app_platforms | many → one | CASCADE | |

---

# 7. Status and Enum Registry

## Booking Status

| Value | Meaning | Entry Condition | Exit Condition |
|-------|---------|----------------|----------------|
| `scheduled` | Recurring booking (Phase 2 only) — not created by mobile API in Phase 1 | Phase 2 | Phase 2 |
| `confirmed` | Paid and confirmed | Payment completed successfully | → `completed` (auto after booking time) OR → `cancelled` |
| `cancelled` | Cancelled by player or admin | Cancellation API called | Terminal |
| `completed` | Attended — booking time passed | Scheduled job runs after `ends_at` | Terminal |
| `no_show` | Player did not attend | Admin marks manually (or scheduled job) | Terminal |
| `failed` | Payment failed, slot not booked | Payment failure | Terminal (no booking row if failed at initiation) |

**No `pending` status.** Booking row is created only after payment succeeds (or for scheduled bookings).

---

## Payment Status

| Value | Meaning |
|-------|---------|
| `pending` | Payment record created, initiation started |
| `processing` | OTP sent to player / WebView opened |
| `completed` | Payment confirmed by provider |
| `failed` | Provider rejected payment |
| `refunded` | Wallet credit issued on booking cancellation |
| `cancelled` | Slot reservation expired before player completed payment |

---

## Club Status

| Value | Meaning |
|-------|---------|
| `pending_approval` | Submitted, awaiting Super Admin review |
| `active` | Live on platform |
| `inactive` | Deactivated by club or admin |
| `suspended` | Temporarily blocked by admin |
| `rejected` | Admin rejected with reason |

---

## Venue Status

| Value | Meaning |
|-------|---------|
| `active` | Bookable by players |
| `inactive` | Not shown to players |
| `suspended` | Blocked by admin |

---

## Settlement Status

| Value | Meaning |
|-------|---------|
| `draft` | Created, not yet reviewed |
| `pending` | Reviewed, awaiting payment transfer |
| `completed` | Paid, reference recorded |
| `cancelled` | Voided |

---

## Account Status (users)

| Value | Meaning |
|-------|---------|
| `pending_profile_completion` | Newly registered, profile incomplete |
| `active` | Normal account |
| `blocked` | Cannot login or book |
| `suspended` | Temporary restriction |

---

## OTP Channel

`sms` | `whatsapp`

## OTP Delivery Status

`pending` | `sent` | `failed`

## Commission Scope

`global` | `club` | `venue`

## Commission Type

`fixed` | `percentage`

## Commission Apply Mode

`added` | `deducted`

## Venue Category Type

`sports` | `hall` | `court` | `outdoor` | `other`

## Booking Source

`mobile` | `manual`

## Manual Booking Type

`external` | `blocked`

## FCM Platform

`mobile` | `web`

## Payment Provider

`syriatel_cash` | `mtn_cash` | `fatora` | `sama_pay` | `wallet`

## Payment Flow Type

`otp` | `webview` | `internal`

---

# 8. Lifecycle Specifications

## 8.1 Booking Lifecycle

```
[Player initiates payment]
       ↓
slot_reservations INSERT (UNIQUE guard — fails if slot taken)
       ↓
payments INSERT (status=pending)
       ↓
[OTP flow] → player enters OTP → POST /payments/{id}/confirm
[WebView flow] → Fatora/SamaPay callback → POST /payments/callback/{provider}
[Wallet flow] → instant internal debit
       ↓
[Payment confirmed]
DB TRANSACTION:
  - UPDATE payments SET status=completed
  - INSERT bookings (status=confirmed, all price snapshots)
  - DELETE slot_reservations
COMMIT
       ↓
FCM → player + super admin + club admins (via club_user pivot)
       ↓
[Day of booking] Scheduler marks status=completed or no_show
       ↓
[Reminders] 2h before + 1h before → FCM if reminders_enabled
```

## 8.2 Recurring Booking Lifecycle

```
Player selects slot + recurrence pattern (weekly/X occurrences)
       ↓
System creates N booking records with status=scheduled (slots are reserved)
       ↓
Scheduler (daily): finds scheduled bookings with payment due → sends FCM reminder
       ↓
Player pays → that occurrence becomes confirmed (normal lifecycle)
       ↓
Scheduler: if payment_window expired and status=scheduled → auto-cancel FREE (no wallet deduction)
```

## 8.3 Cancellation + Refund Lifecycle

```
Player taps Cancel
       ↓
GET /bookings/{id}/cancel/check
  → If starts_at - NOW() < 30 min → return CANCELLATION_NOT_ALLOWED
  → Else → return {can_cancel: true, refund_amount, deduction_amount}
       ↓
POST /bookings/{id}/cancel {confirmed: true}
  → Validate: confirmed = true
  → BEGIN TRANSACTION
    - UPDATE bookings SET status=cancelled, cancelled_at, cancellation_reason
    - Calculate refund = total_price - cancellation_deduction (from BookingSettings)
    - INSERT wallet_transactions (credit, reason=booking_refund)
    - UPDATE wallets SET balance = balance + refund (with FOR UPDATE lock)
    - UPDATE payments SET status=refunded
  - COMMIT
       ↓
Dispatch VenueAvailableNotificationJob (async)
  → Find: saved_venues WHERE venue_id=X + users in same area + matching category
  → FCM: "ملعب X أصبح متاحاً — احجز الآن!"
```

**Unpaid scheduled booking cancellation:** No confirmation phrase required. No deduction. Free cancellation.

## 8.4 Club Onboarding Lifecycle

```
Super Admin creates club → status=pending_approval
       ↓
Super Admin reviews (manual phone contact) → Approve or Reject
       ↓
[Approved] status=active → club visible on mobile app
[Rejected] status=rejected + rejection_reason → SMS/notification to owner
       ↓
Super Admin creates club staff accounts → assigns via club_user pivot
```

## 8.5 Settlement Lifecycle

```
[Admin navigates to club settlements]
GET /admin/clubs/{id}/settlements/preview?from=&to=
  → SELECT unsettled confirmed+completed bookings (not in settlement_items)
  → Compute: total_venue_price, total_commission, net_payable
       ↓
Admin confirms → POST /admin/settlements
BEGIN TRANSACTION
  - INSERT settlements (status=draft, computed totals)
  - INSERT settlement_items (one per booking)
COMMIT
       ↓
Admin reviews → PATCH → status=pending
       ↓
Admin transfers money offline → enters reference → PATCH → status=completed, settled_at=NOW()
       ↓
Club Dashboard shows settlement as completed
```

## 8.6 Player Auth Lifecycle

```
[OTP Path]
POST /api/v1/auth/otp/request {phone_number}
  → Check Baileys: does phone have WhatsApp?
  → If yes: return {whatsapp_available: true, verification_request_id (uuid)}
  → If no: send SMS immediately, return {whatsapp_available: false, uuid}
       ↓
[If whatsapp_available=true] Player chooses channel
POST /api/v1/auth/otp/choose-channel {uuid, channel}
  → Dispatch OTP via chosen channel
       ↓
Player enters 5-digit OTP
POST /api/v1/auth/otp/verify {uuid, otp_code}
  → SHA-256(otp_code) == code_hash? + not expired + attempts < max
  → Issue Sanctum token (30 days)
  → Return {access_token, auth_outcome: login|registration, next_step}

[Google Path]
Mobile → Firebase SDK → Firebase ID token
POST /api/v1/auth/google {firebase_token}
  → FirebaseAuthService.verify(token) via Google JWKS
  → Extract uid, email, name, phone_number (if available)
  → If phone_number matches existing OTP user → return {action: link_required}
  → Else: create or update user → issue Sanctum token

[Account Linking]
POST /api/v1/auth/link {uuid, otp_code}
  → Verify OTP
  → Merge Google identity into existing phone account
  → Single account with both methods
```

---

# 9. Booking Rules Engine

## Availability Model

Slots are computed on-the-fly. No `slots` table.

**Algorithm for GET /api/v1/clubs/{id}/venues/{vid}/slots?date=&duration=:**

```
1. Parse venue.opening_hours → get open intervals for requested date
2. Generate candidate slots: opening_time to closing_time, step = duration_minutes
3. Exclude slots where any of:
   a. bookings exists WHERE venue_id=X AND booking_date=date AND status NOT IN (cancelled, failed)
      AND start_time < slot_end AND end_time > slot_start
   b. slot_reservations exists WHERE venue_id=X AND booking_date=date
      AND start_time = slot_start AND reserved_until > NOW()
4. For each remaining slot: find matching pricing tier (day_type + time range + duration)
5. If no tier matches → slot not bookable (omit from response)
6. Return: [{start_time, end_time, price, available: true}]
```

## Conflict Prevention

- `slot_reservations` has `UNIQUE(venue_id, booking_date, start_time)` — DB-level guard
- Booking creation runs inside `DB::transaction()` with `SELECT FOR UPDATE` on booking overlap check
- Payment confirmation runs inside `DB::transaction()`

## Cancellation Rules

- Allowed any time EXCEPT when `starts_at - NOW() < cancellation_min_minutes_before` (default: 30)
- Cancellation requires `{confirmed: true}` in request body. No phrase input required.
- Unpaid scheduled bookings (Phase 2 only): free cancellation
- `cancellation_min_minutes_before` configurable in BookingSettings

## Booking Validation

- Requested duration must be in `SELECT DISTINCT duration_minutes FROM venue_pricing_tiers WHERE venue_id=? AND is_active=1`
- Booking date must be in the future
- User must have verified phone_number (`PHONE_REQUIRED` if null)
- Venue status must be `active`
- Club status must be `active`

---

# 10. Payments and Financial Logic

## 10.1 Commission Calculation

On payment initiation, before creating the payment record:

```php
$config = CommissionService::resolveConfig($venue->id); // venue → club → global

// Commission is ALWAYS deducted from club. Player always pays venue_price.
// apply_as is not configurable — it is hardcoded as 'deducted'.

// commission_type = 'fixed':
//   commission_amount = $config->commission_value  (SYP amount)

// commission_type = 'percentage':
//   commission_amount = round($tier->price * $config->commission_value / 10000)
//   (commission_value = 700 means 7.00%)

// Result (always):
//   total_price         = $tier->price               // player pays full venue price
//   commission_amount   = calculated above
//   club_payout_amount  = $tier->price - commission_amount
//   commission_type     = $config->commission_type   // 'fixed' | 'percentage' (snapshot)
```

All amounts stored as snapshots on `bookings.*` at creation. Never recalculated.

**Phase 1 constraint:** `commission_configs.scope = 'global'` only. No per-club or per-venue overrides.
**Phase 1 default:** 7% percentage. Admin can adjust global rate from Dashboard.

## 10.2 Payment Providers

### MTN Cash (OTP, 3-step)
1. `createInvoice(invoiceId, amount)` → MTN returns invoiceId
2. `initiatePayment(invoiceId, phone)` → MTN sends OTP, returns {guid, operationNumber}
3. Player enters OTP → `confirmPayment(guid, base64(sha256(otp)), phone, invoiceId, operationNumber)`
- Auth: RSA X-Signature header using `storage/keys/private.pem`
- Base URL: `https://cashmobile.mtnsyr.com:9000`
- SSL verify: false (MTN self-signed cert)

### Syriatel Cash (OTP, 2-step)
1. `paymentRequest(msisdn, amount, transactionId)` → Syriatel sends OTP
2. `paymentConfirmation(otp, transactionId)` → confirms
- Auth: Token-based. `getToken()` cached 3 minutes.
- Success check: `errorCode == 0 AND errorDesc == 'Success'`

### Fatora / SamaPay (WebView)
1. Backend builds signed payload → returns hosted page URL to mobile
2. Mobile opens WebView
3. User completes payment on Fatora page
4. Fatora POSTs to `callBackUrl` → backend verifies → responds `{"responseCode":"OK"}`
5. Mobile polls `GET /api/v1/payments/{id}/status` every 3 seconds (max 2 minutes)
- Idempotency: check `provider_transaction_id` before processing callback
- Callback security: INFERENCE — re-query Fatora on callback receipt before trusting payload (Fatora docs must confirm signature method)

### Wallet (Internal)
- `WalletService::debit()` with `SELECT FOR UPDATE`
- No external API calls
- flow_type = 'internal'

## 10.3 Deposit Model

Players choose at checkout: full payment or deposit.

**Full payment:** Player pays `total_price` via any provider. `deposit_amount = 0`, `deposit_status = 'none'`, `remaining_amount = 0`.

**Deposit payment:** Player pays a portion electronically. Remaining is paid in cash directly to the club upon arrival — the platform does not collect or intermediate the remaining amount.

```
deposit_amount    = chosen_amount (e.g. 30% of total_price)
deposit_status    = 'paid'  (after electronic payment confirms)
remaining_amount  = total_price - deposit_amount
remaining_status  = 'due_on_arrival'
```

When player arrives and pays club directly:
- Club Admin confirms in Dashboard: `PATCH /api/club/v1/bookings/{id}/remaining/confirm`
- `remaining_status` → `'confirmed'`, `remaining_confirmed_at` = NOW()

**Commission on deposit bookings:**
- `commission_amount` = calculated on `deposit_amount` only (the electronically paid portion)
- `club_payout_amount` = `deposit_amount - commission_amount`
- Remaining cash is outside the settlement system — it goes directly to the club

**Cancellation of a deposit booking:**
- If cancelled before 30-min window: `deposit_amount - cancellation_deduction` refunded to wallet
- `remaining_amount` is not touched (was never collected)
- `remaining_status` → `'waived'`

## 10.4 Refund Model

Syrian payment providers do not support refunds. All refunds go to internal wallet.

**Full payment cancellation:** Refund = `total_price - cancellation_deduction`
**Deposit cancellation:** Refund = `deposit_amount - cancellation_deduction`
- `cancellation_deduction` = Admin-configured (BookingSettings): percentage or flat of paid amount
- `cancellation_commission` = platform's portion of the deduction (snapshot on bookings)

Wallet credit transaction: reason=`booking_refund`, reference=booking_id.

## 10.5 Settlement Rules

- Only bookings with status `confirmed` or `completed` are included
- Only bookings NOT already in a `settlement_items` record
- `net_payable` = SUM(club_payout_amount) for included bookings
- Admin can mark settlement `completed` only after entering payment_reference
- Club Dashboard sees settlements read-only

---

# 11. Dashboard Boundaries

## 11.A Super Admin Dashboard (`/admin/*`)

**URL prefix:** `/admin/`
**Auth:** email + password → 2FA TOTP (mandatory)
**Framework:** Laravel Inertia.js + Vue 3 + Tailwind CSS 4 + shadcn-vue
**PWA:** Yes

**Modules:**

| Module | Key Actions |
|--------|-------------|
| App Startup | Manage platforms, environments, versions |
| Users | View/block/suspend players, create dashboard users, assign clubs |
| Geography | Activate/deactivate countries, states, cities from pre-seeded dr5hn data |
| Venue Categories | CRUD venue_categories |
| Clubs | Approval queue, CRUD, feature/unfeature, suspend |
| Venues | View all, edit any, suspend |
| Bookings | View all, filter, cancel, override status, export |
| Payments | View all, filter by provider/status, view raw payload |
| Commission | Set global/club/venue commission configs |
| Settlements | Generate, review, mark paid |
| Revenue Reports | Platform gross / commission / net by period |
| Reviews | Publish/hide, view reviewer + booking |
| Content Pages | Edit About, Privacy, Terms, Help (AR + EN) |
| Competitions | CRUD, publish/archive |
| Sport Categories | CRUD for Events tab only |
| Settings | OTP config, booking rules, cancellation policy, payment toggles, SMS provider switch |
| Roles & Permissions | Create roles, assign permissions, assign roles to users |

---

## 11.B Club Dashboard (`/club/*`)

**URL prefix:** `/club/`
**Auth:** email + password (no 2FA)
**Framework:** Same stack as admin
**PWA:** Yes (primary use case — Club Admin on mobile)
**Scope:** Only clubs assigned via `club_user` pivot

**Modules:**

| Module | Available to | Key Actions |
|--------|-------------|-------------|
| Club Profile | Club Admin | Edit info, images, location |
| Venues | Club Admin, Data Entry | CRUD, images, opening hours, pricing |
| Calendar | Club Admin, Data Entry | View all bookings, add manual/blocked |
| Bookings | Club Admin | View detail, cancel player booking |
| Staff | Club Admin | Invite, remove, change roles |
| Revenue | Club Admin, Club Owner | Booking counts, earnings, occupancy |
| Settlements | Club Admin, Club Owner | View history, pending balance |
| Commission | All (read-only) | View per-booking commission breakdown |

---

# 12. Player App Flows (API Surface)

## 12.1 Authentication

```
App launch → POST /api/v1/app/startup
  → Maintenance mode? → Block app
  → Update required? → Force update screen

[OTP]:
POST /api/v1/auth/otp/request {phone_number}
  → response: {uuid, whatsapp_available, expires_in_seconds, resend_after_seconds, masked_destination}
  → If whatsapp_available: POST /api/v1/auth/otp/choose-channel {uuid, channel}
POST /api/v1/auth/otp/verify {uuid, otp_code}
POST /api/v1/auth/otp/resend {uuid}

[Google]:
POST /api/v1/auth/google {firebase_token}
  → If link_required: → OTP flow to link
POST /api/v1/auth/link {uuid, otp_code}

[Logout]:
POST /api/v1/auth/logout
  → Delete Sanctum token + SET fcm_token = NULL
```

## 12.2 Onboarding

```
POST /api/v1/me (PUT) — name, email?, default_city_id, default_area_id, avatar?
GET /api/v1/geo/countries          → active countries
GET /api/v1/geo/countries/{id}/states → active states/provinces
GET /api/v1/geo/states/{id}/cities    → active cities in state
```

## 12.3 Discovery

```
GET /api/v1/categories           → venue_categories list (active, ordered)
GET /api/v1/clubs                → filtered: area_id, category_id, q, price_min, price_max, rating_min, sort
GET /api/v1/clubs/price-range    → {min_price, max_price} for filter slider
GET /api/v1/clubs/{id}           → club detail + venues list
GET /api/v1/clubs/{id}/venues/{vid}         → venue detail
GET /api/v1/clubs/{id}/venues/{vid}/slots   → ?date=&duration= → available slots with prices
```

## 12.4 Booking + Payment

```
POST /api/v1/payments/initiate
  Body: {
    venue_id, booking_date, start_time, duration_minutes, provider,
    payment_mode: 'full' | 'deposit',     // default: 'full'
    deposit_amount: integer               // required if payment_mode='deposit'
  }
  Response: {payment_id, flow_type: otp|webview|internal, amount, deposit_mode: bool, ...provider-specific}

[OTP providers]:
POST /api/v1/payments/{id}/confirm {otp_code}
POST /api/v1/payments/{id}/resend-otp

[WebView providers]:
Mobile opens hosted_url in WebView
GET /api/v1/payments/{id}/status  → poll until completed or failed

[Wallet]:
POST /api/v1/payments/initiate {provider: wallet} → immediate internal processing
```

## 12.5 Booking Management

```
GET /api/v1/bookings                 → ?tab=upcoming|history|scheduled
POST /api/v1/bookings/{id}/cancel/check
POST /api/v1/bookings/{id}/cancel    {confirm_phrase}
```

## 12.6 Profile & Preferences

```
GET  /api/v1/me
PUT  /api/v1/me
GET  /api/v1/me/settings
PUT  /api/v1/me/settings
POST /api/v1/me/phone/request        → OTP to new number
POST /api/v1/me/phone/verify
GET  /api/v1/me/wallet
GET  /api/v1/me/wallet/transactions
GET  /api/v1/me/grounds              → saved + recent venues
POST /api/v1/saved-venues            {venue_id}
DELETE /api/v1/saved-venues/{venue_id}
```

## 12.7 Reviews

```
POST /api/v1/clubs/{id}/reviews    {rating, body?, is_anonymous}
GET  /api/v1/clubs/{id}/reviews    → paginated
```

## 12.8 Events

```
GET /api/v1/events/competitions
GET /api/v1/events/matches/today       → backend proxy to football-data.org, cached 5 min
GET /api/v1/events/matches/upcoming    → cached 30 min
```

## 12.9 Content

```
GET /api/v1/content-pages/{slug}    → about, privacy, terms, help
GET /api/v1/payment-methods         → active payment methods for display
```

---

# 13. Search, Discovery, and Geo Rules

## Geographic Filtering

**الهرمية:** `countries` → `states` (محافظات) → `cities` (مدن/مناطق)

- Default scope: user's `default_city_id` (المدينة/المنطقة الدقيقة)
- إذا لم تُحدَّد: scope to user's `default_state_id` (المحافظة)
- إذا لم تُحدَّد: prompt user to set location
- User can override من profile أو location picker
- كل بيانات الـ Geography مسبقاً seeded من dr5hn — Admin يُفعّل ويوقف فقط من Dashboard

## Search Dimensions (`q` param)

MySQL FULLTEXT search across:
- `clubs.name` (JSON → both ar + en)
- `venues.name` (JSON → both ar + en)
- `areas.name` (JSON)
- `venue_categories.name` (JSON)

Results carry `match_type: 'club' | 'venue' | 'area' | 'category'`.

## Filters (GET /api/v1/clubs)

| Param | Type | Source |
|-------|------|--------|
| `q` | string | Full-text search |
| `area_id` | integer | Filter by area |
| `city_id` | integer | Widen to city |
| `category_id` | integer | venue_categories.id |
| `price_min` | integer | SYP — from venue pricing tiers |
| `price_max` | integer | SYP |
| `rating_min` | decimal | clubs.avg_rating |
| `sort` | string | distance\|rating\|price_asc\|price_desc |
| `lat` / `lng` | decimal | For distance sort (Haversine) |
| `page` / `per_page` | integer | Pagination |

## My Grounds Logic

`GET /api/v1/me/grounds` returns two merged lists:

1. **Saved:** `saved_venues` for current user → venue detail
2. **Recent:** bookings WHERE user_id=current AND status=completed AND (no review OR review.rating >= `my_grounds_min_rating` setting, default 3.0) → distinct venues, ordered by most recent booking

Merge: if venue appears in both → include once with `type: 'saved'`. Saved takes priority over recent.

---

# 14. API Contract Guidelines

## Auth Model

- Mobile: `Authorization: Bearer {sanctum_token}` on `api` guard
- Dashboard (Inertia): session-based on `web` guard; no Bearer token for dashboard UI
- Dashboard club API (`/api/club/v1/`): `Authorization: Bearer {sanctum_token}` if consumed from mobile-style client; otherwise session

## URL Structure

```
Public mobile:     /api/v1/{resource}
Authenticated:     /api/v1/{resource}  (same, with auth middleware)
Club dashboard:    /api/club/v1/{resource}
Admin dashboard:   /api/admin/v1/{resource}
Webhooks:          /api/v1/payments/callback/{provider}  (public, idempotent)
```

## Response Envelope

**Success:**
```json
{
  "success": true,
  "data": { ... } | [ ... ],
  "meta": { "current_page": 1, "last_page": 5, "total": 47, "per_page": 15 }
}
```

**Error:**
```json
{
  "success": false,
  "error": {
    "code": "SNAKE_CASE_CODE",
    "message": "Human readable, localized",
    "details": {}
  }
}
```

## Canonical Error Codes

| Code | When |
|------|------|
| `PHONE_REQUIRED` | Player has no phone but attempted booking |
| `OTP_INVALID` | Wrong OTP code |
| `OTP_EXPIRED` | OTP past expiry |
| `OTP_MAX_ATTEMPTS` | Too many failed attempts |
| `OTP_LOCKED` | Account locked after max attempts |
| `REQUEST_NOT_FOUND` | UUID not found or already consumed |
| `SLOT_UNAVAILABLE` | Slot was taken between check and initiation |
| `SLOT_RESERVATION_EXPIRED` | 10-min lock expired during payment |
| `PAYMENT_FAILED` | Provider rejected |
| `CANCELLATION_NOT_ALLOWED` | Within 30-min window |
| `INSUFFICIENT_WALLET_BALANCE` | Wallet payment with insufficient funds |
| `BOOKING_NOT_CANCELLABLE` | Status does not allow cancellation |
| `PHONE_ALREADY_EXISTS` | Phone number belongs to another account |

## Pagination

Default: `per_page=15`. Max: `per_page=50`. Always included in `meta` for list responses.

## Idempotency — Sensitive Operations

- Fatora/SamaPay callback: check `payments.provider_transaction_id` before processing. If already `completed` → return OK without re-processing.
- Payment initiation: if active `slot_reservation` already exists for this user+venue+date+time → return existing payment_id
- Booking creation: wrapped in `DB::transaction()` with conflict detection

---

# 15. Data Integrity and Consistency Rules

## Unique Constraints

| Table | Constraint |
|-------|-----------|
| users | UNIQUE(email), UNIQUE(phone_number), UNIQUE(firebase_uid) |
| social_identities | UNIQUE(provider, provider_uid) |
| otp_challenges | UNIQUE(uuid) |
| sport_categories | UNIQUE(slug) |
| venue_categories | UNIQUE(slug) |
| clubs | UNIQUE(slug) |
| club_user | UNIQUE(club_id, user_id) |
| slot_reservations | UNIQUE(venue_id, booking_date, start_time) |
| bookings | UNIQUE(booking_code) |
| wallets | UNIQUE(user_id) |
| reviews | UNIQUE(user_id, club_id) |
| saved_venues | UNIQUE(user_id, venue_id) |
| payment_methods | UNIQUE(provider_key) |
| app_platforms | UNIQUE(platform_key) |
| settlement_items | UNIQUE(settlement_id, booking_id) |

## Race-Condition Sensitive Operations

| Operation | Protection |
|-----------|-----------|
| Booking creation | DB transaction + overlap query FOR UPDATE |
| Slot reservation | UNIQUE constraint on (venue_id, date, start_time) |
| Wallet debit | SELECT balance FOR UPDATE before debit |
| Payment callback | provider_transaction_id idempotency check |
| Settlement generation | UNIQUE(settlement_id, booking_id) prevents double-count |

## Soft Delete

Applied to: `users`, `clubs`, `venues`. Not applied to: `bookings` (status=cancelled serves this), `payments`, `wallet_transactions` (append-only).

## Audit

All dashboard actions on bookings, clubs, venues, users, settlements, commission_configs recorded via `spatie/laravel-activitylog`.

---

# 16. Background Jobs, Queue, Cache, and Scheduled Tasks

## Queue Driver: Database (no Redis)

All jobs dispatched to the `jobs` table. `failed_jobs` table for failures. No Redis.

## Cache Driver: File

`config/cache.php` → driver: file. Football API responses cached with 5-minute TTL. Syriatel Cash token cached 3 minutes. No distributed cache.

## Scheduled Tasks (Laravel Scheduler)

| Task | Frequency | Description |
|------|-----------|-------------|
| `reservations:expire` | Every 1 minute | DELETE slot_reservations WHERE reserved_until < NOW() |
| `bookings:complete` | Every 15 minutes | UPDATE bookings SET status=completed WHERE ends_at < NOW() AND status=confirmed |
| `bookings:remind` | Every 15 minutes | Find bookings starting in ~2h and ~1h → dispatch FCM reminder jobs |
| `scheduled-bookings:remind` | Daily | Find upcoming scheduled (unpaid) bookings → send payment reminder FCM |
| `scheduled-bookings:expire` | Daily | Cancel unpaid scheduled bookings past payment_window |
| `otp:cleanup` | Daily | DELETE otp_challenges WHERE consumed_at IS NOT NULL AND created_at < NOW() - 24h |
| `backup:run` | Daily 2am | spatie/laravel-backup — DB + files |

## Queue Jobs

| Job | Trigger | Payload |
|-----|---------|---------|
| `BookingConfirmedNotificationJob` | Payment confirmed | booking_id |
| `BookingCancelledNotificationJob` | Cancellation | booking_id |
| `VenueAvailableNotificationJob` | Cancellation | venue_id, date, start_time, price |
| `BookingReminderJob` | Scheduler | booking_id, type(2h|1h) |
| `ScheduledPaymentDueJob` | Scheduler | booking_id |
| `UpdateClubRatingJob` | ReviewObserver | club_id |
| `UpdateClubPriceFromJob` | PricingTierObserver | club_id |

---

# 17. Security and Compliance

## Authentication Model

- Players: Sanctum token (30-day TTL). OTP hash = SHA-256. Never plain text.
- Dashboard users: Session-based (Inertia). Super Admin requires TOTP.
- TOTP: Custom TotpService (RFC 6238, PHP native). 6-digit, 30-second window, ±1 clock tolerance.
- Google ID tokens: Verified against Google JWKS endpoint. No kreait package.

## Authorization

- All routes protected by Spatie Permission middleware (`permission:{guard}.{resource}.{action}`)
- Club scoping: club staff can only access clubs in `club_user` pivot
- Super Admin role has all permissions assigned at seed time

## Sensitive Data

- `payments.provider_meta`: Laravel `encrypted` cast (AES-256-CBC)
- `users.google2fa_secret`: Laravel `encrypted` cast
- `users.password`: Laravel `hashed` cast
- OTP codes: never stored plain text — SHA-256 hash only

## Rate Limiting

- OTP request: per phone + per IP (configurable via settings)
- OTP verify: max 5 attempts per challenge (configurable)
- OTP resend: max 3 resends per challenge, 60-second cooldown (configurable)
- Dashboard login: standard Laravel throttle middleware

## FCM Token Management

- Token stored in `users.fcm_token`
- Cleared (set to NULL) on any logout (mobile or dashboard)
- Updated on dashboard login (PWA web push registration)
- Jobs skip users where `fcm_token IS NULL`

---

# 18. Observability and Operations

## Critical Failure Points

| Failure | Impact | Mitigation |
|---------|--------|-----------|
| `app/startup` down | App cannot launch | Health check + uptime monitor on this endpoint |
| Slot reservation race | Double booking | UNIQUE constraint + transaction. Alert on constraint violation. |
| Wallet balance mismatch | Financial inconsistency | Weekly `wallet:reconcile` command: compare balance vs SUM(transactions) |
| Payment callback not received | Booking stuck pending | Scheduler cleans expired slot_reservations. Player can retry. |
| Baileys service down | WhatsApp OTP unavailable | Automatic fallback to SMS. Log Baileys errors. |
| Queue job failure | Notification missed | `failed_jobs` table. Monitor failed_jobs count. Retry policy. |

## Audit Trail

All admin dashboard mutations are logged via `spatie/laravel-activitylog` on:
- bookings (status changes, cancellations)
- clubs (status changes, approvals)
- venues (any change)
- commission_configs (any change)
- settlements (creation, status changes)
- users (block/suspend/create)
- roles and permissions

## Admin Action Traceability

`settlements.settled_by`, `settlements.created_by`, `bookings.cancelled_by`, `clubs.approved_by`, `commission_configs.created_by`, `wallet_transactions.created_by` — all FK to users.

---

# 19. Final Canonical Technical Decisions

| Topic | Decision |
|-------|---------|
| Database | MySQL 8.0+ / utf8mb4_unicode_ci / InnoDB |
| Cache | Laravel File Cache (no Redis) |
| Queue | Laravel Database Queue (no Redis) |
| File Storage | Local — `storage/app/public` via symlink |
| Backend | Laravel 13 (monorepo) |
| Dashboard Frontend | Inertia.js + Vue 3 + Tailwind CSS 4 + shadcn-vue |
| Mobile | Flutter (consumed by separate team via API) |
| PWA | Dashboards only (/admin + /club). Not for players. |
| Maps (Dashboard) | Leaflet.js + OpenStreetMap (no API key) |
| Maps (Mobile) | Google Maps SDK (mobile team concern) |
| Auth (Players) | Laravel Sanctum API guard. OTP + Google. No email/password. |
| Auth (Dashboard) | Session-based web guard. Email + password. Super Admin + 2FA. |
| 2FA | Custom TotpService (RFC 6238, PHP native). No third-party package. |
| OTP Delivery | Syriatel + MTN (SMS). Baileys/WhatsApp optional with SMS fallback. |
| Google Auth | FirebaseAuthService using Google JWKS. No kreait package. |
| Push Notifications | Firebase FCM HTTP v1 API. No wrapper package. Both mobile + PWA. |
| Permissions | Spatie Laravel Permissions. All routes permission-protected. |
| Venue Sport Model | Single category per venue (LD-054). No multi-sport pivot. |
| Category Model | `venue_categories` for venues (generic, scalable). `sport_categories` for Events tab only. |
| Commission Model | Config table with scope resolution (venue → club → global). Snapshots on bookings. |
| Settlement Model | Manual offline payment. `settlements` + `settlement_items`. Draft → Pending → Completed. |
| Slot Generation | Computed on-the-fly. No `slots` table. |
| Refunds | Internal wallet only. No provider refunds (Syrian providers unsupported). |
| Booking Currency | SYP only. Single currency. |
| OTP Length | 5 digits. Range 11111–99999. |
| OTP Hash | SHA-256. Never plain text in DB. |
| Review Target | Club level (not venue). One review per player per club. |
| Club Staff Assignment | `club_user` pivot. Dynamic. No single FK on users. |
| Auditing | spatie/laravel-activitylog on all admin mutations. |

---

# 20. Open Items / Blocked Items

## BLOCKED-001 — Fatora Callback Signature Verification

**What is blocked:** The source documents do not specify whether Fatora/SamaPay send a cryptographic signature on their callback POST that the backend can verify. Without this, the callback endpoint accepts unverified data.

**Why blocked:** The Fatora integration code shows no signature validation logic. The architecture documents do not mention a callback secret or HMAC header.

**What is needed:** Review Fatora API documentation for callback authentication. Options: (a) HMAC signature header, (b) IP whitelist, (c) re-query Fatora API on callback receipt.

**Current interim approach:** Re-query Fatora using `transactionReference` on callback receipt before updating payment status. Explicit confirmation required before release.

---

## BLOCKED-002 — `venues.avg_rating` Computation Source

**What is blocked:** LD-057 moved reviews to club level. The architecture still mentions `venues.avg_rating` being updated by ReviewObserver, but reviews no longer reference venues.

**Why blocked:** Column is kept in schema for future use but has no current computation path.

**What is needed:** Decision on whether venue-level quality rating is needed (e.g., admin-set score, or derived differently). Currently the column exists but will always be NULL.

---

## BLOCKED-003 — No-Show Handling Automation

**What is blocked:** Booking status `no_show` exists but no automated job or admin flow is specified for setting it.

**Why blocked:** Source documents mention `no_show` as a terminal status but do not define who sets it or when.

**What is needed:** Either (a) Club Admin manually marks a booking as no_show from the Club Dashboard, OR (b) a scheduled job marks it after a configurable grace period after `ends_at`. Confirm which.

**Current interim:** INFERENCE — Club Admin marks no_show manually. No automated job.

---

## BLOCKED-004 — WhatsApp Baileys Session Management

**What is blocked:** Baileys requires a persistent WhatsApp session (QR scan or pairing code). The architecture does not specify how this session is maintained in production, how re-authentication is handled, or where session files are stored.

**Why blocked:** This is an operational concern, not a business logic concern. If the Baileys session expires in production, WhatsApp OTP silently fails (fallback to SMS covers it), but the session must be periodically maintained.

**What is needed:** Define: session storage path (Docker volume?), monitoring for session disconnect, and re-authentication procedure (manual scan?).

