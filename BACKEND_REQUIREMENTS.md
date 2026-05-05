# دق احجزلي — Backend Requirements & Gap Report

**Version:** 1.0
**Date:** May 5, 2026
**Status:** Ready for Backend Team Review
**Author:** Mobile Team

---

## 📋 Executive Summary

This document is a comprehensive backend requirements specification derived from analyzing:

1. **293 endpoints** in `DaqEhjezly_Mobile_API_v1_INFERRED.postman_collection.json` (281 with response shapes, 96% coverage)
2. **9 Flutter MVP phases** built mock-first (~520 files, ~313 tests)
3. **Canonical entity schemas** from `entity_schemas.md` (derived from Laravel models/resources/migrations)

### Key Numbers

| Metric | Value |
|--------|-------|
| Total endpoints in INFERRED Postman | 293 |
| Endpoints with response shapes | 281 (96%) |
| Endpoints needed for Mobile MVP | ~83 |
| Flutter phases completed mock-first | 9 |
| **Identified gaps** | See "Gap Report" section |

### How to Use This Document

**For Backend Team:**
1. Read sections P0 → P3 in priority order
2. For each endpoint: check if it matches your existing Laravel route
3. Compare response shape with what's documented here
4. If matches → ✅ already done, just verify
5. If different shape → adjust to match (Mobile is built against this contract)
6. If not in Postman → see "Gap Report" for required additions

**For Mobile Team (Khaled):**
- Use this as the integration spec when wiring real backend
- All shapes here are what the Flutter mock implementation expects
- Any deviation requires DI flag flip + model adjustment

---

## 📊 Coverage Summary by Phase

| Flutter Phase | Endpoints | In Postman | Gaps | Status |
|--------------|-----------|------------|------|--------|
| Phase 1: Auth | 12 | 12 | 0 | ✅ Fully covered |
| Phase 2: Home + Discovery | 10 | 10 | 0 | ✅ Fully covered |
| Phase 3: Venue Detail + Booking | 9 | 9 | 0 | ✅ Fully covered |
| Sprint: Maps + Settings | 4 | 4 | 0 | ✅ Fully covered |
| Phase Matches/Waitlist/Deals | 17 | 14 | 3 | ⚠️ Waitlist gap |
| Phase 7: Tournaments + Notifications | 11 | 10 | 1 | ⚠️ FCM minor |
| Phase 8: Wallet + Coupons | 11 | 5 | 6 | 🔴 Coupons missing |
| Phase 9: Teams + Sports Profile | 14 | 5 | 9 | 🔴 Sports Profile missing |
| Phase 10: Chat (Pusher) | 9 | 4 | 5 | 🔴 Major gaps |
| **TOTALS** | **97** | **73** | **24** | |

---

## 🎯 Conventions & Standards

### Response Envelope (USED EVERYWHERE)

```json
{
  "success": true,
  "message": "Optional human-readable message",
  "data": { ... },
  "errors": null,
  "meta": { "current_page": 1, "per_page": 20, "total": 142, "last_page": 8 }
}
```

For errors:
```json
{
  "success": false,
  "message": "User-friendly error message in Arabic",
  "data": null,
  "errors": { "field": ["validation error 1", "validation error 2"] }
}
```

### HTTP Status Codes

| Code | When |
|------|------|
| 200 | Successful GET, PUT, DELETE |
| 201 | Successful POST that creates resource |
| 204 | Successful DELETE without body |
| 401 | Missing/invalid token |
| 403 | Authenticated but no permission |
| 404 | Resource doesn't exist |
| 422 | Validation errors |
| 429 | Rate limited |

### Authentication

- **Method:** `Authorization: Bearer {access_token}` header
- **Type:** Sanctum/JWT (Mobile doesn't care, just needs string)
- **Expiry:** Returned as `expires_in` (seconds) in auth responses
- **Refresh:** `POST /auth/refresh` (exists in Postman ✅)

### Locale & Currency

- **Currency:** SYP (Syrian Pounds), **integer values only** (no decimals)
- **Phone:** E.164 format: `+963991234567`
- **Dates:** ISO 8601 UTC: `2026-05-05T14:30:00Z`
- **Date-only:** `YYYY-MM-DD`
- **Time-only:** `HH:MM` (24-hour)
- **Localized fields:** Some return `{ ar: "...", en: "..." }` (e.g., venue names)

### Pagination

- Default: `per_page=20`, max: `100`
- Query: `?page=1&per_page=20`
- Response includes `meta.current_page`, `meta.last_page`, `meta.total`

### Mobile Headers (sent by Flutter app)

```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
X-App-Version: 1.0.0
X-Platform: android | ios
X-Device-ID: <unique-device-id>
Accept-Language: ar | en
```

### Common Status Enums

**Booking statuses:** `pending_payment | confirmed | checked_in | completed | cancelled | no_show | expired`

**Payment providers:** `syriatel_cash | mtn_cash | bank_transfer | cash_at_venue | wallet`

**Payment flow types:** `otp | redirect | qr_code | manual_confirmation`

**Payment next steps:** `verify_otp | redirect_to_url | scan_qr | wait_for_confirmation | completed`

**User roles:** `player | club_manager | club_staff | admin`

**Account statuses:** `active | blocked | suspended | pending_profile_completion`

**Booking code format:** `BK-YYYY-NNNN` (e.g., `BK-2026-0142`)

**Support ticket format:** `TK-YYYY-NNNN`


---

# 🔴 P0 — BLOCKERS

**These endpoints are required for the app to launch. Without them, the app cannot function at all.**

---

## Phase 1: Authentication & Profile (12 endpoints)

**Goal:** User can sign up, log in, manage their profile.

### `POST /auth/otp/send`

**Description:** Send OTP code to phone (5 digits, expires in 5 min). Returns `challenge_uuid` to track this OTP request.
**Priority:** P0 - critical
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `live`
**Auth:** Public (no token needed)

**Notes:** Mobile sends `+963991234567` format. Backend validates, generates 5-digit code, sends via Syriatel/MTN SMS gateway. Rate limit: 1 per 60s, max 5/hour per phone.

**Request body:**

```json
{
  "phone_number": "{{test_phone}}"
}
```

**Response:**

```json
{
  "success": false,
  "message": "The رقم الهاتف field is required.",
  "errors": {
    "phone": [
      "The رقم الهاتف field is required."
    ]
  }
}
```

---

### `POST /auth/otp/verify`

**Description:** Verify OTP code. Returns `access_token` + `user` if existing user, OR returns flag to call `/auth/register` next.
**Priority:** P0 - critical
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `live`
**Auth:** Public (no token needed)

**Notes:** Two response variants: (1) existing user → full auth response; (2) new user → `{ challenge_uuid, requires_registration: true }`. Mobile then routes to "Complete Profile" page.

**Request body:**

```json
{
  "phone_number": "{{test_phone}}",
  "otp_code": "{{test_otp}}"
}
```

**Response:**

```json
{
  "success": false,
  "message": "The رقم الهاتف field is required. (and 1 more error)",
  "errors": {
    "phone": [
      "The رقم الهاتف field is required."
    ],
    "otp": [
      "The رمز التحقق field is required."
    ]
  }
}
```

---

### `POST /auth/otp/resend`

**Description:** Resend OTP (when user did not receive). Same `challenge_uuid` continues.
**Priority:** P0 - critical
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `live`
**Auth:** Public (no token needed)

**Notes:** Rate limit: 1 per 60s. After 3 resends, force re-send.

**Request body:**

```json
{
  "phone_number": "{{test_phone}}"
}
```

**Response:**

```json
{
  "success": false,
  "message": "The challenge uuid field is required.",
  "errors": {
    "challenge_uuid": [
      "The challenge uuid field is required."
    ]
  }
}
```

---

### `POST /auth/register`

**Description:** Complete profile after OTP verification (for new users only).
**Priority:** P0 - critical
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `high`
**Auth:** Public (no token needed)

**Notes:** Sends: `{ challenge_uuid, name, date_of_birth, email?, language? }`. Returns full auth response with `access_token` + `user`.

**Request body:**

```json
{
  "phone_number": "{{test_phone}}",
  "name": "محمد أحمد",
  "email": "mohamed@example.com"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم إنشاء الحساب بنجاح",
  "data": {
    "user": {
      "id": 1,
      "name": "محمد علي",
      "first_name": "محمد",
      "last_name": "علي",
      "phone_number": "+963991234567",
      "email": "user1@example.com",
      "avatar_url": null,
      "city": {
        "id": 1,
        "country_id": 1,
        "state_id": 1,
        "name": "Damascus",
        "name_ar": "دمشق",
        "latitude": 33.5138,
        "longitude": 36.2765
      },
      "language": "ar",
      "is_phone_verified": true,
      "is_email_verified": false,
      "verified_at": "2026-03-27T10:00:00Z",
      "role": "player",
      "account_status": "active",
      "preferences": {
        "notifications_push_enabled": true,
        "preferred_language": "ar"
      },
      "created_at": "2025-12-27T10:00:00Z",
      "updated_at": "2026-04-26T06:00:00Z"
    },
    "access_token": "1|qHQYz8oBcRvW6Lhg0KM3sW1b5pVnE2JiX9DfUaTk0c8a42",
    "token_type": "Bearer",
    "expires_in": 31536000,
    "refresh_token": null
  }
}
```

---

### `POST /auth/google`

**Description:** Google Sign-In with `id_token` from Google SDK.
**Priority:** P0 - high
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `live`
**Auth:** Public (no token needed)

**Notes:** Returns same shape as `/auth/otp/verify`. Backend validates id_token via Google APIs, creates user if new.

**Request body:**

```json
{
  "id_token": "firebase-id-token-here",
  "device_name": "iPhone 13"
}
```

**Response:**

```json
{
  "success": false,
  "message": "auth.google_token_invalid",
  "errors": {
    "id_token": [
      "Invalid Firebase ID token format."
    ]
  }
}
```

---

### `POST /auth/logout`

**Description:** Invalidate current access token.
**Priority:** P0 - critical
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `high`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم تسجيل الخروج",
  "data": []
}
```

---

### `GET /profile`

**Description:** Get current authenticated user's profile.
**Priority:** P0 - critical
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `PUT /profile`

**Description:** Update profile (name, email, date_of_birth, language, city_id).
**Priority:** P0 - high
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `high`
**Auth:** Bearer token required

**Request body:**

```json
{
  "name": "محمد أحمد",
  "email": "mohamed@example.com",
  "city_id": 1
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم تحديث الملف الشخصي",
  "data": {
    "id": 1,
    "name": "محمد علي",
    "first_name": "محمد",
    "last_name": "علي",
    "phone_number": "+963991234567",
    "email": "user1@example.com",
    "avatar_url": null,
    "city": {
      "id": 1,
      "country_id": 1,
      "state_id": 1,
      "name": "Damascus",
      "name_ar": "دمشق",
      "latitude": 33.5138,
      "longitude": 36.2765
    },
    "language": "ar",
    "is_phone_verified": true,
    "is_email_verified": false,
    "verified_at": "2026-03-27T10:00:00Z",
    "role": "player",
    "account_status": "active",
    "preferences": {
      "notifications_push_enabled": true,
      "preferred_language": "ar"
    },
    "created_at": "2025-12-27T10:00:00Z",
    "updated_at": "2026-04-26T06:00:00Z"
  }
}
```

---

### `POST /profile/avatar`

**Description:** Upload avatar image (multipart/form-data, max 5MB, jpg/png).
**Priority:** P0 - medium
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `high`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم رفع الصورة",
  "data": {
    "avatar_url": "https://api.daqehjezly.com/storage/avatars/u1.jpg"
  }
}
```

---

### `DELETE /profile/avatar`

**Description:** Remove avatar (revert to default).
**Priority:** P0 - low
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `high`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم حذف الصورة الشخصية",
  "data": {
    "avatar_url": null
  }
}
```

---

### `POST /devices`

**Description:** Register FCM token for push notifications.
**Priority:** P0 - medium
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `high`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ fcm_token, platform: "android"|"ios", device_id, app_version }`. Backend stores per user, used to send push.

**Request body:**

```json
{
  "fcm_token": "fcm-token-here",
  "platform": "ios",
  "device_name": "iPhone 13"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم تسجيل الجهاز",
  "data": {
    "id": 1,
    "device_id": "iPhone-14-Pro-ABC123",
    "platform": "ios",
    "fcm_token": "fcm_token_value",
    "app_version": "1.0.0",
    "os_version": "17.4",
    "last_used_at": "2026-04-26T10:00:00Z"
  }
}
```

---

### `POST /auth/refresh`

**Description:** Refresh access token using refresh_token.
**Priority:** P0 - medium
**Postman folder:** `01. Authentication & Onboarding`
**Confidence:** `live`
**Auth:** Bearer token required

**Notes:** Optional — Mobile uses long-lived tokens via Sanctum. If not implemented, return 501 Not Implemented.

**Request body:**

```json
{
  "refresh_token": "{{refresh_token}}"
}
```

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---


## Phase 2 (P0 subset): Minimum viable Home (3 endpoints)

**Goal:** Home page renders with content even at MVP launch.

### `GET /categories`

**Description:** List all sport categories (tennis, football, basketball, volleyball, padel, futsal).
**Priority:** P0 - critical
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Public (no token needed)

