# V1 Schema — Sports Venue Booking Platform (Syria)

---

## 1. Purpose

Migration-ready specification for the first migration wave. All open questions are resolved. No ambiguity remains before code is written.

---

## 2. V1 Schema Principles

- Player pays the listed field price. Commission is internal; it is stored on the booking at creation time and visible to admin only.
- Browse (venues, fields, schedules, deals) requires no account.
- Booking requires a registered account. No guest booking in v1.
- Deposit is first-class. Every booking captures the deposit amount, total amount, and how much has been paid.
- Deposit payment is confirmed manually by admin in v1. No payment provider integration in the schema.
- `field_schedules` defines operating rules only (when a field is open). Availability is derived at the app layer: schedule windows minus overlapping non-cancelled bookings.
- Admin observes all bookings and commissions. Venues and players never see commission figures.

---

## 3. Core Entities

Seven tables: `users`, `venues`, `fields`, `field_schedules`, `bookings`, `waitlist_entries`, `deals`.

---

## 4. Entity Definitions

---

### users

Laravel default table extended with `phone` and `role`.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | — | |
| name | string | NO | |
| email | string | NO | unique |
| phone | string | NO | unique; primary identifier in Syria context |
| password | string | NO | |
| role | enum | NO | `player`, `venue_manager`, `admin` |
| email_verified_at | timestamp | YES | |
| remember_token | string | YES | |
| created_at / updated_at | timestamp | — | |

**Relationships:** has many `venues` (via user_id), has many `bookings` (via user_id), has many `waitlist_entries` (via user_id).

---

### venues

A sports club or facility. Owns the fields. Pays the platform commission.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | — | |
| user_id | bigint FK → users | NO | venue manager; one manager per venue in v1 |
| name | string | NO | |
| city | string | NO | |
| district | string | YES | neighborhood/area |
| address | string | YES | |
| phone | string | YES | |
| commission_rate | decimal(5,2) | NO | platform %; stored here, never exposed to players or venue managers via API |
| is_active | boolean | NO | default true |
| created_at / updated_at | timestamp | — | |

**Relationships:** belongs to `users`, has many `fields`.

---

### fields

A specific bookable surface (pitch, court) within a venue.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | — | |
| venue_id | bigint FK → venues | NO | |
| name | string | NO | e.g. "Field 1", "Court A" |
| sport_type | enum | NO | `football`, `basketball`, `tennis`, `padel`, `volleyball`, `other` |
| price_per_hour | decimal(10,2) | NO | player-facing price; base for booking total |
| deposit_percentage | tinyint unsigned | NO | e.g. 30 = 30% deposit at booking; 0 = no deposit |
| capacity | smallint unsigned | YES | max players; nullable = no limit enforced |
| description | text | YES | |
| is_active | boolean | NO | default true |
| created_at / updated_at | timestamp | — | |

**Relationships:** belongs to `venues`, has many `field_schedules`, has many `bookings`, has many `waitlist_entries`, has many `deals`.

---

### field_schedules

Operating rules only. Defines the days and hours a field is open. Does not represent individual slots. Does not track occupancy.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | — | |
| field_id | bigint FK → fields | NO | |
| day_of_week | tinyint unsigned | NO | 0 = Sunday … 6 = Saturday |
| opens_at | time | NO | |
| closes_at | time | NO | |
| is_active | boolean | NO | default true |
| created_at / updated_at | timestamp | — | |

**Availability rule:** a slot is available if it falls within an active schedule window AND no existing booking with `status` in (`pending`, `confirmed`) overlaps it.

**Relationships:** belongs to `fields`.

---

### bookings

