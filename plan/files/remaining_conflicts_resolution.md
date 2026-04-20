# remaining_conflicts_resolution.md
## دق احجزلي — حل التعارضات المتبقية بعد جولة الـ Business Transformation

---

## السياق

بعد جولة التحليل التجاري والـ UX، نشأت تعارضات جديدة بين ما تقوله الوثائق التقنية الأصلية وما تقرر في الجولة الأخيرة. هذا الملف يحسم كل واحدة منها نهائياً.

---

## CR-019 — طريقة عرض العمولة على اللاعب

**التعارض:**
- [من implementation_spec.md §10.1] النظام يدعم `apply_as = 'added'` و`'deducted'`، كلاهما خيار.
- [من monetization_model_v2.md] القرار النهائي: العمولة دائماً على النادي (deducted). اللاعب لا يرى إضافة.

**الحل النهائي:**
`commission_configs.apply_as` = `'deducted'` هو القيمة الافتراضية والوحيدة المسموحة في Phase 1.
القيمة `'added'` تبقى في الـ Schema لأغراض مستقبلية لكن لا يُسمح بتفعيلها عبر Admin Dashboard في Phase 1. قيد على مستوى الـ application layer وليس DB.

**مسؤول التنفيذ:** Backend — `CommissionService::calculate()` يُطلق exception إذا `apply_as = 'added'` في Phase 1.

---

## CR-020 — خيار العربون مقابل الدفع الكامل

**التعارض:**
- [من implementation_spec.md §8.1] الحجز يُنشأ فقط بعد اكتمال الدفع. لا pending state.
- [من monetization_model_v2.md + transformation_decision_log T-03] إضافة خيار العربون (30-50%) مع دفع الباقي نقداً عند الوصول.

**الحل النهائي:**
العربون لا يُغيّر منطق الحجز الأساسي. التعديل هو:

```
حالة 1 — دفع كامل: الـ flow الموجود بدون تغيير.
حالة 2 — عربون: نفس الـ flow، لكن:
  - booking.total_price = السعر الكامل (snapshot)
  - booking.deposit_amount = المبلغ المدفوع الآن
  - booking.deposit_status = 'paid'
  - booking.remaining_amount = total_price - deposit_amount
  - booking.remaining_status = 'due_on_arrival'
  - الحجز يُنشأ بعد تأكيد دفع العربون = نفس الـ flow
  - النادي يرى في Dashboard: "مبلغ متبقٍّ X عند الوصول"
  - عند وصول اللاعب: Club Admin يضغط "تأكيد الاستلام"
```

**الحقول الجديدة على `bookings`:**
```sql
deposit_amount          INT UNSIGNED NOT NULL DEFAULT 0
deposit_status          ENUM('none','paid','pending') NOT NULL DEFAULT 'none'
remaining_amount        INT UNSIGNED NOT NULL DEFAULT 0
remaining_status        ENUM('none','due_on_arrival','paid','waived') NOT NULL DEFAULT 'none'
remaining_confirmed_at  TIMESTAMP NULL
remaining_confirmed_by  BIGINT UNSIGNED NULL FK → users SET NULL
```

**Commission على العربون فقط:**
`commission_amount` يُحسب على `deposit_amount` إذا كان العربون هو المدفوع فعلاً.

---

## CR-021 — Last-Minute Deals: جدول جديد

**التعارض:**
- غائب من implementation_spec.md و database_schema.md.
- مُقرَّر في club_demand_activation_strategy.md و product_scope_reset.md كـ Phase 1.

**الحل النهائي — إضافة جدول `venue_flash_deals`:**
```sql
venue_flash_deals:
  id                  BIGINT UNSIGNED PK
  venue_id            BIGINT UNSIGNED NOT NULL FK → venues(id) CASCADE
  created_by          BIGINT UNSIGNED NULL FK → users(id) SET NULL
  start_datetime      DATETIME NOT NULL
  end_datetime        DATETIME NOT NULL
  original_price      INT UNSIGNED NOT NULL
  discounted_price    INT UNSIGNED NOT NULL
  max_bookings        TINYINT UNSIGNED NULL  -- NULL = غير محدود
  bookings_count      TINYINT UNSIGNED NOT NULL DEFAULT 0
  expires_at          DATETIME NOT NULL  -- متى ينتهي العرض بغض النظر عن الحجز
  status              ENUM('active','expired','fully_booked','cancelled') NOT NULL DEFAULT 'active'
  created_at          TIMESTAMP NULL
  updated_at          TIMESTAMP NULL

  INDEX (venue_id, status, expires_at)
  INDEX (status, start_datetime)
```

**API الجديد:**
```
Club Dashboard: POST /api/club/v1/venues/{id}/flash-deals
Mobile:         GET  /api/v1/venues/{id}/flash-deals (active only)
Mobile:         GET  /api/v1/flash-deals?area_id=X&category_id=Y (اكتشاف)
```