**Notes:** Static-ish list, can be cached aggressively. Returns array, no pagination.

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "slug": "football-pitches",
      "name": "ملاعب كرة قدم",
      "type": "sports",
      "is_active": true,
      "venues_count": 16
    },
    {
      "id": 2,
      "slug": "basketball-courts",
      "name": "ملاعب كرة سلة",
      "type": "court",
      "is_active": true,
      "venues_count": 1
    },
    {
      "id": 3,
      "slug": "tennis-courts",
      "name": "ملاعب تنس",
      "type": "court",
      "is_active": true,
      "venues_count": 11
    },
    {
      "id": 4,
      "slug": "gyms",
      "name": "صالات رياضية",
      "type": "hall",
      "is_active": true,
      "venues_count": 7
    },
    {
      "id": 5,
      "slug": "volleyball-courts",
      "name": "ملاعب كرة طائرة",
      "type": "court",
      "is_active": true,
      "venues_count": 8
    },
    {
      "id": 6,
      "slug": "padel-courts",
      "name": "ملاعب بادل",
      "type": "court",
      "is_active": true,
      "venues_count": 7
    },
    {
      "id": 7,
      "slug": "swimming-pools",
      "name": "ملاعب سباحة",
      "type": "outdoor",
      "is_active": true,
      "venues_count": 6
    }
  ]
}
```

---

### `GET /venues/popular`

**Description:** Popular venues for home page (sorted by booking count, then rating).
**Priority:** P0 - critical
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Public (no token needed)

**Notes:** Returns top 10 venues. No pagination needed for home (use full /venues for that).

**Query parameters:**

- `city_id` _(required)_ — example: ``
- `limit` _(required)_ — example: `20`

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 1,
      "slug": "al-jaish-stadium",
      "club_id": 1,
      "category_id": 1,
      "name": {
        "ar": "ملعب الجلاء",
        "en": "Al-Jaish Stadium"
      },
      "description": {
        "ar": "ملعب كرة قدم بمواصفات دولية في دمشق، مجهز بإنارة ليلية وأرضية عشبية صناعية",
        "en": "International standard football pitch in Damascus with night lighting and artificial turf"
      },
      "size": null,
      "capacity": null,
      "amenities": null,
      "opening_hours": {
        "friday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "monday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "sunday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "tuesday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "saturday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "thursday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "wednesday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        }
      },
      "latitude": "33.51380000",
      "longitude": "36.27650000",
      "avg_rating": null,
      "reviews_count": 0,
      "reports_count": 0,
      "is_flagged": 0,
      "price_from": 50000,
      "status": "active",
      "is_featured": false,
      "view_count": 4,
      "order_column": 0,
      "deleted_at": null,
      "created_at": "2026-04-21T00:11:50.000000Z",
      "updated_at": "2026-04-26T10:17:42.000000Z",
      "bookings_count": 10
    },
    {
      "id": 2,
      "slug": "al-fayhaa-stadium",
      "club_id": 1,
      "category_id": 1,
      "name": {
        "ar": "ملعب الفيحاء",
        "en": "Al-Fayhaa Stadium"
      },
      "description": {
        "ar": "ملعب كرة قدم بأبعاد قياسية مع خدمات كاملة",
        "en": "Standard football pitch with full facilities"
      },
      "size": null,
      "capacity": 
  ... (truncated for brevity)
}
```

---

### `GET /content/banners`

**Description:** Promotional banners for home top section.
**Priority:** P0 - critical
**Postman folder:** `17. Content & Media 📰`
**Confidence:** `live`
**Auth:** Public (no token needed)

**Notes:** Mobile filters by `position=home_top`. Each banner has `image_url`, `action_url`, `position`, `display_order`, `is_active`.

**Query parameters:**

- `position` _(required)_ — example: `home_top`

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 1,
      "title_ar": "ابدأ رحلتك الرياضية",
      "subtitle_ar": "احجز ملعبك المفضل بضغطة زر",
      "image_url": "https://placehold.co/1080x540/png",
      "image_url_dark": null,
      "link": {
        "type": null,
        "value": null
      },
      "display_order": 1
    }
  ]
}
```

---


## Phase 3 (P0 subset): Booking Creation Flow (5 endpoints)

**Goal:** User can complete a booking end-to-end.

### `GET /venues/{slug}`

**Description:** Full venue detail page (info + working hours + amenities + reviews preview).
**Postman path:** `GET /venues/{venue_slug}` _(Flutter expects same)_
**Priority:** P0 - critical
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Bearer token required

**Notes:** `{slug}` is the URL-friendly identifier. Response includes `is_favorite` (auth-aware), `description`, `amenities[]`, `working_hours[]`, `gallery[]`, `previewReviews[]` (top 3), `relatedVenues[]` (5 similar).

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "slug": "al-jaish-stadium",
    "name": "ملعب الجلاء",
    "description": "ملعب كرة قدم بمواصفات دولية في دمشق، مجهز بإنارة ليلية وأرضية عشبية صناعية",
    "category": {
      "id": 1,
      "slug": "football-pitches",
      "name": "ملاعب كرة قدم"
    },
    "club": {
      "id": 1,
      "slug": "al-jaish-club",
      "name": "نادي الجلاء",
      "city": {
        "id": 1,
        "name": "Damascus",
        "name_ar": "دمشق"
      }
    },
    "location": {
      "latitude": 33.5138,
      "longitude": 36.2765
    },
    "main_image_url": null,
    "pricing": {
      "price_from": 50000,
      "currency": "SYP"
    },
    "rating": null,
    "reviews_count": 0,
    "is_favorite": false,
    "is_featured": false,
    "is_open_now": true,
    "view_count": 5,
    "distance_km": null,
    "status": "suspended",
    "amenities": [],
    "opening_hours": {
      "friday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "monday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "sunday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "tuesday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "saturday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "thursday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "wednesday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      }
    },
    "capacity": null,
    "size": null,
    "images": [],
    "club_contact": {
      "phone": "+963112123456",
      "whatsapp": null,
      "email": null,
      "address": "منطقة الفيحاء، دمشق",
      "logo_url": null
    },
    "booking_rules": {
      "min_hours": 1,
      "max_hours": 8,
      "deposit_required": false,
      "deposit_percentage": null,
      "free_cancellation_hours": 24
    }
  }
}
```

---

### `GET /venues/{slug}/availability`

**Description:** Available time slots for a specific date.
**Postman path:** `GET /venues/{venue_slug}/availability` _(Flutter expects same)_
**Priority:** P0 - critical
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Bearer token required

**Notes:** Query: `?date=2026-05-10`. Returns hourly slots from open to close. Each slot: `{ start_time, end_time, is_available, price, blocked_reason? }`. Past slots filtered out.

**Query parameters:**

- `date` _(required)_ — example: `2026-04-25`

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "venue_id": 1,
    "slug": "al-jaish-stadium",
    "opening_hours": {
      "friday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "monday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "sunday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "tuesday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "saturday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "thursday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      },
      "wednesday": {
        "open": "08:00",
        "close": "22:00",
        "closed": false
      }
    },
    "business_hours": null,
    "booking_rules": null
  }
}
```

---

### `POST /bookings/check-availability`

**Description:** Pre-check if a specific slot is available before user submits.
**Priority:** P0 - critical
**Postman folder:** `03. Booking Flow`
**Confidence:** `live`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ venue_id, date, start_time, end_time }`. Returns `{ is_available: bool, blocked_reason?: string }`. Used to catch race conditions just before submit.

**Request body:**

```json
{
  "venue_id": 1,
  "booking_date": "2026-04-25",
  "start_time": "18:00",
  "duration_hours": 2
}
```

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `POST /bookings/calculate-price`

**Description:** Calculate total price including coupon discount.
**Priority:** P0 - critical
**Postman folder:** `03. Booking Flow`
**Confidence:** `live`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ venue_id, date, start_time, end_time, coupon_code? }`. Returns: `{ subtotal, discount, total, currency: "SYP", breakdown[] }`.

**Request body:**

```json
{
  "venue_id": 1,
  "booking_date": "2026-04-25",
  "start_time": "18:00",
  "duration_hours": 2,
  "promo_code": "{{promo_code}}"
}
```

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `POST /bookings`

**Description:** Create booking. Returns booking + payment initiation.
**Priority:** P0 - critical
**Postman folder:** `03. Booking Flow`
**Confidence:** `high`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ venue_id, date, start_time, end_time, payment_method, coupon_code? }`. Returns: `{ booking: Booking, payment_initiation: PaymentInitiation }`. The `payment_initiation` tells mobile which next step (verify_otp/scan_qr/wait_for_confirmation/completed).

**Request body:**

```json
{
  "venue_id": 1,
  "booking_date": "2026-04-25",
  "start_time": "18:00",
  "duration_hours": 2,
  "promo_code": "{{promo_code}}",
  "notes": "ملاحظات خاصة"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم إنشاء الحجز",
  "data": {
    "id": 7,
    "booking_code": "BK-2026-0007",
    "qr_code": "DAQ-BOOKING-00000007",
    "user_id": 1,
    "captain_id": null,
    "team_id": null,
    "subscription_id": null,
    "venue": {
      "id": 1,
      "slug": "al-jaish-stadium",
      "name": "ملعب الجلاء",
      "club": {
        "id": 1,
        "name": "نادي الجلاء",
        "slug": "al-jaish-club"
      }
    },
    "sport_category": {
      "id": 1,
      "name": "ملاعب كرة قدم",
      "slug": "football-pitches"
    },
    "booking_date": "2026-04-28",
    "start_time": "18:00",
    "end_time": "20:00",
    "starts_at": "2026-04-28T18:00:00Z",
    "ends_at": "2026-04-28T20:00:00Z",
    "duration_minutes": 120,
    "duration_hours": 2,
    "status": "pending_payment",
    "payment_status": "unpaid",
    "refund_status": "none",
    "venue_price": 60000,
    "discount_amount": 0,
    "commission_amount": 6000,
    "club_payout_amount": 54000,
    "total_amount": 60000,
    "total_price": 60000,
    "paid_amount": 0,
    "remaining_amount": 60000,
    "currency": "SYP",
    "is_recurring": false,
    "is_group_booking": false,
    "group_size": null,
    "is_split_payment": false,
    "split_method": null,
    "checked_in_at": null,
    "cancelled_at": null,
    "cancellation_reason": null,
    "notes": null,
    "reschedule_count": 0,
    "applied_promotion_id": null,
    "created_at": "2026-04-26T04:00:00Z",
    "updated_at": "2026-04-26T08:00:00Z"
  }
}
```

---


## Phase 3 (P0 subset): Payment Initiation (5 endpoints)

**Goal:** Booking can be paid via Syriatel Cash, MTN Cash, or Cash at Venue.

### `POST /payments/syriatel/initiate`

**Description:** Start Syriatel Cash payment flow.
**Priority:** P0 - critical
**Postman folder:** `04. Payments > 04.1 Syriatel Cash`
**Confidence:** `high`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ booking_id, amount, phone_number }`. Returns `PaymentInitiation` with `next_step: "verify_otp"`, `provider_reference` to track.

**Request body:**

```
{
  "booking_id": {{booking_id}},
  "phone_number": "{{test_phone}}"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم بدء الدفع عبر سيرياتيل كاش",
  "data": {
    "payment_id": 1234,
    "booking_id": 1,
    "status": "pending",
    "flow_type": "otp",
    "next_step": "verify_otp",
    "provider": "syriatel_cash",
    "provider_reference": "SYR-A1B2C3",
    "amount": 60000,
    "currency": "SYP",
    "expires_at": "2026-04-26T10:15:00Z",
    "metadata": {
      "otp_length": 6,
      "otp_resend_after": 60
    }
  }
}
```

---

### `POST /payments/syriatel/verify`

**Description:** Verify Syriatel OTP to complete payment.
**Priority:** P0 - critical
**Postman folder:** `04. Payments > 04.1 Syriatel Cash`
**Confidence:** `high`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ payment_id, otp_code }`. On success: `payment.status = completed`, `booking.status = confirmed`, `booking.payment_status = paid`.

**Request body:**

```
{
  "payment_id": {{payment_id}},
  "otp_code": "{{test_otp}}"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم التحقق من الدفع",
  "data": {
    "payment_id": 1234,
    "booking_id": 1,
    "status": "completed",
    "flow_type": "otp",
    "next_step": "verify_otp",
    "provider": "syriatel_cash",
    "provider_reference": "SYR-A1B2C3",
    "amount": 60000,
    "currency": "SYP",
    "expires_at": "2026-04-26T10:15:00Z",
    "metadata": {
      "otp_length": 6,
      "otp_resend_after": 60
    }
  }
}
```

---

### `POST /payments/mtn/initiate`

**Description:** Start MTN Cash payment flow. Same shape as Syriatel.
**Priority:** P0 - critical
**Postman folder:** `04. Payments > 04.2 MTN Cash`
**Confidence:** `high`
**Auth:** Bearer token required

**Request body:**

```
{
  "booking_id": {{booking_id}},
  "phone_number": "{{test_phone}}"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم بدء الدفع عبر MTN كاش",
  "data": {
    "payment_id": 1235,
    "booking_id": 1,
    "status": "pending",
    "flow_type": "otp",
    "next_step": "verify_otp",
    "provider": "mtn_cash",
    "provider_reference": "MTN-A1B2C3",
    "amount": 60000,
    "currency": "SYP",
    "expires_at": "2026-04-26T10:15:00Z",
    "metadata": {
      "otp_length": 6,
      "otp_resend_after": 60
    }
  }
}
```

---

### `POST /payments/mtn/verify`

**Description:** Verify MTN OTP. Same shape as Syriatel.
**Priority:** P0 - critical
**Postman folder:** `04. Payments > 04.2 MTN Cash`
**Confidence:** `high`
**Auth:** Bearer token required

**Request body:**

```
{
  "payment_id": {{payment_id}},
  "otp_code": "{{test_otp}}"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم التحقق من الدفع",
  "data": {
    "payment_id": 1234,
    "booking_id": 1,
    "status": "completed",
    "flow_type": "otp",
    "next_step": "verify_otp",
    "provider": "syriatel_cash",
    "provider_reference": "SYR-A1B2C3",
    "amount": 60000,
    "currency": "SYP",
    "expires_at": "2026-04-26T10:15:00Z",
    "metadata": {
      "otp_length": 6,
      "otp_resend_after": 60
    }
  }
}
```

---

### `POST /payments/cash/confirm`

**Description:** Confirm cash-at-venue selection (no real payment, just records intent).
**Priority:** P0 - critical
**Postman folder:** `04. Payments > 04.4 Cash at Venue`
**Confidence:** `high`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ booking_id }`. Backend marks `booking.payment_status = pending`, `booking.payment_method = cash_at_venue`. Booking is confirmed but unpaid until staff checks them in.

**Request body:**

```
{
  "booking_id": {{booking_id}}
}
```

**Response:**

```json
{
  "success": true,
  "message": "سيتم الدفع نقداً عند الوصول",
  "data": {
    "payment_id": 1237,
    "booking_id": 1,
    "status": "pending",
    "flow_type": "manual_confirmation",
    "next_step": "wait_for_confirmation",
    "provider": "cash_at_venue",
    "provider_reference": "CAS-A1B2C3",
    "amount": 60000,
    "currency": "SYP",
    "expires_at": "2026-04-26T10:15:00Z",
    "metadata": {
      "reference_code": "CASH-001237"
    }
  }
}
```

---


---

# 🟠 P1 — CORE (Main user value)

**These deliver core user value: full home, bookings management, reviews.**

---

## Phase 2 (P1): Full Home + Discovery (7 endpoints)

### `GET /content/featured`

**Description:** Featured content carousel (mixed venues + promotions + events).
**Priority:** P1 - high
**Postman folder:** `17. Content & Media 📰`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": []
}
```