The core booking record. Owns all financial state. Immutable price snapshot at creation.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | — | |
| user_id | bigint FK → users | NO | the player; guest booking is not supported in v1 |
| field_id | bigint FK → fields | NO | |
| starts_at | datetime | NO | |
| ends_at | datetime | NO | |
| total_price | decimal(10,2) | NO | snapshot at booking time; never recalculated |
| deposit_amount | decimal(10,2) | NO | snapshot: total_price × (deposit_percentage / 100) at booking time |
| paid_amount | decimal(10,2) | NO | default 0.00; updated by admin when payment is confirmed |
| commission_amount | decimal(10,2) | NO | snapshot: total_price × (commission_rate / 100) at booking time; admin-only |
| payment_status | enum | NO | see lifecycle below |
| status | enum | NO | see lifecycle below |
| notes | text | YES | player-supplied notes |
| created_at / updated_at | timestamp | — | |

**Booking status lifecycle:**
```
pending → confirmed → completed
                   ↘ cancelled
        ↘ cancelled
```
- `pending`: created, deposit not yet confirmed
- `confirmed`: deposit confirmed by admin
- `completed`: booking window has passed and was fulfilled
- `cancelled`: cancelled by player or admin at any stage before completion

**Payment status lifecycle:**
```
unpaid → deposit_paid → fully_paid
                      ↘ refunded
       ↘ refunded
```
- `unpaid`: no payment received
- `deposit_paid`: deposit confirmed; remainder due on-site or before start
- `fully_paid`: full amount confirmed
- `refunded`: payment returned (full or partial; tracked via paid_amount delta)

`payment_status` and `status` change independently. A `confirmed` booking always has `payment_status` of at least `deposit_paid`.

**Relationships:** belongs to `users`, belongs to `fields`.

---

### waitlist_entries

A registered player requests notification when a specific time slot becomes available.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | — | |
| user_id | bigint FK → users | NO | |
| field_id | bigint FK → fields | NO | |
| desired_date | date | NO | |
| desired_start_time | time | NO | |
| desired_end_time | time | NO | |
| status | enum | NO | `waiting`, `notified`, `booked`, `expired` |
| created_at / updated_at | timestamp | — | |

**Relationships:** belongs to `users`, belongs to `fields`.

---

### deals

A venue-defined last-minute discounted offer for a specific field and time window.

| Column | Type | Null | Notes |
|--------|------|------|-------|
| id | bigint PK | — | |
| field_id | bigint FK → fields | NO | |
| starts_at | datetime | NO | the bookable window start |
| ends_at | datetime | NO | the bookable window end |
| original_price | decimal(10,2) | NO | reference price before discount |
| discounted_price | decimal(10,2) | NO | price player pays when booking via this deal |
| offer_expires_at | datetime | NO | deal disappears from listings after this timestamp |
| is_active | boolean | NO | default true; set false to pull a deal early |
| created_at / updated_at | timestamp | — | |

**Relationships:** belongs to `fields`.

---

## 5. Relationship Ownership Rules

| Relationship | FK Column |
|---|---|
| venue → manager | `venues.user_id` |
| field → venue | `fields.venue_id` |
| field_schedule → field | `field_schedules.field_id` |
| booking → player | `bookings.user_id` |
| booking → field | `bookings.field_id` |
| waitlist_entry → player | `waitlist_entries.user_id` |
| waitlist_entry → field | `waitlist_entries.field_id` |
| deal → field | `deals.field_id` |

No pivot tables in v1.

---

## 6. Out of Scope for V1

- Guest booking (decided: not supported)
- Payment provider integration tables
- Reviews and ratings
- Media / image uploads
- Recurring bookings
- Group / team bookings
- Multi-manager per venue
- Notifications table (use Laravel notifications)
- Loyalty / rewards
- Analytics tables
- Admin config tables

---

## 7. Resolved Decisions

| Question | Decision |
|---|---|
| Guest booking | Not supported. `bookings.user_id` is NOT NULL. Browse remains public. |
| Deposit payment method | Manual admin confirmation in v1. `paid_amount` is updated by admin. No payment provider table needed. |
| Availability source | `field_schedules` = operating rules only. Occupied slots derived from bookings at app layer. |
| Commission visibility | Stored on `venues.commission_rate` and snapshotted to `bookings.commission_amount`. Never in API responses to venue or player. |
