# phase1_lock.md
## دق احجزلي — Phase 1 Scope المقفل النهائي
> Version: FINAL — بعد جولة التنظيف الكامل.

---

## ما يُبنى في Phase 1 — القائمة الكاملة والنهائية

### البنية التحتية
- [ ] Laravel 13 monorepo + Docker
- [ ] MySQL 8.0+ مع utf8mb4
- [ ] Database Queue + File Cache (لا Redis)
- [ ] Spatie Permissions (seeded + dynamic)
- [ ] spatie/laravel-activitylog
- [ ] spatie/laravel-medialibrary
- [ ] spatie/laravel-translatable
- [ ] spatie/opening-hours
- [ ] spatie/laravel-settings
- [ ] Laravel Sanctum (multi-guard: api + web)
- [ ] Custom TotpService (RFC 6238, PHP native)
- [ ] Firebase FCM Service (HTTP v1, custom, no package)
- [ ] FirebaseAuthService (JWKS, no kreait)
- [ ] App Startup API

### Geography (dr5hn)
- [ ] Seeder: countries + states + cities من dr5hn SQL dump
- [ ] Admin Dashboard: activate/deactivate countries/states/cities
- [ ] Mobile API: GET countries/states/cities (active only)

### Authentication
- [ ] OTP SMS (Syriatel + MTN)
- [ ] WhatsApp OTP (Baileys — auto-detect, لا choice screen)
- [ ] Google Sign-In (Firebase JWT, JWKS)
- [ ] Account Linking (Google + Phone)
- [ ] Dashboard Login (email + password)
- [ ] Super Admin 2FA (Custom TOTP)
- [ ] Logout + FCM token clearing

### Catalog
- [ ] Venue Categories CRUD (Admin)
- [ ] Clubs CRUD + approval flow
- [ ] Venues CRUD + opening hours + pricing tiers
- [ ] Media uploads (spatie/medialibrary)

### Booking Core (Standard فقط — لا Recurring)
- [ ] Slot availability (computed on-the-fly — لا slots table)
- [ ] Slot reservation (10-min soft lock)
- [ ] Booking creation (بعد تأكيد الدفع — لا pending)
- [ ] **خيار العربون** (deposit + remaining_at_venue)
- [ ] Booking cancellation (`{confirmed: true}` — لا phrase)
- [ ] Booking history (upcoming + completed + cancelled)
- [ ] Manual bookings (Club Dashboard — external + blocked)
- [ ] Booking reminders (2h + 1h FCM)
- [ ] SMS confirmation لكل حجز مؤكد
- [ ] Booking completion scheduler

### Payments
- [ ] MTN Cash (OTP 3-step)
- [ ] Syriatel Cash (OTP 2-step)
- [ ] Fatora (WebView + callback)
- [ ] SamaPay (WebView + callback)
- [ ] Internal Wallet
- [ ] Wallet refund on cancellation
- [ ] Payment status polling API
- [ ] Club confirms remaining_amount receipt

### Commission (Phase 1 — Global Only)
- [ ] Global commission config only (7% deducted from club — default)
- [ ] Commission calculated at booking time (snapshot)
- [ ] Commission on deposit_amount only (for deposit bookings)

### Settlement (Phase 1 — Manual)
- [ ] Settlement generation by Super Admin
- [ ] settlement_items (per booking)
- [ ] Club views settlement read-only

### Demand Activation
- [ ] **Last-Minute Deals** (venue_flash_deals)
- [ ] **Waitlist** (venue_waitlist)
- [ ] Waitlist FCM on cancellation
- [ ] Flash Deal FCM to interested players

### Notifications
- [ ] FCM: booking confirmed (player + super admin + club admins)
- [ ] FCM: booking cancelled
- [ ] FCM: booking reminders (2h + 1h)
- [ ] FCM: Flash Deal alert
- [ ] FCM: Waitlist slot available
- [ ] SMS: booking confirmation (دائماً، بغض النظر عن FCM)
- [ ] FCM to mobile + Dashboard PWA

### Reviews
- [ ] Rating (1-5) per club — إجباري
- [ ] Optional text — لا حد أدنى
- [ ] Anonymous option
- [ ] One review per (player, club)
- [ ] Admin moderation (publish/hide)

### Content & Events
- [ ] Content pages (About, Privacy, Terms, Help)
- [ ] Competitions (Admin manages)
- [ ] Football Matches today/upcoming (proxy + cache)

### Player App API
- [ ] Browse venues without auth
- [ ] Full discovery + filtering + search
- [ ] Profile + settings
- [ ] Wallet view + transactions
- [ ] Saved venues

### Super Admin Dashboard
- [ ] Full system control
- [ ] Commission config (global only)
- [ ] Settlement management
- [ ] Revenue reports
- [ ] Approval queue
- [ ] Roles & Permissions
- [ ] App Startup config
- [ ] Geography management (activate/deactivate)
- [ ] **Behavioral Analytics** (tracking + basic funnels)

### Club Dashboard
- [ ] Progressive onboarding (3 أقسام في أول 7 أيام)
- [ ] Club profile
- [ ] Venue management
- [ ] Calendar view
- [ ] Manual bookings
- [ ] Revenue summary
- [ ] Settlement view (read-only)
- [ ] **Flash Deals creation**
- [ ] **Remaining amount confirmation** (Club confirms cash receipt)

### Behavioral Tracking
- [ ] `player_events` table (append-only)
- [ ] Batch tracking API (`POST /api/v1/events/track`)
- [ ] Core events (Phase 1 minimum — per observability spec)
- [ ] Admin analytics: basic funnels + D7/D30 retention

---

## ما لا يُبنى في Phase 1 — قائمة مقفلة

| الميزة | متى |
|--------|-----|
| Recurring bookings | Phase 2 |
| Request Marketplace | Phase 2 |
| Club Subscription Plans | Phase 2 |
| Featured Placement | Phase 2 |
| Commission per venue/club | Phase 2 |
| Reactivation Campaigns | Phase 2 |
| Advanced Club Analytics | Phase 2 |
| Team Captain / Group Booking | Phase 3 |
| صالات الأفراح والمناسبات (تشغيل فعلي) | Phase 3 |
| Multi-city expansion | Phase 3 |
| User-facing PWA | Phase 2 |
| Apple Sign-In | TBD |
| WhatsApp OTP choice screen | ملغى |
| Cancellation phrase validation | ملغى |
| Reviews minimum chars | ملغى |
| apply_as = 'added' | ملغى نهائياً |