---

### `GET /venues/featured`

**Description:** Featured venues for hero section (top 1 displayed large).
**Priority:** P1 - high
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "data": [
    {
      "id": 54,
      "slug": "al-karamah-club-main-46",
      "name": "صالة التنس الرئيسي - نادي الكرامة",
      "description": "منشأة رياضية حديثة مجهزة بالكامل في سوريا.",
      "category": {
        "id": 3,
        "slug": "tennis-courts",
        "name": "ملاعب تنس"
      },
      "club": {
        "id": 5,
        "slug": "al-karamah-club",
        "name": "نادي الكرامة",
        "city": {
          "id": 15,
          "name": "Homs",
          "name_ar": "حمص"
        }
      },
      "location": {
        "latitude": 34.7546,
        "longitude": 36.7106
      },
      "main_image_url": null,
      "pricing": {
        "price_from": 152000,
        "currency": "SYP"
      },
      "rating": null,
      "reviews_count": 0,
      "is_favorite": false,
      "is_featured": true,
      "is_open_now": true,
      "view_count": 2396,
      "distance_km": null,
      "status": "active"
    },
    {
      "id": 28,
      "slug": "al-futowa-club-north-20",
      "name": "صالة الفرعي - نادي الفتوة",
      "description": "منشأة رياضية حديثة مجهزة بالكامل في سوريا.",
      "category": {
        "id": 5,
        "slug": "volleyball-courts",
        "name": "ملاعب كرة طائرة"
      },
      "club": {
        "id": 7,
        "slug": "al-futowa-club",
        "name": "نادي الفتوة",
        "city": {
          "id": 36,
          "name": "Deir ez-Zor",
          "name_ar": "دير الزور"
        }
      },
      "location": {
        "latitude": 35.3331,
        "longitude": 40.14
      },
      "main_image_url": null,
      "pricing": {
        "price_from": 74000,
        "currency": "SYP"
      },
      "rating": null,
      "reviews_count": 0,
      "is_favorite": false,
      "is_featured": true,
      "is_open_now": true,
      "view_count": 2364,
      "distance_km": null,
      "status": "active"
    },
    {
      "id": 16,
      "slug": "al-wathba-club-north-8",
      "name": "صالة البادل الفرعي - نادي الوثبة",
      "description": "منشأة رياضية ح
  ... (truncated)
}
```

---

### `GET /venues/nearby`

**Description:** Nearby venues sorted by distance.
**Priority:** P1 - high
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Bearer token required

**Notes:** Query: `?lat=33.5&lng=36.3&radius_km=20`. Mobile uses GPS; if denied, this section is hidden in UI.

**Response:**

```json
{
  "data": [
    {
      "id": 50,
      "slug": "al-jaish-club-royal-42",
      "name": "ملعب الطائرة الذهبي - نادي الجلاء",
      "description": "منشأة رياضية حديثة مجهزة بالكامل في سوريا.",
      "category": {
        "id": 5,
        "slug": "volleyball-courts",
        "name": "ملاعب كرة طائرة"
      },
      "club": {
        "id": 1,
        "slug": "al-jaish-club",
        "name": "نادي الجلاء",
        "city": {
          "id": 1,
          "name": "Damascus",
          "name_ar": "دمشق"
        }
      },
      "location": {
        "latitude": 33.5162,
        "longitude": 36.2759
      },
      "main_image_url": null,
      "pricing": {
        "price_from": 34000,
        "currency": "SYP"
      },
      "rating": null,
      "reviews_count": 0,
      "is_favorite": false,
      "is_featured": false,
      "is_open_now": true,
      "view_count": 1067,
      "distance_km": 0.27,
      "status": "active"
    },
    {
      "id": 2,
      "slug": "al-fayhaa-stadium",
      "name": "ملعب الفيحاء",
      "description": "ملعب كرة قدم بأبعاد قياسية مع خدمات كاملة",
      "category": {
        "id": 1,
        "slug": "football-pitches",
        "name": "ملاعب كرة قدم"
      },
      "club": {
        "id": 1,
        "slug": "al-jaish-club",
        "name": "نادي الجلاء",
        "city": {
          "id": 1,
          "name": "Damascus",
          "name_ar": "دمشق"
        }
      },
      "location": {
        "latitude": 33.515,
        "longitude": 36.28
      },
      "main_image_url": null,
      "pricing": {
        "price_from": 45000,
        "currency": "SYP"
      },
      "rating": null,
      "reviews_count": 0,
      "is_favorite": false,
      "is_featured": false,
      "is_open_now": true,
      "view_count": 0,
      "distance_km": 0.35,
      "status": "active"
    },
    {
      "id": 15,
      "slug": "al-jaish-club-north-7",
      "name": "ملعب البادل الفرعي - نادي الجلاء",
      "description": "منشأة رياضية حديثة مجهزة بالكامل في سوريا.
  ... (truncated)
}
```

---

### `GET /venues/recently-viewed`

**Description:** User's recently viewed venues (auth-required).
**Priority:** P1 - medium
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Bearer token required

**Notes:** Backend tracks views via implicit behavior or explicit `POST /venues/{slug}/view` (not exposed in Mobile).

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /venues/search`

**Description:** Full search with filters (category, city, price, rating, distance).
**Priority:** P1 - high
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Bearer token required

**Notes:** Query supports: `q` (text), `category_id`, `city_id`, `price_min`, `price_max`, `rating_min`, `distance_km`, `lat`, `lng`. Returns paginated.

**Response:**

```json
{
  "data": [],
  "links": {
    "first": "http://localhost:8001/api/v1/venues/search?page=1",
    "last": "http://localhost:8001/api/v1/venues/search?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": null,
    "last_page": 1,
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "page": null,
        "active": false
      },
      {
        "url": "http://localhost:8001/api/v1/venues/search?page=1",
        "label": "1",
        "page": 1,
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "page": null,
        "active": false
      }
    ],
    "path": "http://localhost:8001/api/v1/venues/search",
    "per_page": 10,
    "to": null,
    "total": 0
  }
}
```

---

### `GET /promotions/featured`

**Description:** Featured promotions banner section.
**Priority:** P1 - medium
**Postman folder:** `05. Promotions & Reviews > 05.1 Promotions`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "code": "WELCOME20",
      "slug": "welcome20",
      "name": {
        "ar": "خصم الترحيب",
        "en": "Welcome Discount"
      },
      "description": {
        "ar": "خصم 20% على أول حجز",
        "en": "20% off your first booking"
      },
      "type": "percentage",
      "value": 20,
      "min_amount": null,
      "max_discount": 100000,
      "venue": null,
      "applies_to": "all",
      "is_featured": true,
      "first_booking_only": true,
      "allowed_days": null,
      "image_url": null,
      "valid_from": "2026-04-22T13:27:54.000000Z",
      "valid_to": "2026-07-23T13:27:54.000000Z",
      "max_uses": 1000,
      "current_uses": 0,
      "max_uses_per_user": 1,
      "status": "active",
      "is_active": true
    },
    {
      "id": 2,
      "code": "SUMMER30",
      "slug": "summer30",
      "name": {
        "ar": "صيف حار",
        "en": "Hot Summer"
      },
      "description": {
        "ar": "خصم 30% على جميع الحجوزات",
        "en": "30% off all bookings"
      },
      "type": "percentage",
      "value": 30,
      "min_amount": null,
      "max_discount": 150000,
      "venue": null,
      "applies_to": "all",
      "is_featured": true,
      "first_booking_only": false,
      "allowed_days": null,
      "image_url": null,
      "valid_from": "2026-04-22T13:27:54.000000Z",
      "valid_to": "2026-06-23T13:27:54.000000Z",
      "max_uses": 500,
      "current_uses": 0,
      "max_uses_per_user": 1,
      "status": "active",
      "is_active": true
    }
  ]
}
```

---

### `GET /events`

**Description:** Upcoming events list.
**Priority:** P1 - medium
**Postman folder:** `16. Events & Tournaments 🎯`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "data": [
      {
        "id": 1,
        "club_id": 1,
        "venue_id": 1,
        "created_by": null,
        "title": "Friday Football Tournament",
        "title_ar": "بطولة كرة القدم - الجمعة",
        "description": "A weekly Friday tournament for amateur teams.",
        "description_ar": "بطولة جمعة أسبوعية لكل الفرق الهاوية.",
        "cover_image_url": null,
        "gallery_urls": null,
        "type": "tournament",
        "sport_type": "football",
        "starts_at": "2026-05-02T18:15:20.000000Z",
        "ends_at": "2026-05-02T22:15:20.000000Z",
        "registration_opens_at": null,
        "registration_closes_at": "2026-04-30T15:15:20.000000Z",
        "max_participants": 16,
        "min_participants": 8,
        "current_participants": 0,
        "registration_fee": "25000.00",
        "participant_type": "team",
        "team_size": 7,
        "prize_structure": [
          {
            "value": 200000,
            "position": 1,
            "prize_type": "cash"
          },
          {
            "value": 100000,
            "position": 2,
            "prize_type": "cash"
          },
          {
            "value": "كأس البرونز",
            "position": 3,
            "prize_type": "trophy"
          }
        ],
        "rules": null,
        "rules_ar": "1. كل فريق يتألف من 7 لاعبين\n2. مدة المباراة 30 دقيقة\n3. النتيجة بركلات الترجيح في حالة التعادل",
        "requirements": null,
        "requirements_ar": null,
        "status": "open",
        "is_featured": true,
        "is_published": true,
        "views_count": 2,
        "created_at": "2026-04-25T15:15:20.000000Z",
        "updated_at": "2026-04-26T10:17:53.000000Z",
        "club": {
          "id": 1,
          "name": {
            "ar": "نادي الجلاء",
            "en": "Al-Jaish Club"
          },
          "slug": "al-jaish-club"
        },
        "venue": {
          "id": 1,
          "name": {
            "ar":
  ... (truncated)
}
```

---


## Phase 3 (P1): Bookings Management (8 endpoints)

### `GET /bookings`

**Description:** My bookings list (paginated, all statuses).
**Priority:** P1 - critical
**Postman folder:** `03. Booking Flow`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /bookings/upcoming`

**Description:** Upcoming bookings tab (status: confirmed, future date).
**Priority:** P1 - critical
**Postman folder:** `03. Booking Flow`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /bookings/past`

**Description:** Past bookings tab (status: completed, past date).
**Priority:** P1 - critical
**Postman folder:** `03. Booking Flow`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /bookings/{id}`

**Description:** Booking detail with QR code, full venue info, payment status.
**Postman path:** `GET /bookings/{booking_id}`
**Priority:** P1 - critical
**Postman folder:** `03. Booking Flow`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /bookings/{id}/receipt`

**Description:** Get booking receipt (PDF or HTML link).
**Postman path:** `GET /bookings/{booking_id}/receipt`
**Priority:** P1 - high
**Postman folder:** `03. Booking Flow`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `PUT /bookings/{id}/cancel`

**Description:** Cancel booking. Refund logic depends on cancellation_policy.
**Postman path:** `PUT /bookings/{booking_id}/cancel`
**Priority:** P1 - critical
**Postman folder:** `03. Booking Flow`
**Confidence:** `high`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ reason?: string }`. Backend updates `booking.status = cancelled`, `cancelled_at`, may trigger refund if eligible (refund_status updated).

**Request body:**

```json
{
  "reason": "ظرف طارئ"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم إلغاء الحجز",
  "data": {
    "id": 1,
    "booking_code": "BK-2026-0001",
    "qr_code": "DAQ-BOOKING-00000001",
    "user_id": 1,
    "captain_id": null,
    "team_id": null,
    "subscription_id": null,
    "venue": {
      "id": 1,
      "slug": "al-jaish-stadium",
      "name": "ملعب الجلاء",
      "club": {
        "id": 1,
        "name": "نادي الجلاء",
        "slug": "al-jaish-club"
      }
    },
    "sport_category": {
      "id": 1,
      "name": "ملاعب كرة قدم",
      "slug": "football-pitches"
    },
    "booking_date": "2026-04-28",
    "start_time": "18:00",
    "end_time": "20:00",
    "starts_at": "2026-04-28T18:00:00Z",
    "ends_at": "2026-04-28T20:00:00Z",
    "duration_minutes": 120,
    "duration_hours": 2,
    "status": "cancelled",
    "payment_status": "paid",
    "refund_status": "none",
    "venue_price": 60000,
    "discount_amount": 0,
    "commission_amount": 6000,
    "club_payout_amount": 54000,
    "total_amount": 60000,
    "total_price": 60000,
    "paid_amount": 60000,
    "remaining_amount": 0,
    "currency": "SYP",
    "is_recurring": false,
    "is_group_booking": false,
    "group_size": null,
    "is_split_payment": false,
    "split_method": null,
    "checked_in_at": null,
    "cancelled_at": "2026-04-26T10:00:00Z",
    "cancellation_reason": "تعارض مع موعد",
    "notes": null,
    "reschedule_count": 0,
    "applied_promotion_id": null,
    "created_at": "2026-04-26T04:00:00Z",
    "updated_at": "2026-04-26T08:00:00Z"
  }
}
```

---

### `PUT /bookings/{id}/reschedule`

**Description:** Reschedule booking to a different date/time.
**Postman path:** `PUT /bookings/{booking_id}/reschedule`
**Priority:** P1 - high
**Postman folder:** `03. Booking Flow`
**Confidence:** `high`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ new_date, new_start_time, new_end_time }`. Backend validates new slot is available, updates booking, increments `reschedule_count`.

**Request body:**