**FCM Job:** `FlashDealNotificationJob` — يُرسل لـ:
1. اللاعبين الذين حفظوا هذا الملعب (saved_venues)
2. اللاعبين في نفس المنطقة الذين لديهم تفضيل لهذه الـ category (من سجل حجوزاتهم)

---

## CR-022 — Waitlist: جدول جديد

**التعارض:**
- غائب من implementation_spec.md.
- مُقرَّر كـ Phase 1.

**الحل النهائي — إضافة جدول `venue_waitlist`:**
```sql
venue_waitlist:
  id                BIGINT UNSIGNED PK
  venue_id          BIGINT UNSIGNED NOT NULL FK → venues(id) CASCADE
  user_id           BIGINT UNSIGNED NOT NULL FK → users(id) CASCADE
  booking_date      DATE NOT NULL
  start_time        TIME NOT NULL
  duration_minutes  SMALLINT UNSIGNED NOT NULL
  notified_at       TIMESTAMP NULL
  expires_at        TIMESTAMP NOT NULL  -- يُحذف بعد مرور يوم على التاريخ المطلوب
  created_at        TIMESTAMP NULL

  UNIQUE(venue_id, user_id, booking_date, start_time)
  INDEX(venue_id, booking_date, start_time)
```

**التعديل على VenueAvailableNotificationJob:**
عند إلغاء حجز → يُفحص أولاً `venue_waitlist` لنفس الـ venue + date + start_time → FCM لأول شخص في القائمة → إذا لم يحجز خلال 30 دقيقة → يُرسل للتالي أو ينتقل للـ saved_venues.

---

## CR-023 — تأكيد الإلغاء: حذف الـ phrase validation

**التعارض:**
- [من implementation_spec.md §8.3] "Player types 'cancel' or 'الغاء'"
- [من transformation_decision_log T-14] Confirmation dialog يكفي.

**الحل النهائي:**
حذف phrase validation من:
- Backend: `BookingService::validateCancellationPhrase()` — يُحذف
- API: `bookings/{id}/cancel` لا يتطلب `confirm_phrase` في الـ body
- يُستبدل بـ: Confirmation dialog على Mobile (مسؤولية mobile team) مع payload بسيط: `{confirmed: true}`

---

## CR-024 — WhatsApp OTP: حذف choice screen

**التعارض:**
- [من implementation_spec.md §12.1] `POST /api/v1/auth/otp/choose-channel` endpoint موجود.
- [من ux_simplification_spec.md] لا choice screen — auto-detect وأرسل.

**الحل النهائي:**
`POST /api/v1/auth/otp/request` يُرسل OTP تلقائياً عبر القناة المناسبة:
- إذا Baileys متاح وRقم عنده WhatsApp → يرسل WhatsApp تلقائياً
- إذا لا → SMS تلقائياً
`whatsapp_available` يبقى في الـ response لأغراض tracking فقط (تُسجَّل كـ event). لا choice screen يُعرض.
Endpoint `otp/choose-channel` يُلغى من Phase 1.

---

## CR-025 — Reviews: حذف minimum chars validation

**التعارض:**
- [من implementation_spec.md §5.18] "min 50 chars if provided"
- [من transformation_decision_log T-13] لا حد أدنى

**الحل النهائي:**
`reviews.body` — nullable text. إذا موجود: لا حد أدنى للحروف. لا validation على الطول.

---

## CR-026 — Recurring Booking: تأجيل لـ Phase 2

**التعارض:**
- [من implementation_spec.md] Recurring bookings في scope الإطلاق.
- [من product_scope_reset.md] تأجيل لـ Phase 2.

**الحل النهائي:**
Recurring bookings = Phase 2. تُحذف من Phase 1 scope.

**ما يبقى من الـ Schema:** الحقول `is_recurring`، `recurrence_pattern`، `recurrence_parent_id` تبقى في الـ schema (لا تكلفة) لكن لا API يستخدمها في Phase 1.

**ما يُحذف من Phase 1:**
- `ScheduledBookingPaymentReminderJob`
- `ExpireUnpaidScheduledBookingsJob`
- status `scheduled` لا يُنشأ من mobile API في Phase 1 (يبقى في schema)

---

## CR-027 — Migration Order: إضافة الجداول الجديدة

**التعارض:**
- database_schema.md لا يشمل `venue_flash_deals`, `venue_waitlist`, حقول العربون.

**الحل النهائي — إضافة للـ Migration Order:**
```
33. venue_flash_deals     [جديد — Phase 1]
34. venue_waitlist        [جديد — Phase 1]
```

و`bookings` migration يُعدَّل لإضافة حقول العربون.

