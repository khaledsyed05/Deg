# patch_instructions.md
## دق احجزلي — تعليمات تحديث الوثائق التقنية

> هذا الملف يخبر فريق التطوير بالتغييرات المطلوبة على implementation_spec.md و database_schema.md مباشرة.

---

## PATCH-001 — database_schema.md: إضافة حقول العربون على `bookings`

**الموقع:** جدول `bookings`، بعد `total_price`.

**إضافة:**
```sql
deposit_amount           INT UNSIGNED NOT NULL DEFAULT 0
                         -- المبلغ المدفوع كعربون (0 = دفع كامل)
deposit_status           ENUM('none','paid') NOT NULL DEFAULT 'none'
remaining_amount         INT UNSIGNED NOT NULL DEFAULT 0
                         -- الباقي المستحق عند الوصول
remaining_status         ENUM('none','due_on_arrival','confirmed','waived')
                         NOT NULL DEFAULT 'none'
remaining_confirmed_at   TIMESTAMP NULL
remaining_confirmed_by   BIGINT UNSIGNED NULL FK → users(id) SET NULL
```

**Index إضافي:**
```sql
INDEX (remaining_status)  -- للفلترة: هل في مبالغ متبقية غير مؤكدة؟
```

---

## PATCH-002 — database_schema.md: إضافة جدول `venue_flash_deals`

**الموقع:** بعد `venue_pricing_tiers`.

```sql
venue_flash_deals:
  id                  BIGINT UNSIGNED PK AUTO_INCREMENT
  venue_id            BIGINT UNSIGNED NOT NULL FK → venues(id) CASCADE
  created_by          BIGINT UNSIGNED NULL FK → users(id) SET NULL
  start_datetime      DATETIME NOT NULL
  end_datetime        DATETIME NOT NULL
  original_price      INT UNSIGNED NOT NULL
  discounted_price    INT UNSIGNED NOT NULL  -- CHECK: discounted_price < original_price
  max_bookings        TINYINT UNSIGNED NULL  -- NULL = غير محدود
  bookings_count      TINYINT UNSIGNED NOT NULL DEFAULT 0
  expires_at          DATETIME NOT NULL
  status              ENUM('active','expired','fully_booked','cancelled')
                      NOT NULL DEFAULT 'active'
  created_at          TIMESTAMP NULL
  updated_at          TIMESTAMP NULL

  INDEX (venue_id, status, expires_at)
  INDEX (status, start_datetime)
  CHECK (discounted_price < original_price)
  CHECK (end_datetime > start_datetime)
```

**Migration Order:** #33.

---

## PATCH-003 — database_schema.md: إضافة جدول `venue_waitlist`

**الموقع:** بعد `saved_venues`.

```sql
venue_waitlist:
  id                BIGINT UNSIGNED PK AUTO_INCREMENT
  venue_id          BIGINT UNSIGNED NOT NULL FK → venues(id) CASCADE
  user_id           BIGINT UNSIGNED NOT NULL FK → users(id) CASCADE
  booking_date      DATE NOT NULL
  start_time        TIME NOT NULL
  duration_minutes  SMALLINT UNSIGNED NOT NULL
  notified_at       TIMESTAMP NULL  -- متى أُرسل الإشعار
  expires_at        TIMESTAMP NOT NULL  -- يوم بعد booking_date
  created_at        TIMESTAMP NULL

  UNIQUE(venue_id, user_id, booking_date, start_time)
  INDEX(venue_id, booking_date, start_time, notified_at)
  INDEX(expires_at)  -- للـ cleanup scheduler
```

**Migration Order:** #34.

**Scheduler:** `venue-waitlist:cleanup` — يومياً، يحذف records WHERE `expires_at < NOW()`.

---

## PATCH-004 — database_schema.md: إضافة جدول `player_events`

**الموقع:** بعد `venue_waitlist`.

```sql
player_events:
  id              BIGINT UNSIGNED PK AUTO_INCREMENT
  user_id         BIGINT UNSIGNED NULL FK → users(id) SET NULL
                  -- NULL للمستخدمين غير المسجلين
  anonymous_id    VARCHAR(64) NULL
                  -- UUID مولّد على الجهاز قبل التسجيل
  session_id      VARCHAR(64) NOT NULL
  event_name      VARCHAR(100) NOT NULL  -- من الـ canonical event taxonomy
  properties      JSON NULL  -- الخصائص الإضافية
  occurred_at     TIMESTAMP NOT NULL  -- وقت الحدث الفعلي (من الجهاز)
  created_at      TIMESTAMP NULL  -- وقت الاستلام في الـ server

  INDEX (user_id, event_name, occurred_at)
  INDEX (event_name, occurred_at)
  INDEX (session_id)
  INDEX (anonymous_id)
  -- لا soft delete — append-only
```

**Migration Order:** #35.

**ملاحظات:**
- Append-only. لا UPDATE، لا DELETE.
- Archiving: records أقدم من 12 شهراً تُنقل لـ cold storage أو تُحذف (قرار تشغيلي لاحقاً).
- لا `updated_at`.

---

## PATCH-005 — implementation_spec.md §8.3: تعديل Cancellation Flow

**احذف:**
```
Player types "cancel" or "الغاء" (case-insensitive, hamza-insensitive)
```

**استبدل بـ:**
```
POST /bookings/{id}/cancel
Body: { "confirmed": true }
-- لا phrase validation. Mobile يعرض confirmation dialog.
```

**احذف من Backend:**
- `BookingService::validateCancellationPhrase()`
- `Arabic normalization` logic

---

## PATCH-006 — implementation_spec.md §12.1: تعديل OTP Flow

**احذف:**
```
→ If whatsapp_available: POST /api/v1/auth/otp/choose-channel {uuid, channel}
```

**استبدل بـ:**
```
POST /api/v1/auth/otp/request {phone_number}
  → Backend يفحص Baileys availability
  → إذا WhatsApp متاح → يرسل تلقائياً عبر WhatsApp
  → إذا لا → يرسل SMS تلقائياً
  → Response: {uuid, channel_used: 'whatsapp'|'sms', ...}
  → لا choice screen
```

**يُلغى:** Endpoint `POST /api/v1/auth/otp/choose-channel` — غير موجود في Phase 1.

---

## PATCH-007 — implementation_spec.md §5.18: تعديل Reviews validation

**احذف:**
```
body | TEXT NULL | min 50 chars if provided
```

**استبدل بـ:**
```
body | TEXT NULL | No minimum length
```

---

## PATCH-008 — implementation_spec.md §10.1: قفل Commission

**أضف بعد code block الـ commission:**
```
PHASE 1 CONSTRAINT:
apply_as is always 'deducted' in Phase 1.
CommissionService::calculate() throws InvalidCommissionConfigException
if apply_as = 'added' is detected.
This constraint is enforced at application layer, not DB level.
```

---

## PATCH-009 — database_schema.md: Migration Order المحدّث

```
33. venue_flash_deals
34. venue_waitlist
35. player_events
```

وتعديل migration رقم 10 (`bookings`) لإضافة حقول العربون.

---

## PATCH-010 — Scheduler: إضافة مهام جديدة

**أضف للـ Scheduled Tasks table في implementation_spec.md §16:**

| Task | Frequency | Description |
|------|-----------|-------------|
| `flash-deals:expire` | Every 5 minutes | UPDATE venue_flash_deals SET status='expired' WHERE expires_at < NOW() AND status='active' |
| `waitlist:cleanup` | Daily | DELETE venue_waitlist WHERE expires_at < NOW() |
| `player-events:archive` | Monthly | Archive events older than 6 months (TBD) |