```json
{
  "new_slot_date": "2026-04-30",
  "new_start_time": "18:00",
  "new_end_time": "19:00",
  "reason": "تعارض مع عمل"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم تغيير موعد الحجز",
  "data": {
    "id": 1,
    "booking_code": "BK-2026-0001",
    "qr_code": "DAQ-BOOKING-00000001",
    "user_id": 1,
    "captain_id": null,
    "team_id": null,
    "subscription_id": null,
    "venue": {
      "id": 1,
      "slug": "al-jaish-stadium",
      "name": "ملعب الجلاء",
      "club": {
        "id": 1,
        "name": "نادي الجلاء",
        "slug": "al-jaish-club"
      }
    },
    "sport_category": {
      "id": 1,
      "name": "ملاعب كرة قدم",
      "slug": "football-pitches"
    },
    "booking_date": "2026-04-28",
    "start_time": "18:00",
    "end_time": "20:00",
    "starts_at": "2026-04-28T18:00:00Z",
    "ends_at": "2026-04-28T20:00:00Z",
    "duration_minutes": 120,
    "duration_hours": 2,
    "status": "confirmed",
    "payment_status": "paid",
    "refund_status": "none",
    "venue_price": 60000,
    "discount_amount": 0,
    "commission_amount": 6000,
    "club_payout_amount": 54000,
    "total_amount": 60000,
    "total_price": 60000,
    "paid_amount": 60000,
    "remaining_amount": 0,
    "currency": "SYP",
    "is_recurring": false,
    "is_group_booking": false,
    "group_size": null,
    "is_split_payment": false,
    "split_method": null,
    "checked_in_at": null,
    "cancelled_at": null,
    "cancellation_reason": null,
    "notes": null,
    "reschedule_count": 1,
    "applied_promotion_id": null,
    "created_at": "2026-04-26T04:00:00Z",
    "updated_at": "2026-04-26T08:00:00Z"
  }
}
```

---

### `POST /bookings/{id}/refund`

**Description:** Request refund for cancelled booking.
**Postman path:** `POST /bookings/{booking_id}/refund`
**Priority:** P1 - medium
**Postman folder:** `03. Booking Flow`
**Confidence:** `high`
**Auth:** Bearer token required

**Notes:** Returns `refund_status: requested`. Admin must approve; status flow: requested → approved → completed.

**Request body:**

```json
{
  "reason": "تعارض مع موعد طارئ",
  "refund_method": "wallet"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم إصدار طلب الاسترداد بنجاح",
  "data": {
    "refund_request_id": 14,
    "status": "approved",
    "approved_amount": 45000,
    "requested_amount": 60000,
    "refund_method": "wallet",
    "policy_applied": "24-48h: 75%",
    "auto_approved": true,
    "estimated_processing_time_minutes": 5
  }
}
```

---


## Phase 3 (P1): Reviews (6 endpoints)

### `GET /venues/{slug}/reviews`

**Description:** Venue reviews list (paginated, filterable).
**Postman path:** `GET /venues/{venue_slug}/reviews`
**Priority:** P1 - high
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Bearer token required

**Notes:** Query: `?rating=5`, `?has_comment=true`. Returns paginated reviews with user info.

**Response:**

```json
{
  "success": true,
  "data": {
    "reviews": [
      {
        "id": 9,
        "user_id": 12,
        "club_id": 1,
        "venue_id": null,
        "booking_id": 10,
        "rating": "4.5",
        "body": "ملعب نظيف ومجهز بشكل جيد، أنصح به",
        "comment": null,
        "pros": null,
        "cons": null,
        "helpful_count": 0,
        "club_reply": null,
        "club_replied_at": null,
        "club_replied_by": null,
        "is_anonymous": false,
        "venue_hint": null,
        "is_published": true,
        "published_at": "2026-04-21T00:11:53.000000Z",
        "hidden_at": null,
        "hidden_by": null,
        "created_at": "2026-04-21T00:11:53.000000Z",
        "updated_at": "2026-04-21T00:11:53.000000Z",
        "reports_count": 0,
        "is_hidden": 0,
        "photos": null,
        "user": {
          "id": 12,
          "name": "أيهم الأشعري",
          "bio": null,
          "interests": null,
          "email": null,
          "phone_number": "+963944111116",
          "referral_code": null,
          "phone_verified_at": null,
          "country_code": "+963",
          "firebase_uid": null,
          "firebase_provider": null,
          "remember_token": null,
          "default_state_id": null,
          "default_city_id": null,
          "address": null,
          "fcm_token": null,
          "fcm_platform": null,
          "account_status": "active",
          "block_reason": null,
          "blocked_at": null,
          "blocked_by": null,
          "unblocked_at": null,
          "onboarding_completed_at": null,
          "notifications_push_enabled": true,
          "notifications_sms_enabled": true,
          "notifications_reminders_enabled": true,
          "preferred_language": "ar",
          "timezone": "Asia/Damascus",
          "google2fa_secret": null,
          "google2fa_enabled_at": null,
          "last_login_at": null,
          "deleted_at": null,
          "created_at": "2026-04-21T00:11:52.000000Z",
   
  ... (truncated)
}
```

---

### `GET /venues/{slug}/similar`

**Description:** Related/similar venues (same category, nearby city).
**Postman path:** `GET /venues/{venue_slug}/similar`
**Priority:** P1 - medium
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 4,
      "slug": "aleppo-international-stadium",
      "club_id": 3,
      "category_id": 1,
      "name": {
        "ar": "ملعب حلب الدولي",
        "en": "Aleppo International Stadium"
      },
      "description": {
        "ar": "ملعب حلب الدولي بطاقة استيعابية 73,000 متفرج",
        "en": "Aleppo International Stadium with 73,000 capacity"
      },
      "size": null,
      "capacity": null,
      "amenities": null,
      "opening_hours": {
        "friday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "monday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "sunday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "tuesday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "saturday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "thursday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        },
        "wednesday": {
          "open": "08:00",
          "close": "22:00",
          "closed": false
        }
      },
      "latitude": "36.19000000",
      "longitude": "37.14000000",
      "avg_rating": null,
      "reviews_count": 0,
      "reports_count": 0,
      "is_flagged": 0,
      "price_from": 45000,
      "status": "active",
      "is_featured": false,
      "view_count": 0,
      "order_column": 0,
      "deleted_at": null,
      "created_at": "2026-04-21T00:11:50.000000Z",
      "updated_at": "2026-04-22T08:28:43.000000Z",
      "bookings_count": 11
    },
    {
      "id": 2,
      "slug": "al-fayhaa-stadium",
      "club_id": 1,
      "category_id": 1,
      "name": {
        "ar": "ملعب الفيحاء",
        "en": "Al-Fayhaa Stadium"
      },
      "description": {
        "
  ... (truncated)
}
```

---

### `GET /venues/{slug}/photos`

**Description:** Full venue photo gallery.
**Postman path:** `GET /venues/{venue_slug}/photos`
**Priority:** P1 - medium
**Postman folder:** `02. Venue Discovery`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "venue_id": 1,
    "venue_slug": "al-jaish-stadium",
    "venue_name_ar": "ملعب الجلاء",
    "photos": [],
    "total": 0
  }
}
```

---


**Reviews CRUD endpoints (folder `05.2 Reviews`):**

### `POST /reviews`

**Description:** Submit Review
**Priority:** P1 - medium
**Postman folder:** `05. Promotions & Reviews > 05.2 Reviews`
**Confidence:** `high`
**Auth:** Bearer token required

**Request body:**

```
{
  "booking_id": {{booking_id}},
  "rating": 5,
  "comment": "تجربة ممتازة، الملعب نظيف والموظفين متعاونين",
  "pros": ["نظيف", "موقع ممتاز", "موظفين محترمين"],
  "cons": ["مواقف محدودة"]
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم نشر تقييمك",
  "data": {
    "id": 99,
    "booking_id": 1,
    "venue_id": 1,
    "user_id": 1,
    "rating": 4.5,
    "comment": "ملعب رائع ومرافق ممتازة، أنصح به",
    "pros": [
      "الموقع ممتاز",
      "الإنارة جيدة",
      "السعر معقول"
    ],
    "cons": [
      "ازدحام في عطل الأسبوع"
    ],
    "helpful_count": 12,
    "is_helpful": false,
    "can_edit": true,
    "is_published": true,
    "is_anonymous": false,
    "photos": [],
    "user": {
      "id": 1,
      "name": "محمد علي",
      "avatar_url": null
    },
    "venue": {
      "id": 1,
      "slug": "al-jaish-stadium",
      "name": "ملعب الجلاء"
    },
    "club_reply": null,
    "club_replied_at": null,
    "created_at": "2026-04-23T10:00:00Z",
    "updated_at": "2026-04-23T10:00:00Z"
  }
}
```

---

### `GET /reviews/my-reviews`

**Description:** My Reviews
**Priority:** P1 - medium
**Postman folder:** `05. Promotions & Reviews > 05.2 Reviews`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /reviews/pending`

**Description:** Pending Reviews
**Priority:** P1 - medium
**Postman folder:** `05. Promotions & Reviews > 05.2 Reviews`
**Confidence:** `live`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `PUT /reviews/{review_id}`

**Description:** Update Review
**Priority:** P1 - medium
**Postman folder:** `05. Promotions & Reviews > 05.2 Reviews`
**Confidence:** `high`
**Auth:** Bearer token required

**Request body:**

```json
{
  "rating": 4,
  "comment": "تجربة جيدة"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم تعديل التقييم",
  "data": {
    "id": 1,
    "booking_id": 1,
    "venue_id": 1,
    "user_id": 1,
    "rating": 4.5,
    "comment": "ملعب رائع ومرافق ممتازة، أنصح به",
    "pros": [
      "الموقع ممتاز",
      "الإنارة جيدة",
      "السعر معقول"
    ],
    "cons": [
      "ازدحام في عطل الأسبوع"
    ],
    "helpful_count": 12,
    "is_helpful": false,
    "can_edit": true,
    "is_published": true,
    "is_anonymous": false,
    "photos": [],
    "user": {
      "id": 1,
      "name": "محمد علي",
      "avatar_url": null
    },
    "venue": {
      "id": 1,
      "slug": "al-jaish-stadium",
      "name": "ملعب الجلاء"
    },
    "club_reply": null,
    "club_replied_at": null,
    "created_at": "2026-04-23T10:00:00Z",
    "updated_at": "2026-04-23T10:00:00Z"
  }
}
```

---

### `DELETE /reviews/{review_id}`

**Description:** Delete Review
**Priority:** P1 - medium
**Postman folder:** `05. Promotions & Reviews > 05.2 Reviews`
**Confidence:** `high`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم حذف التقييم",
  "data": []
}
```

---

### `POST /reviews/{review_id}/helpful`

**Description:** Mark Helpful
**Priority:** P1 - medium
**Postman folder:** `05. Promotions & Reviews > 05.2 Reviews`
**Confidence:** `high`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم التصويت بنجاح",
  "data": {
    "review_id": 1,
    "is_helpful": true,
    "helpful_count": 13
  }
}
```

---


---

# 🟡 P2 — ENGAGEMENT (Retention features)

**Features that drive user engagement and retention.**

---

## Phase 8: Wallet (5 endpoints — 6 missing, see Gap Report)

**Goal:** User can top up wallet and pay bookings via wallet balance.

### `POST /wallet/topup`

**Description:** Initiate wallet top-up (Syriatel Cash or MTN Cash).
**Priority:** P2 - high
**Postman folder:** `10. Wallet & Credits`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ amount, payment_method: "syriatel_cash"|"mtn_cash", phone_number }`. Returns `PaymentInitiation` like booking payments. Flow: initiate → verify-otp → completed.

**Request body:**

```json
{
  "amount": 50000,
  "method": "syriatel",
  "phone": "{{test_phone}}"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم بدء عملية الشحن",
  "data": {
    "payment_id": 1234,
    "booking_id": 1,
    "status": "pending",
    "flow_type": "otp",
    "next_step": "verify_otp",
    "provider": "syriatel_cash",
    "provider_reference": "SYR-A1B2C3",
    "amount": 60000,
    "currency": "SYP",
    "expires_at": "2026-04-26T10:15:00Z",
    "metadata": {
      "otp_length": 6,
      "otp_resend_after": 60
    }
  }
}
```

---

### `POST /wallet/topup/verify`

**Description:** Verify top-up OTP.
**Priority:** P2 - high
**Postman folder:** `10. Wallet & Credits`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ payment_id, otp_code }`. On success, wallet balance increased.

**Request body:**

```json
{
  "payment_id": "{{wallet_topup_payment_id}}",
  "otp": "{{test_otp}}"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم شحن المحفظة بنجاح",
  "data": {
    "payment_id": 1234,
    "status": "completed",
    "credited_amount": 55000,
    "bonus_amount": 5000,
    "new_balance": 305000
  }
}
```

---

### `POST /wallet/topup/resend-otp`

**Description:** Resend OTP for top-up.
**Priority:** P2 - medium
**Postman folder:** `10. Wallet & Credits`
**Auth:** Bearer token required

**Request body:**

```json
{
  "payment_id": "{{wallet_topup_payment_id}}"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تمت إعادة إرسال OTP",
  "data": {
    "resent": true,
    "expires_in_seconds": 60
  }
}
```

---

### `GET /wallet/topup/status/{id}`

**Description:** Check top-up payment status.
**Postman path:** `GET /wallet/topup/status/{wallet_topup_payment_id}`
**Priority:** P2 - medium
**Postman folder:** `10. Wallet & Credits`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `POST /wallet/topup/cancel/{id}`

**Description:** Cancel a pending top-up.
**Postman path:** `POST /wallet/topup/cancel/{wallet_topup_payment_id}`
**Priority:** P2 - low
**Postman folder:** `10. Wallet & Credits`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم إلغاء العملية",
  "data": {
    "id": 1234,
    "booking_id": 1,
    "user_id": 1,
    "amount": 60000,
    "currency": "SYP",
    "provider": "syriatel_cash",
    "flow_type": "otp",
    "status": "cancelled",
    "provider_transaction_id": "SYR-TX-A1B2C3D4",
    "provider_reference": "SYR-A1B2C3",
    "initiated_at": "2026-04-26T04:00:00Z",
    "completed_at": "2026-04-26T04:02:00Z",
    "created_at": "2026-04-26T04:00:00Z"
  }
}
```

---


### 🔴 Wallet — Missing Endpoints (Gap Report)

Mobile expects these but they don't exist in Postman. **Backend team must add:**

### `GET /wallet/account`

**Description:** Get wallet balance + summary (balance, locked, available, total_earned, total_spent, total_topup).
**Priority:** P2 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Returns the `Wallet` entity from `entity_schemas.md`. Mobile expects this on app start to show balance in UI. **Suggested implementation:** simple GET that returns user's wallet model.

---

### `GET /wallet/transactions`

**Description:** Wallet transactions list (paginated).
**Priority:** P2 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Returns paginated `WalletTransaction[]`. Each: `{ id, type: credit|debit, credit_type, amount, balance_after, reason, description, status, processed_at }`. Mobile shows these in transactions page.

---

### `GET /wallet/settings`

**Description:** Get wallet settings (auto top-up, default amount).
**Priority:** P2 - medium
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Returns: `{ auto_topup_enabled, auto_topup_threshold, auto_topup_amount, low_balance_alert }`.

---

### `PUT /wallet/settings`

**Description:** Update wallet settings.
**Priority:** P2 - medium
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

---

### `POST /wallet/pay-booking`

**Description:** Pay a booking using wallet balance (4th payment method).
**Priority:** P2 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ booking_id }`. If `wallet.available >= booking.total`, deduct and mark booking confirmed/paid. Else return 422 with insufficient_balance error.

---


## Phase 8: Coupons (already in Promotions folder)

**Mapped to existing Postman:** Coupons in Mobile = Promotions in Postman. Mobile uses term "coupons" for user-facing copy, but the underlying entity is Promotion.

### `GET /coupons/my`

**Description:** My coupons (active/used/expired) — Mobile displays as "Coupons" tab.
**Postman path:** `GET /promotions/my-history`
**Priority:** P2 - medium
**Postman folder:** `05. Promotions & Reviews > 05.1 Promotions`
**Auth:** Bearer token required

**Notes:** **Suggestion:** Add alias route `/coupons/my` that internally calls promotion logic, OR Mobile updates DI to use `/promotions/my-history` (preferred).

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /coupons/{id}`

**Description:** Coupon detail.
**Postman path:** `GET /promotions/{promo_code}`
**Priority:** P2 - low
**Postman folder:** `05. Promotions & Reviews > 05.1 Promotions`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `POST /coupons/validate`

**Description:** Validate coupon code at booking time.
**Postman path:** `POST /promotions/{promo_code}/validate`
**Priority:** P2 - high
**Postman folder:** `05. Promotions & Reviews > 05.1 Promotions`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ code, venue_id, booking_total }`. Returns: `{ is_valid, discount_amount, error_message? }`.

**Request body:**

```json
{
  "venue_id": 1,
  "booking_amount": 300000,
  "booking_date": "2026-04-25"
}
```

**Response:**

```json
{
  "success": true,
  "message": "العرض صالح",
  "data": {
    "valid": true,
    "promotion": {
      "id": 1,
      "code": "WELCOME10",
      "slug": "welcome10",
      "name": {
        "ar": "خصم الترحيب",
        "en": "Welcome discount"
      },
      "description": {
        "ar": "خصم 10% للمستخدمين الجدد",
        "en": "10% off for new users"
      },
      "type": "percentage",
      "value": 10,
      "min_amount": 30000,
      "max_discount": 20000,
      "valid_from": "2026-04-16T10:00:00Z",
      "valid_to": "2026-05-16T10:00:00Z",
      "max_uses": 1000,
      "max_uses_per_user": 1,
      "current_uses": 124,
      "is_featured": true,
      "is_qr_promotion": false,
      "applies_to": "all",
      "image_url": null,
      "created_at": "2026-04-16T10:00:00Z"
    },
    "discount_amount": 6000,
    "applies_to_booking": true
  }
}
```

---

### `POST /coupons/redeem`

**Description:** Redeem coupon (apply to booking).
**Priority:** P2 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** **Suggestion:** This is implicit in booking creation — Mobile sends `coupon_code` in `POST /bookings`, backend validates + applies. So no separate endpoint needed if backend handles in /bookings.

---


## Phase 9: Teams (Partial coverage — see Gap Report)

**Mobile uses `{slug}` while Postman has `{team_id}` (numeric). Backend should support both, or Mobile uses ID.**

### `GET /teams`

**Description:** List teams (with `?my=true` filter for user's teams only).
**Priority:** P2 - high
**Postman folder:** `09. Teams & Group Bookings > Teams`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /teams/{id}`

**Description:** Team detail with members.
**Postman path:** `GET /teams/1`
**Priority:** P2 - high
**Postman folder:** `09. Teams & Group Bookings > Teams`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `POST /teams`

**Description:** Create new team. Captain is the authenticated user.
**Priority:** P2 - high
**Postman folder:** `09. Teams & Group Bookings > Teams`
**Auth:** Bearer token required

**Request body:**

```json
{
  "name": "Damascus FC",
  "description": "Our Friday football crew",
  "type": "regular",
  "sport_category_id": 1,
  "max_members": 10,
  "is_public": true,
  "requires_approval": false
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم إنشاء الفريق",
  "data": {
    "id": 1,
    "name": "Falcons FC",
    "description": "فريق كرة قدم ودي",
    "type": "casual",
    "sport_category_id": 1,
    "captain_id": 1,
    "max_members": 12,
    "is_public": true,
    "requires_approval": false,
    "avatar_url": null,
    "total_bookings": 8,
    "total_members": 7,
    "active_members_count": 7,
    "created_at": "2026-02-25T10:00:00Z"
  }
}
```

---

### `PUT /teams/{id}/leave`

**Description:** Leave team (member action).
**Postman path:** `PUT /teams/{team_id}/leave`
**Priority:** P2 - high
**Postman folder:** `09. Teams & Group Bookings`
**Auth:** Bearer token required

**Request body:**

```json
{
  "reason": "غادرت المدينة"
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم مغادرة الفريق",
  "data": {
    "team_id": 1,
    "left_at": "2026-04-26T10:00:00Z"
  }
}
```

---


### 🔴 Teams — Missing Endpoints (Gap Report)

### `PUT /teams/{id}`

**Description:** Update team info (name, description, type, max_members).
**Priority:** P2 - medium
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Captain-only action. **Suggestion:** Add to existing Teams folder.

---

### `DELETE /teams/{id}`

**Description:** Delete team (captain only). Soft delete.
**Priority:** P2 - medium
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

---

### `POST /teams/{id}/kick`

**Description:** Kick member from team (captain action).
**Priority:** P2 - medium
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ user_id }`. Backend validates auth user is captain.

---

### `POST /teams/{id}/transfer-captain`

**Description:** Transfer captain role to another member.
**Priority:** P2 - low
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

---

### `POST /teams/{id}/invite`

**Description:** Generate team invite link/code.
**Priority:** P2 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Returns: `{ invite_code, invite_url, expires_at }`. Used in `GET /teams/invite/{code}` to join.

---

### `GET /teams/invite/{code}`

**Description:** Resolve invite code → join team.
**Priority:** P2 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Authenticated user joining via invite code. Backend validates code, adds user as member, returns team detail.

---


## Phase 9: Sports Profile (Partial coverage — see Gap Report)

### `GET /profile/stats`

**Description:** My sports statistics (matches played, win rate, hours, etc.).
**Priority:** P2 - high
**Postman folder:** `07. Advanced Features > Player Profile & Stats`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /profile/achievements`

**Description:** My achievements (unlocked + in-progress + locked).
**Priority:** P2 - medium
**Postman folder:** `07. Advanced Features > Player Profile & Stats`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /profile/history`

**Description:** My player history (recent matches, bookings).
**Priority:** P2 - medium
**Postman folder:** `07. Advanced Features > Player Profile & Stats`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `POST /profile/bio`

**Description:** Update sports bio.
**Priority:** P2 - low
**Postman folder:** `07. Advanced Features > Player Profile & Stats`
**Auth:** Bearer token required

**Request body:**

```json
{
  "bio": "لاعب كرة قدم محترف",
  "interests": [
    "كرة القدم",
    "كرة السلة"
  ]
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم تحديث النبذة",
  "data": {
    "id": 1,
    "name": "محمد علي",
    "first_name": "محمد",
    "last_name": "علي",
    "phone_number": "+963991234567",
    "email": "user1@example.com",
    "avatar_url": null,
    "city": {
      "id": 1,
      "country_id": 1,
      "state_id": 1,
      "name": "Damascus",
      "name_ar": "دمشق",
      "latitude": 33.5138,
      "longitude": 36.2765
    },
    "language": "ar",
    "is_phone_verified": true,
    "is_email_verified": false,
    "verified_at": "2026-03-27T10:00:00Z",
    "role": "player",
    "account_status": "active",
    "preferences": {
      "notifications_push_enabled": true,
      "preferred_language": "ar"
    },
    "created_at": "2025-12-27T10:00:00Z",
    "updated_at": "2026-04-26T06:00:00Z"
  }
}
```

---


### 🔴 Sports Profile — Missing Endpoints (Gap Report)

### `GET /sports-profile/me`

**Description:** Aggregated sports profile (combines stats + achievements + recent activity).
**Priority:** P2 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Mobile fetches once for profile page. Could be aggregation of existing endpoints. **Suggestion:** Compose from /profile/stats + /profile/achievements + /bookings/past.

---

### `GET /sports-profile/weekly-activity`

**Description:** Weekly activity bar chart data.
**Priority:** P2 - medium
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Returns: `{ weeks: [{ week_start, bookings_count, hours_played }] }`. Used to render bar chart in profile.

---


## Phase 7: Notifications (mostly covered)

### `GET /notifications`

**Description:** Notifications list (paginated).
**Priority:** P2 - high
**Postman folder:** `06. Notifications & Settings > 06.1 Notifications`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `GET /notifications/unread`

**Description:** Unread notifications only.
**Priority:** P2 - high
**Postman folder:** `06. Notifications & Settings > 06.1 Notifications`
**Auth:** Bearer token required

**Notes:** Mobile uses for unread count badge.

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `PUT /notifications/{id}/read`

**Description:** Mark single notification as read.
**Postman path:** `PUT /notifications/{notification_id}/read`
**Priority:** P2 - medium
**Postman folder:** `06. Notifications & Settings > 06.1 Notifications`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم تعليم الإشعار كمقروء",
  "data": {
    "id": 1,
    "read_at": "2026-04-26T10:00:00Z"
  }
}
```

---

### `PUT /notifications/read-all`

**Description:** Mark all as read.
**Priority:** P2 - medium
**Postman folder:** `06. Notifications & Settings > 06.1 Notifications`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم تعليم كل الإشعارات كمقروءة",
  "data": {
    "updated_count": 5
  }
}
```

---

### `DELETE /notifications/{id}`

**Description:** Delete notification.
**Postman path:** `DELETE /notifications/{notification_id}`
**Priority:** P2 - low
**Postman folder:** `06. Notifications & Settings > 06.1 Notifications`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم حذف الإشعار",
  "data": []
}
```

---

### `GET /notifications/settings`

**Description:** Notification preferences.
**Priority:** P2 - medium
**Postman folder:** `06. Notifications & Settings > 06.1 Notifications`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `PUT /notifications/settings`

**Description:** Update notification preferences.
**Priority:** P2 - medium
**Postman folder:** `06. Notifications & Settings > 06.1 Notifications`
**Auth:** Bearer token required

**Request body:**

```json
{
  "settings": {
    "booking_confirmed": {
      "enabled": true,
      "push_enabled": true,
      "sms_enabled": false,
      "email_enabled": false
    },
    "booking_reminder": {
      "enabled": true,
      "push_enabled": true
    },
    "payment_received": {
      "enabled": true,
      "push_enabled": true,
      "email_enabled": true
    },
    "promo_available": {
      "enabled": false,
      "push_enabled": false
    }
  }
}
```

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---


## Phase 7: Tournaments (covered)

### `GET /events`

**Description:** Tournaments/events list. Mobile filters by `?type=tournament`.
**Priority:** P2 - medium
**Postman folder:** `16. Events & Tournaments 🎯`
**Auth:** Bearer token required

**Notes:** Postman folder uses `Events`, but it covers tournaments too. Mobile UI calls them "tournaments".

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "data": [
      {
        "id": 1,
        "club_id": 1,
        "venue_id": 1,
        "created_by": null,
        "title": "Friday Football Tournament",
        "title_ar": "بطولة كرة القدم - الجمعة",
        "description": "A weekly Friday tournament for amateur teams.",
        "description_ar": "بطولة جمعة أسبوعية لكل الفرق الهاوية.",
        "cover_image_url": null,
        "gallery_urls": null,
        "type": "tournament",
        "sport_type": "football",
        "starts_at": "2026-05-02T18:15:20.000000Z",
        "ends_at": "2026-05-02T22:15:20.000000Z",
        "registration_opens_at": null,
        "registration_closes_at": "2026-04-30T15:15:20.000000Z",
        "max_participants": 16,
        "min_participants": 8,
        "current_participants": 0,
        "registration_fee": "25000.00",
        "participant_type": "team",
        "team_size": 7,
        "prize_structure": [
          {
            "value": 200000,
            "position": 1,
            "prize_type": "cash"
          },
          {
            "value": 100000,
            "position": 2,
            "prize_type": "cash"
          },
          {
            "value": "كأس البرونز",
            "position": 3,
            "prize_type": "trophy"
          }
        ],
        "rules": null,
        "rules_ar": "1. كل فريق يتألف من 7 لاعبين\n2. مدة المباراة 30 دقيقة\n3. النتيجة بركلات الترجيح في حالة التعادل",
        "requirements": null,
        "requirements_ar": null,
        "status": "open",
        "is_featured": true,
        "is_published": true,
        "views_count": 2,
        "created_at": "2026-04-25T15:15:20.000000Z",
        "updated_at": "2026-04-26T10:17:53.000000Z",
        "club": {
          "id": 1,
          "name": {
            "ar": "نادي الجلاء",
            "en": "Al-Jaish Club"
          },
          "slug": "al-jaish-club"
        },
        "venue": {
          "id": 1,
          "name": {
            "ar":
  ... (truncated)
}
```

---

### `GET /events/{id}`

**Description:** Tournament/event detail.
**Postman path:** `GET /events/{event_id}`
**Priority:** P2 - medium
**Postman folder:** `16. Events & Tournaments 🎯`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "id": 1,
    "title": "Friday Football Tournament",
    "title_ar": "بطولة كرة القدم - الجمعة",
    "description": "A weekly Friday tournament for amateur teams.",
    "description_ar": "بطولة جمعة أسبوعية لكل الفرق الهاوية.",
    "cover_image_url": null,
    "gallery_urls": [],
    "type": "tournament",
    "sport_type": "football",
    "starts_at": "2026-05-02T18:15:20+00:00",
    "ends_at": "2026-05-02T22:15:20+00:00",
    "registration_closes_at": "2026-04-30T15:15:20+00:00",
    "max_participants": 16,
    "current_participants": 0,
    "remaining_spots": 16,
    "registration_fee": 25000,
    "participant_type": "team",
    "team_size": 7,
    "prize_structure": [
      {
        "value": 200000,
        "position": 1,
        "prize_type": "cash"
      },
      {
        "value": 100000,
        "position": 2,
        "prize_type": "cash"
      },
      {
        "value": "كأس البرونز",
        "position": 3,
        "prize_type": "trophy"
      }
    ],
    "rules": null,
    "rules_ar": "1. كل فريق يتألف من 7 لاعبين\n2. مدة المباراة 30 دقيقة\n3. النتيجة بركلات الترجيح في حالة التعادل",
    "requirements_ar": null,
    "status": "open",
    "is_registration_open": true,
    "is_user_registered": false,
    "user_registration_status": null,
    "club": {
      "id": 1,
      "name": "نادي الجلاء",
      "slug": "al-jaish-club",
      "phone": "+963112123456"
    },
    "venue": {
      "id": 1,
      "name": "ملعب الجلاء",
      "slug": "al-jaish-stadium",
      "address": null,
      "location": {
        "latitude": 33.5138,
        "longitude": 36.2765
      }
    }
  }
}
```

---

### `POST /events/{id}/register`

**Description:** Register for tournament.
**Postman path:** `POST /events/{event_id}/register`
**Priority:** P2 - medium
**Postman folder:** `16. Events & Tournaments 🎯`
**Auth:** Bearer token required

**Request body:**

```json
{
  "team_id": null,
  "participant_info": {
    "age": 25,
    "skill_level": "intermediate"
  }
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم التسجيل في الفعالية بنجاح",
  "data": {
    "registration_id": 14,
    "registration_number": "REG-20260426-0014",
    "status": "confirmed",
    "amount_paid": 25000,
    "event_id": 1
  }
}
```

---

### `GET /events/registered`

**Description:** My registered events/tournaments.
**Priority:** P2 - medium
**Postman folder:** `16. Events & Tournaments 🎯`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `DELETE /events/{id}/registration`

**Description:** Cancel tournament registration.
**Postman path:** `DELETE /events/{event_id}/registration`
**Priority:** P2 - low
**Postman folder:** `16. Events & Tournaments 🎯`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تم إلغاء التسجيل بنجاح",
  "data": {
    "registration_id": 14,
    "status": "refunded",
    "refunded_amount": 25000
  }
}
```

---


## Sprint: Maps + Cities (covered)

### `GET /cities`

**Description:** Cities list (Syria).
**Priority:** P2 - high
**⚠️ Status:** Not found in Postman, may exist under different name
**Auth:** Bearer token required

---

### `GET /cities/{id}/neighborhoods`

**Description:** Neighborhoods by city.
**Postman path:** `GET /cities/{city_id}/neighborhoods`
**Priority:** P2 - medium
**⚠️ Status:** Not found in Postman, may exist under different name
**Auth:** Bearer token required

---


### Map Clusters

_(Found in Postman: `GET /geography/venues/clusters` in `13. Geography & Locations`)_

### `GET /venues/clusters`

**Description:** Map clusters by zoom level (for Google Maps clustering).
**Priority:** P2 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** **Likely missing.** Mobile uses Google Maps clustering. **Suggestion:** Returns `{ clusters: [{ lat, lng, count, bounds }], venues: [...] }`. If no clustering on backend, Mobile can compute client-side from /venues/by-bounds.

---

### `GET /venues/by-bounds`

**Description:** Venues within map bounds (NE, SW corners).
**Priority:** P2 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Query: `?ne_lat=&ne_lng=&sw_lat=&sw_lng=`. Returns venues visible in current map viewport.

---


---

# 🟢 P3 — REAL-TIME (Can ship after P0-P2)

**Real-time features requiring polling or WebSocket infrastructure. Less critical for MVP launch.**

---

## Phase Matches: Football Live Score (43 endpoints in folder 12)

**Mobile uses 12 of these for the Matches tab.**

Polling strategy:

- Live matches list: poll every 30s

- Match detail (live): poll every 15s

- Upcoming/Past: fetch once, refresh on pull-to-refresh


### `GET /football/matches/today`

**Description:** Today's matches.
**Priority:** P3 - high
**Postman folder:** `12. Football Matches & Favorites > 12.1 Schedule & Fixtures`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "date": "2026-04-26",
    "matches": [],
    "total": 0,
    "cached_at": "2026-04-26T10:19:49+00:00"
  }
}
```

---

### `GET /football/matches/upcoming`

**Description:** Upcoming matches.
**Priority:** P3 - high
**Postman folder:** `12. Football Matches & Favorites > 12.1 Schedule & Fixtures`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "matches": [],
    "total": 0,
    "days": 7
  }
}
```

---

### `GET /football/matches/yesterday`

**Description:** Past matches with results.
**Priority:** P3 - medium
**Postman folder:** `12. Football Matches & Favorites > 12.1 Schedule & Fixtures`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "date": "2026-04-25",
    "matches": [],
    "total": 0
  }
}
```

---

### `GET /football/matches/{slug}`

**Description:** Match detail.
**Postman path:** `GET /football/matches/{match_external_id}`
**Priority:** P3 - high
**Postman folder:** `12. Football Matches & Favorites > 12.1 Schedule & Fixtures`
**Auth:** Bearer token required

**Notes:** When live, Mobile polls every 15s. When finished, cache aggressively.

**Response:**

```json
{
  "success": false,
  "message": "Match not found",
  "errors": null
}
```

---

### `GET /football/live/matches`

**Description:** Currently live matches (most important endpoint for Live tab).
**Priority:** P3 - high
**Postman folder:** `12. Football Matches & Favorites > 12.2 Live Score Layer`
**Auth:** Bearer token required

**Notes:** Mobile polls every 30s. Returns matches with `status: live` and current minute.

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "matches": [],
    "total": 0,
    "updated_at": "2026-04-26T10:19:49+00:00",
    "next_update_in_seconds": 30
  }
}
```

---

### `GET /football/live/matches/{id}`

**Description:** Live match detail with current score.
**Postman path:** `GET /football/live/matches/{fixture_id}`
**Priority:** P3 - high
**Postman folder:** `12. Football Matches & Favorites > 12.2 Live Score Layer`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Match not found",
  "errors": null
}
```

---

### `GET /football/live/matches/{id}/events`

**Description:** Match events timeline (goals, cards, substitutions).
**Postman path:** `GET /football/live/matches/{fixture_id}/events`
**Priority:** P3 - high
**Postman folder:** `12. Football Matches & Favorites > 12.2 Live Score Layer`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "events": []
  }
}
```

---

### `GET /football/live/matches/{id}/lineups`

**Description:** Team lineups.
**Postman path:** `GET /football/live/matches/{fixture_id}/lineups`
**Priority:** P3 - medium
**Postman folder:** `12. Football Matches & Favorites > 12.2 Live Score Layer`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "lineups": []
  }
}
```

---

### `GET /football/live/matches/{id}/statistics`

**Description:** Live match statistics (possession, shots, etc.).
**Postman path:** `GET /football/live/matches/{fixture_id}/statistics`
**Priority:** P3 - medium
**Postman folder:** `12. Football Matches & Favorites > 12.2 Live Score Layer`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": {
    "statistics": []
  }
}
```

---

### `GET /football/leagues`

**Description:** Leagues list.
**Priority:** P3 - medium
**Postman folder:** `12. Football Matches & Favorites > 12.3 Leagues / Competitions`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": null,
  "data": [
    {
      "id": 1,
      "external_id": "placeholder_PL",
      "code": "PL",
      "name": "Premier League",
      "name_ar": "الدوري الإنجليزي الممتاز",
      "country": "England",
      "country_ar": "إنجلترا",
      "emblem_url": null,
      "type": "LEAGUE",
      "is_featured": true,
      "current_season_start": null,
      "current_season_end": null
    },
    {
      "id": 2,
      "external_id": "placeholder_PD",
      "code": "PD",
      "name": "La Liga",
      "name_ar": "الدوري الإسباني",
      "country": "Spain",
      "country_ar": "إسبانيا",
      "emblem_url": null,
      "type": "LEAGUE",
      "is_featured": true,
      "current_season_start": null,
      "current_season_end": null
    },
    {
      "id": 3,
      "external_id": "placeholder_CL",
      "code": "CL",
      "name": "UEFA Champions League",
      "name_ar": "دوري أبطال أوروبا",
      "country": "Europe",
      "country_ar": "أوروبا",
      "emblem_url": null,
      "type": "LEAGUE",
      "is_featured": true,
      "current_season_start": null,
      "current_season_end": null
    },
    {
      "id": 4,
      "external_id": "placeholder_BL1",
      "code": "BL1",
      "name": "Bundesliga",
      "name_ar": "الدوري الألماني",
      "country": "Germany",
      "country_ar": "ألمانيا",
      "emblem_url": null,
      "type": "LEAGUE",
      "is_featured": true,
      "current_season_start": null,
      "current_season_end": null
    },
    {
      "id": 5,
      "external_id": "placeholder_SA",
      "code": "SA",
      "name": "Serie A",
      "name_ar": "الدوري الإيطالي",
      "country": "Italy",
      "country_ar": "إيطاليا",
      "emblem_url": null,
      "type": "LEAGUE",
      "is_featured": true,
      "current_season_start": null,
      "current_season_end": null
    },
    {
      "id": 6,
      "external_id": "placeholder_FL1",
      "code": "FL1",
      "name": "Ligue 1",
      "name_ar": "الدوري الفرنسي",
      "cou
  ... (truncated)
}
```

---

### `GET /football/leagues/{id}/standings`

**Description:** League standings table.
**Postman path:** `GET /football/leagues/{league_id}/standings`
**Priority:** P3 - medium
**⚠️ Status:** Not found, may need investigation
**Auth:** Bearer token required

---

### `GET /football/teams`

**Description:** Football teams list.
**Priority:** P3 - medium
**⚠️ Status:** Not found, may need investigation
**Auth:** Bearer token required

---


### Favorite Teams & Followed Leagues (auth-required)

### `POST /football/teams/{id}/favorite`

**Description:** Favorite a football team.
**Postman path:** `POST /football/teams/{team_id}/favorite`
**Priority:** P3 - low
**⚠️ Status:** Not found, may need investigation
**Auth:** Bearer token required

---

### `GET /football/teams/favorites`

**Description:** My favorite teams.
**Priority:** P3 - low
**⚠️ Status:** Not found, may need investigation
**Auth:** Bearer token required

---

### `POST /football/leagues/{id}/follow`

**Description:** Follow a league.
**Postman path:** `POST /football/leagues/{league_id}/follow`
**Priority:** P3 - low
**⚠️ Status:** Not found, may need investigation
**Auth:** Bearer token required

---


## Phase Matches: Waitlist + Deals

### `GET /waitlist`

**Description:** My waitlist entries.
**Priority:** P3 - medium
**Postman folder:** `03. Booking Flow`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---

### `POST /waitlist`

**Description:** Join waitlist for a fully-booked slot.
**Priority:** P3 - medium
**Postman folder:** `03. Booking Flow`
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ venue_id, date, start_time, end_time }`. When the original booking is cancelled, backend triggers FCM to wait-listed users in queue order.

**Request body:**

```json
{
  "venue_id": 1,
  "booking_date": "2026-04-25",
  "start_time": "18:00",
  "duration_hours": 2
}
```

**Response:**

```json
{
  "success": true,
  "message": "تمت إضافتك إلى قائمة الانتظار",
  "data": {
    "id": 1,
    "status": "waiting",
    "position": 2
  }
}
```

---

### `DELETE /waitlist/{id}`

**Description:** Leave waitlist.
**Postman path:** `DELETE /waitlist/1`
**Priority:** P3 - low
**Postman folder:** `03. Booking Flow`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": true,
  "message": "تمت إزالتك من قائمة الانتظار",
  "data": []
}
```

---


### Flash Deals (mapped to Promotions)

### `GET /promotions`

**Description:** Active promotions/deals (Mobile UI calls them "Flash Deals").
**Priority:** P3 - medium
**Postman folder:** `05. Promotions & Reviews > 05.1 Promotions`
**Auth:** Bearer token required

**Notes:** Filter: `?type=flash_deal`. Each has `expires_at` for countdown timer.

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "code": "WELCOME20",
      "slug": "welcome20",
      "name": {
        "ar": "خصم الترحيب",
        "en": "Welcome Discount"
      },
      "description": {
        "ar": "خصم 20% على أول حجز",
        "en": "20% off your first booking"
      },
      "type": "percentage",
      "value": 20,
      "min_amount": null,
      "max_discount": 100000,
      "venue": null,
      "applies_to": "all",
      "is_featured": true,
      "first_booking_only": true,
      "allowed_days": null,
      "image_url": null,
      "valid_from": "2026-04-22T13:27:54.000000Z",
      "valid_to": "2026-07-23T13:27:54.000000Z",
      "max_uses": 1000,
      "current_uses": 0,
      "max_uses_per_user": 1,
      "status": "active",
      "is_active": true
    },
    {
      "id": 2,
      "code": "SUMMER30",
      "slug": "summer30",
      "name": {
        "ar": "صيف حار",
        "en": "Hot Summer"
      },
      "description": {
        "ar": "خصم 30% على جميع الحجوزات",
        "en": "30% off all bookings"
      },
      "type": "percentage",
      "value": 30,
      "min_amount": null,
      "max_discount": 150000,
      "venue": null,
      "applies_to": "all",
      "is_featured": true,
      "first_booking_only": false,
      "allowed_days": null,
      "image_url": null,
      "valid_from": "2026-04-22T13:27:54.000000Z",
      "valid_to": "2026-06-23T13:27:54.000000Z",
      "max_uses": 500,
      "current_uses": 0,
      "max_uses_per_user": 1,
      "status": "active",
      "is_active": true
    },
    {
      "id": 3,
      "code": "WEEKEND50K",
      "slug": "weekend50k",
      "name": {
        "ar": "عطلة نهاية الأسبوع",
        "en": "Weekend Deal"
      },
      "description": {
        "ar": "خصم 50,000 ل.س على حجوزات عطلة نهاية الأسبوع",
        "en": "50,000 SYP off weekend bookings"
      },
      "type": "fixed_amount",
      "value": 50000,
      "min_amount": 200000,
      "max_discount": null,
      "venue": null,
      "app
  ... (truncated)
}
```

---

### `GET /promotions/{code}`

**Description:** Deal detail.
**Postman path:** `GET /promotions/{promo_code}`
**Priority:** P3 - medium
**Postman folder:** `05. Promotions & Reviews > 05.1 Promotions`
**Auth:** Bearer token required

**Response:**

```json
{
  "success": false,
  "message": "Unauthenticated.",
  "errors": null
}
```

---


## Phase 10: Chat (Pusher) — 🔴 MAJOR GAPS

**Status:** Postman has only 4 endpoints under `Social Features` folder, none specifically for chat.

**Mobile expects:** 9 chat-specific endpoints + Pusher channel auth.


### `GET /conversations`

**Description:** List user's chat conversations (DM + group + team).
**Priority:** P3 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Returns paginated list. Each: `{ channel_id, type: dm|group|team, name, avatar_url, last_message, unread_count, members[] }`.

---

### `GET /conversations/{channelId}`

**Description:** Single conversation detail.
**Priority:** P3 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

---

### `GET /conversations/{channelId}/messages`

**Description:** Messages history (paginated, newest first).
**Priority:** P3 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Query: `?before_id=`, `?limit=50`. Each message: `{ id, channel_id, sender, content, type: text|image|booking|system, attachments[], read_by[], created_at }`.

---

### `POST /messages`

**Description:** Send message.
**Priority:** P3 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Mobile sends: `{ channel_id, content, type, attachments? }`. Returns the created message. Backend also broadcasts via Pusher to all channel members.

---

### `POST /messages/{id}/mark-read`

**Description:** Mark message as read.
**Priority:** P3 - medium
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

---

### `POST /pusher/auth`

**Description:** Pusher private channel auth (REQUIRED for Pusher to work).
**Priority:** P3 - critical
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** **CRITICAL:** Pusher private channels require backend auth. Mobile sends `{ socket_id, channel_name }`. Backend validates user has access to channel, returns Pusher auth signature. Without this, real-time chat does NOT work.

---

### `POST /conversations/{id}/mute`

**Description:** Mute conversation (no notifications).
**Priority:** P3 - low
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

---

### `POST /conversations/{id}/leave`

**Description:** Leave conversation (group/team only, not DM).
**Priority:** P3 - medium
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

---

### `GET /chat/unread-summary`

**Description:** Total unread count across all conversations.
**Priority:** P3 - high
**🔴 Status:** GAP — backend team needs to add this
**Auth:** Bearer token required

**Notes:** Used for badge on bottom nav chat icon. Returns: `{ total_unread, by_channel: [...] }`.

---


---

# 📊 Gap Report — Endpoints Backend Team Must Add

This section lists all endpoints Mobile expects but are NOT in the current INFERRED Postman collection.

---

## 🔴 Summary of Gaps


| Phase | Gap Count | Priority | Description |
|-------|-----------|----------|-------------|
| Wallet | 5 | P2 high | Account, transactions, settings, pay-booking |
| Coupons | 1 | P2 high | Redemption (or roll into /bookings) |
| Teams | 6 | P2 medium | Update, delete, kick, transfer, invite, join via code |
| Sports Profile | 2 | P2 high | Aggregated profile, weekly activity |
| Map Clusters | 2 | P2 high | Clusters by zoom, by-bounds queries |
| Chat (Pusher) | 9 | P3 high | All chat endpoints + Pusher auth |
| Waitlist | 0 | P3 medium | ✅ Already exists in Postman |
| **TOTAL** | **25** | | |


## 🔍 Detailed Gap Listing


### 1. Wallet (5 gaps)

| Method | Path | Why Mobile needs it |
|--------|------|---------------------|
| `GET` | `/wallet/account` | Show balance + summary on app start |
| `GET` | `/wallet/transactions` | Transactions history page |
| `GET` | `/wallet/settings` | Auto-topup configuration |
| `PUT` | `/wallet/settings` | Update auto-topup |
| `POST` | `/wallet/pay-booking` | 4th payment method (after Syriatel/MTN/Cash) |

**Suggested implementation:**
```php
// routes/api.php
Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
    Route::get('account', [WalletController::class, 'show']);
    Route::get('transactions', [WalletController::class, 'transactions']);
    Route::get('settings', [WalletController::class, 'getSettings']);
    Route::put('settings', [WalletController::class, 'updateSettings']);
    Route::post('pay-booking', [WalletController::class, 'payBooking']);
});
```

**Response shapes:** See `entity_schemas.md` for `Wallet` and `WalletTransaction`.

---

### 2. Coupons (1 gap — minor)

Mobile UI uses term "Coupons" but backend has "Promotions" — mostly compatible.

| Method | Path | Resolution |
|--------|------|-----------|
| `POST` | `/coupons/redeem` | Either: (a) backend adds alias route, OR (b) Mobile uses `coupon_code` field in `POST /bookings` (preferred — already implicit) |

**Recommendation:** Mobile already sends `coupon_code` in booking creation. Backend should validate + apply at that point. No separate redeem endpoint needed.

---

### 3. Teams (6 gaps)

The current Postman has Teams as basic create/list/leave only. Mobile uses team management heavily.

| Method | Path | Description |
|--------|------|-------------|
| `PUT` | `/teams/{id}` | Update team info |
| `DELETE` | `/teams/{id}` | Delete team (captain only, soft-delete) |
| `POST` | `/teams/{id}/kick` | Kick member (captain only) |
| `POST` | `/teams/{id}/transfer-captain` | Transfer captain role |
| `POST` | `/teams/{id}/invite` | Generate invite code/link |
| `GET` | `/teams/invite/{code}` | Resolve invite → join team |

**Suggested response for `POST /teams/{id}/invite`:**
```json
{
  "success": true,
  "data": {
    "invite_code": "TEAM-X7K9P2",
    "invite_url": "https://daqehjizly.app/teams/join/TEAM-X7K9P2",
    "expires_at": "2026-05-12T00:00:00Z",
    "max_uses": 10,
    "uses_count": 0
  }
}
```

---

### 4. Sports Profile (2 gaps)

Postman has `/profile/stats`, `/profile/achievements`, `/profile/history` separately. Mobile expects an aggregated endpoint.

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/sports-profile/me` | Aggregated profile (stats + achievements + recent matches) |
| `GET` | `/sports-profile/weekly-activity` | Weekly bar chart data |

**Suggested implementation:**
```php
Route::middleware('auth:sanctum')->prefix('sports-profile')->group(function () {
    Route::get('me', [SportsProfileController::class, 'aggregate']);
    Route::get('weekly-activity', [SportsProfileController::class, 'weeklyActivity']);
});
```

The `me` endpoint can simply compose existing `/profile/stats` + `/profile/achievements` + `/bookings/past?limit=5`.

**Weekly activity response:**
```json
{
  "success": true,
  "data": {
    "weeks": [
      { "week_start": "2026-04-28", "bookings_count": 3, "hours_played": 4 },
      { "week_start": "2026-04-21", "bookings_count": 2, "hours_played": 3 },
      { "week_start": "2026-04-14", "bookings_count": 5, "hours_played": 7 }
    ]
  }
}
```

---

### 5. Map Clusters (2 gaps)

For Google Maps clustering performance.

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/venues/clusters` | Server-side clustering by zoom level |
| `GET` | `/venues/by-bounds` | Venues within visible map bounds |

**Suggested response for `/venues/clusters`:**
```json
{
  "success": true,
  "data": {
    "clusters": [
      {
        "id": "c1",
        "lat": 33.5138, "lng": 36.2765,
        "count": 24,
        "bounds": { "ne": {...}, "sw": {...} }
      }
    ],
    "venues": [
      // Individual venues at high zoom
    ]
  }
}
```

**Note:** If implementing server-side clustering is complex, Mobile can compute clusters client-side from `/venues/by-bounds`. Backend just needs the bounds query.

---

### 6. Chat (Pusher) — 9 gaps (HIGHEST GAP)

Currently Postman has only 4 generic "Social Features" endpoints (friends, referrals). **No chat-specific endpoints exist.**

Mobile has Phase 10 fully built mock-first with Pusher placeholder. To wire real chat, backend needs:

| Method | Path | Critical? |
|--------|------|-----------|
| `GET` | `/conversations` | Yes |
| `GET` | `/conversations/{channelId}` | Yes |
| `GET` | `/conversations/{channelId}/messages` | Yes |
| `POST` | `/messages` | Yes |
| `POST` | `/messages/{id}/mark-read` | Yes |
| `POST` | `/pusher/auth` | **🔴 CRITICAL** — Pusher won't work without it |
| `POST` | `/conversations/{id}/mute` | Optional |
| `POST` | `/conversations/{id}/leave` | Yes (for groups/teams) |
| `GET` | `/chat/unread-summary` | Yes (for badge) |

**Pusher Auth is critical:**
```php
// POST /pusher/auth
public function authenticatePusher(Request $request)
{
    $request->validate([
        'socket_id' => 'required',
        'channel_name' => 'required'
    ]);
    
    $channelName = $request->channel_name;
    $userId = auth()->id();
    
    // Validate user has access to this channel
    if (str_starts_with($channelName, 'private-team-')) {
        $teamId = (int) str_replace('private-team-', '', $channelName);
        if (!auth()->user()->teams()->where('id', $teamId)->exists()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
    }
    
    $pusher = new Pusher(...);
    $auth = $pusher->socket_auth($channelName, $request->socket_id);
    return response($auth);
}
```

**Channel naming convention (Mobile expects):**
- Direct messages: `private-dm-{user1_id}-{user2_id}` (sorted ascending)
- Team chat: `private-team-{team_id}`
- Group bookings: `private-group-{booking_id}`

**Events Mobile listens to:**
- `message.created` — new message arrived
- `message.read` — read receipt
- `member.joined` / `member.left` — group changes
- `typing.start` / `typing.stop` — typing indicators (optional)

---

### 7. FCM Token Lifecycle (1 minor gap)

Postman has `POST /devices` for registration. Mobile also expects:

| Method | Path | Description |
|--------|------|-------------|
| `DELETE` | `/devices/{token}` | Unregister FCM token (logout, app uninstall) |

If not added, Mobile silently swallows the 404 (current behavior).


## 🟡 Naming Mismatches (Mobile uses different names than Postman)


These are not "gaps" — Postman has the endpoints, but Mobile uses different paths. **Resolution: Mobile updates DI to use Postman's naming, OR backend adds alias routes.**

| Mobile expects | Postman has | Status |
|----------------|-------------|--------|
| `/auth/profile` | `/profile` | ✅ Mobile needs to change |
| `/auth/avatar` | `/profile/avatar` | ✅ Mobile needs to change |
| `/auth/fcm-token` | `/devices` | ✅ Mobile needs to change |
| `/coupons/my` | `/promotions/my-history` | ✅ Mobile changes (or backend alias) |
| `/coupons/{id}` | `/promotions/{promo_code}` | ✅ Mobile changes |
| `/coupons/validate` | `/promotions/{promo_code}/validate` | ✅ Mobile changes |
| `/sports-profile/achievements` | `/profile/achievements` | ✅ Mobile changes |
| `/sports-profile/statistics` | `/profile/stats` | ✅ Mobile changes |

**Recommendation:** Mobile team updates DI registrations to use Postman's actual paths. This avoids duplicate routes on backend.


## 📋 Action Items for Backend Team


Listed in priority order:

### Sprint 1 (P0 — Critical for MVP launch)
- [ ] Verify all 25 P0 endpoints listed above match the documented shapes
- [ ] If shape differs from documented, decide: change backend OR notify Mobile to adapt
- [ ] Confirm `challenge_uuid` flow in OTP verification

### Sprint 2 (P1 — Core)
- [ ] Verify Bookings list/detail endpoints
- [ ] Verify Reviews CRUD shapes
- [ ] Verify Venue detail returns all required fields (amenities, working hours, gallery)

### Sprint 3 (P2 — Add Wallet, Teams gaps)
- [ ] Add 5 Wallet endpoints (`/wallet/account`, `/wallet/transactions`, `/wallet/settings` GET/PUT, `/wallet/pay-booking`)
- [ ] Add 6 Teams management endpoints (update, delete, kick, transfer-captain, invite, join via code)
- [ ] Add `/sports-profile/me` aggregated endpoint
- [ ] Decide on coupon redemption strategy (separate endpoint vs. inline in booking)

### Sprint 4 (P3 — Real-time chat)
- [ ] Add 9 chat endpoints (highest gap area)
- [ ] **CRITICAL:** Implement `POST /pusher/auth` with channel access validation
- [ ] Configure Pusher app credentials and broadcast events
- [ ] Add map clustering endpoints OR document client-side approach

### Ongoing
- [ ] Update Postman collection with real response examples (replace `data: {}` with actual data) for all 12 endpoints currently missing examples
- [ ] Add response examples for newly-added endpoints
- [ ] Document rate limits per endpoint group


## 📋 Action Items for Mobile Team (Khaled)


### Naming alignment
- [ ] Update DI to use Postman's actual paths (see Mismatches section)
  - `/profile` instead of `/auth/profile`
  - `/devices` instead of `/auth/fcm-token`
  - `/promotions/*` instead of `/coupons/*` (UI keeps "Coupons" wording)
  - `/profile/stats`, `/profile/achievements` instead of `/sports-profile/*`

### Integration phases (after backend ships)
- [ ] **Phase 11 — Real Auth integration:** Replace mock with real `/auth/*` endpoints
- [ ] **Phase 12 — Real Bookings + Venues:** Wire `/venues`, `/bookings` real datasources
- [ ] **Phase 13 — Real Payments:** Wire Syriatel + MTN OTP flows
- [ ] **Phase 14 — Real Notifications:** Configure FCM with real credentials
- [ ] **Phase 15 — Real Pusher:** Wire chat with backend `/pusher/auth`
- [ ] **Phase 16 — Production hardening:** Error handling, retry logic, offline mode

### Documentation
- [ ] Update `docs/postman/inferred/entity_schemas.md` if backend response shapes differ
- [ ] Add `docs/api-integration-guide.md` with real backend URL conventions


---

# 📚 Appendix A — Entity Schemas (Canonical)

These are the canonical entity shapes used across all responses, derived from actual Laravel models, resources, and migrations.

**Source of truth:** `entity_schemas.md` shipped with the INFERRED Postman collection.


---

# Entity Schemas (TypeScript-style)

These are the canonical shapes used across the inferred Postman responses.
All are derived from the actual Laravel models / resources / migrations.

```ts
interface User {
  id: number;
  name: string;
  first_name: string;
  last_name: string;
  phone_number: string;        // E.164, e.g. "+963991234567"
  email: string | null;
  avatar_url: string | null;
  city: City | null;
  language: "ar" | "en";
  is_phone_verified: boolean;
  is_email_verified: boolean;
  verified_at: string | null;  // ISO 8601
  role: "player" | "club_manager" | "club_staff" | "admin";
  account_status: "active" | "blocked" | "suspended" | "pending_profile_completion";
  preferences: Record<string, unknown>;
  created_at: string;
  updated_at: string;
}

interface AuthTokenResponse {
  user: User;
  access_token: string;
  token_type: "Bearer";
  expires_in: number;          // seconds
  refresh_token: string | null;
}

interface City {
  id: number;
  country_id: number;
  state_id: number;
  name: string;
  name_ar: string;
  latitude: number;
  longitude: number;
}

interface Venue {
  id: number;
  slug: string;
  name: { ar: string; en: string } | string;
  description: { ar: string; en: string } | string;
  category: { id: number; slug: string; name: string };
  club: { id: number; slug: string; name: string; city: City };
  location: { latitude: number | null; longitude: number | null };
  main_image_url: string | null;
  pricing: { price_from: number; currency: "SYP" };
  rating: number | null;
  reviews_count: number;
  is_favorite: boolean;
  is_featured: boolean;
  is_open_now: boolean;
  view_count: number;
  distance_km: number | null;
  status: "active" | "inactive" | "suspended";
}

interface Club {
  id: number;
  slug: string;
  name: string;
  name_ar: string;
  description_ar: string | null;
  logo_url: string | null;
  cover_image_url: string | null;
  city: City | null;
  address: string | null;
  phone: string | null;
  venues_count: number;
  followers_count: number;
  rating: number | null;
  reviews_count: number;
  is_featured: boolean;
  is_followed: boolean;
  location: { latitude: number; longitude: number };
}

interface Booking {
  id: number;
  booking_code: string;        // BK-YYYY-NNNN
  qr_code: string;
  user_id: number;
  captain_id: number | null;
  team_id: number | null;
  subscription_id: number | null;
  venue: { id: number; slug: string; name: string; club: Pick<Club, "id"|"name"|"slug"> };
  sport_category: { id: number; name: string; slug: string };
  booking_date: string;        // YYYY-MM-DD
  start_time: string;          // HH:MM
  end_time: string;
  starts_at: string;           // ISO 8601
  ends_at: string;
  duration_minutes: number;
  duration_hours: number;
  status: "pending_payment" | "confirmed" | "checked_in" | "completed" | "cancelled" | "no_show" | "expired";
  payment_status: "unpaid" | "partial" | "paid" | "refunded" | "failed";
  refund_status: "none" | "requested" | "approved" | "completed" | "rejected";
  venue_price: number;
  discount_amount: number;
  commission_amount: number;
  club_payout_amount: number;
  total_amount: number;        // == total_price
  total_price: number;
  paid_amount: number;
  remaining_amount: number;
  currency: "SYP";
  is_recurring: boolean;
  is_group_booking: boolean;
  group_size: number | null;
  is_split_payment: boolean;
  split_method: "equal" | "custom" | null;
  checked_in_at: string | null;
  cancelled_at: string | null;
  cancellation_reason: string | null;
  reschedule_count: number;
  applied_promotion_id: number | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

type PaymentFlowType = "otp" | "redirect" | "qr_code" | "manual_confirmation";
type PaymentNextStep = "verify_otp" | "redirect_to_url" | "scan_qr"
                     | "wait_for_confirmation" | "completed";
type PaymentProvider = "syriatel_cash" | "mtn_cash" | "bank_transfer"
                     | "cash_at_venue" | "wallet";
type PaymentStatus = "pending" | "processing" | "completed" | "failed"
                   | "cancelled" | "expired";

interface PaymentInitiation {
  payment_id: number;
  booking_id: number;
  status: PaymentStatus;
  flow_type: PaymentFlowType;
  next_step: PaymentNextStep;
  provider: PaymentProvider;
  provider_reference: string;
  amount: number;              // SYP integer
  currency: "SYP";
  expires_at: string;          // ISO 8601
  metadata: Record<string, unknown>;
}

interface Review {
  id: number;
  booking_id: number;
  venue_id: number;
  user_id: number;
  rating: number;              // 1.0–5.0
  comment: string | null;
  pros: string[] | null;
  cons: string[] | null;
  helpful_count: number;
  is_helpful: boolean;
  can_edit: boolean;
  is_published: boolean;
  is_anonymous: boolean;
  photos: string[];            // URLs
  user: Pick<User, "id"|"name"|"avatar_url"> | null;
  venue: { id: number; slug: string; name: string };
  club_reply: string | null;
  club_replied_at: string | null;
  created_at: string;
  updated_at: string;
}

interface Wallet {
  id: number;
  user_id: number;
  balance: number;
  locked: number;
  available: number;           // balance - locked
  total_earned: number;
  total_spent: number;
  total_topup: number;
  currency: "SYP";
  created_at: string;
}

interface WalletTransaction {
  id: number;
  wallet_id: number;
  type: "credit" | "debit";
  credit_type: "topup" | "promotional" | "referral" | "refund"
             | "transfer_in" | "transfer_out" | "booking" | "withdrawal"
             | "bonus" | "event_registration";
  amount: number;
  balance_after: number;
  reason: string;
  description: string;
  reference_type: string | null;
  reference_id: number | null;
  status: "pending" | "completed" | "reversed";
  processed_at: string;
  created_at: string;
}

interface Notification {
  id: number;
  type: string;                // e.g. "App\Notifications\Booking\BookingConfirmed"
  data: Record<string, unknown>;
  read_at: string | null;
  created_at: string;
}

interface Subscription {
  id: number;
  user_id: number;
  venue: Pick<Venue, "id"|"slug"|"name">;
  frequency: "daily" | "weekly" | "biweekly" | "monthly";
  interval: number;
  day_of_week: number | null;  // 0–6 (Sun–Sat)
  day_of_month: number | null;
  start_time: string;          // HH:MM
  duration_hours: number;
  start_date: string;          // YYYY-MM-DD
  end_date: string | null;
  status: "active" | "paused" | "cancelled" | "completed";
  auto_pay: boolean;
  price_per_booking: number;
  discount_percentage: number;
  next_booking_date: string;
  next_charge_date: string;
  total_bookings_created: number;
  pause_count: number;
  paused_at: string | null;
  cancelled_at: string | null;
  created_at: string;
}

interface Team {
  id: number;
  name: string;
  description: string | null;
  type: "casual" | "regular" | "competitive";
  sport_category_id: number;
  captain_id: number;
  max_members: number;
  is_public: boolean;
  requires_approval: boolean;
  avatar_url: string | null;
  total_bookings: number;
  total_members: number;
  active_members_count: number;
  created_at: string;
}

interface SupportTicket {
  id: number;
  ticket_number: string;       // TK-YYYY-NNNN
  user_id: number;
  subject: string;
  category: "general" | "payment" | "booking" | "venue" | "account" | "other";
  priority: "low" | "medium" | "high" | "urgent";
  status: "open" | "in_progress" | "awaiting_user_reply"
        | "awaiting_agent_reply" | "resolved" | "closed";
  description: string;
  attachments: string[];
  assigned_agent_id: number | null;
  resolved_at: string | null;
  last_activity_at: string;
  messages_count: number;
  created_at: string;
}

interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

interface Envelope<T> {
  success: boolean;
  message: string | null;
  data: T;
  errors?: unknown;
  meta?: PaginationMeta;
}
```


---

# 📚 Appendix B — Postman Collection Inference Methodology



The INFERRED Postman collection was built using this methodology:

## Stats
- **Total endpoints scanned:** 293
- **Live captures kept untouched:** 62
- **Inferred responses added:** 231
  - 🟢 high-confidence: 224 (entity factory derived from live Laravel code)
  - 🟡 medium-confidence: 3 (derived from request body + URL pattern)
  - 🔴 low-confidence: 4 (best-guess based on naming + business logic)

## Confidence Labels in Postman
- `✅ 200 (live)` — untouched real captures (62 endpoints)
- `🟢 200 (inferred-high)` — entity has a live reference, very high confidence
- `🟡 200 (inferred-medium)` — derived from request body + URL pattern
- `🔴 200 (inferred-low)` — best-guess based on naming + business logic

## Entity Factories (from Laravel code)

| Entity | Source |
|--------|--------|
| User | `app/Http/Resources/UserResource.php` + `users` migration |
| Booking | `app/Models/Booking.php` + `bookings` migration |
| Review | `app/Http/Resources/ReviewResource.php` + `reviews` migration |
| Payment | `app/Models/Payment.php` + payment provider services |
| Wallet | `app/Models/Wallet.php` + `wallet_transactions` migration |
| Notification | Laravel default `notifications` table |
| Subscription | `app/Models/Subscription.php` + `subscriptions` migration |
| Team / TeamMember | `app/Models/Team.php` + `app/Models/TeamMember.php` |
| SupportTicket | `app/Models/SupportTicket.php` + `support_tickets` migration |
| Venue | live captures (Featured Venues, List Venues) |
| Club | live captures (Club Details) |
| Promotion | live captures (Featured Promotions) |
| Event | live captures + `app/Models/Event.php` |
| Football match/team/league | live captures + football-data.org public schema |


# 📚 Appendix C — Decisions Made (Contract §1)



The following inference decisions were made when the existing code didn't pin a specific shape:

| Decision | Confidence |
|----------|-----------|
| `GET /settings/data-export` → async-job pattern: `{export_id, status: 'processing', requested_at, estimated_completion, download_url, expires_at}` | 🔴 |
| `DELETE /settings/delete-account` → grace-period pattern (30-day): `{deletion_scheduled_at, deletion_effective_at, can_cancel_until, status}` | 🔴 |
| `GET /admin/v1/reports/payments` → aggregated `summary` (by_method + by_status) + paginated `transactions[]` | 🔴 |
| `GET /admin/v1/reports/wallet-flow` → `{period, totals, breakdown[]}` shape | 🔴 |
| **Payments** → contract §2 envelope with `flow_type`, `next_step`, `provider`, `metadata` | 🟢 |
| Per-provider next-step: Syriatel/MTN→`verify_otp`; Bank→`redirect_to_url`; Cash→`wait_for_confirmation`; Wallet→`completed` | 🟢 |


# 📚 Appendix D — Flutter Phase to Backend Endpoint Mapping



Quick reference: which Flutter phase uses which backend endpoints.

## Phase 1: Auth (12 endpoints)
- `/auth/otp/send`, `/auth/otp/verify`, `/auth/otp/resend`
- `/auth/register`, `/auth/google`, `/auth/logout`
- `/profile`, `/profile/avatar`, `/devices`
- `/auth/refresh` (optional)

## Phase 2: Home + Discovery (10 endpoints)
- `/content/banners`, `/content/featured`
- `/categories`
- `/venues/featured`, `/venues/popular`, `/venues/nearby`, `/venues/recently-viewed`, `/venues/search`
- `/promotions/featured`
- `/events`

## Phase 3: Venue Detail + Booking (9 endpoints)
- `/venues/{slug}`, `/venues/{slug}/availability`
- `/bookings/check-availability`, `/bookings/calculate-price`, `/bookings`
- `/venues/{slug}/reviews`, `/reviews` (POST)
- `/bookings/{id}/cancel`, `/bookings/{id}/reschedule`

## Sprint: Maps + Filters + Settings (4 endpoints)
- `/venues/clusters`, `/venues/by-bounds` (gaps)
- `/profile` (PUT), `/profile/avatar`

## Phase Matches/Waitlist/Deals (12 endpoints)
- `/football/matches/today`, `/upcoming`, `/yesterday`
- `/football/matches/{slug}`, `/football/matches/{slug}/events`, `/lineups`, `/standings`
- `/waitlist` (3 ops)
- `/promotions` (deals — 2 ops)

## Phase 7: Tournaments + Notifications + FCM (8 endpoints)
- `/events`, `/events/{id}`, `/events/{id}/register`
- `/notifications`, `/notifications/{id}/read`, `/notifications/read-all`
- FCM via `/devices` POST/DELETE

## Phase 8: Wallet + Coupons (11 endpoints)
- Wallet: `/wallet/topup`, `/wallet/topup/verify`, `/wallet/account`, `/wallet/transactions`, `/wallet/settings`, `/wallet/pay-booking`
- Coupons (mapped to Promotions): `/promotions/my-history`, `/promotions/{code}`, `/promotions/{code}/validate`

## Phase 9: Teams + Sports Profile (14 endpoints)
- Teams: `/teams`, `/teams/{id}`, `/teams/{id}/leave`, `/teams/{id}/kick`, `/teams/{id}/invite`
- Sports Profile: `/profile/stats`, `/profile/achievements`, `/profile/history`, `/profile/bio`
- Aggregated: `/sports-profile/me`, `/sports-profile/weekly-activity` (gaps)

## Phase 10: Chat (Pusher) (9 endpoints — all gaps!)
- `/conversations`, `/conversations/{id}`, `/conversations/{id}/messages`
- `/messages` (POST), `/messages/{id}/mark-read`
- `/pusher/auth` (CRITICAL)
- `/conversations/{id}/mute`, `/conversations/{id}/leave`
- `/chat/unread-summary`


---

# 📞 Contact & Maintenance



This document was generated by the Mobile Team based on:
- INFERRED Postman collection (293 endpoints, 281 with response shapes)
- Flutter mock implementation (9 phases, ~520 files)
- Canonical entity schemas (`entity_schemas.md`)

**Update Triggers:**
- Backend adds new endpoint → update Postman → regenerate this doc
- Mobile changes mock data shape → update entity_schemas.md → notify backend
- Mobile adds new feature/phase → update relevant priority section

**Document version:** 1.0 (May 5, 2026)
**Mobile project location:** `~/projects/deg-ehjizly-flutter/`
**Backend project location:** `~/projects/deg-ehjizli/`

For questions or clarifications, contact the Mobile Team lead.
