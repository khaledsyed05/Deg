# admin_retention_observability_spec.md
## دق احجزلي — منظومة التتبع السلوكي والـ Retention Analytics
> Super Admin فقط. النادي لا يرى هذه البيانات.

---

# 1. Purpose

هذه المنظومة موجودة لسبب واحد: نريد أن نعرف بالضبط أين يضيع اللاعب، وأين يعود، وأي قرار منتجي يحسن أو يضر.

بدون هذه البيانات، كل قرار تحسين = تخمين. مع هذه البيانات، كل تعديل على الـ UX أو الـ flow أو الـ pricing = قرار مبني على سلوك حقيقي.

**لماذا هي Admin-Only:**
البيانات السلوكية الفردية (كل خطوة عملها لاعب بعينه) هي معلومة استراتيجية للمنصة فقط. النادي لا يحتاجها ولا يجب أن يراها. النادي يرى أرقام إشغاله فقط.

---

# 2. Scope Boundary

## ما يرى Super Admin فقط
- رحلة كل لاعب فردياً (journey analysis)
- Funnel drop-off بالخطوة
- Session duration وfrequency
- Conversion من كل source
- Cohort retention
- Payment failure analysis
- Behavioral patterns مرتبطة بالعودة

## ما لا يرى النادي أبداً
- أي journey-level data لأي لاعب بعينه
- Drop-off analysis للاعبين
- Session behavior
- Source attribution
- Funnel performance عامة للمنصة

## ما يرى النادي (محدود — Phase 2)
- عدد الحجوزات الإجمالية لملاعبه
- نسبة إشغال ملاعبه (aggregate)
- الإيراد الإجمالي
- أوقات الذروة في ناديه (aggregate, not individual)

---

# 3. Canonical Event Taxonomy

**المبدأ:** كل event له هدف قرار منتجي واضح. لا نتتبع ما لا نستطيع استخدامه.

---

## 3.1 App / Session Events

| Event | متى يُطلق | Properties الأساسية | القرار المنتجي |
|-------|----------|---------------------|----------------|
| `app_opened` | عند فتح التطبيق | user_id, anonymous_id, session_id, source_channel, app_version, device_type | هل المستخدمون يعودون؟ |
| `session_started` | بداية كل جلسة نشطة | session_id, user_id, anonymous_id, app_version | كم جلسة/يوم؟ |
| `session_ended` | عند إغلاق أو background بعد 30 ثانية | session_id, session_duration_seconds, screens_viewed, last_screen | كم مدة الجلسة؟ |
| `first_open_completed` | أول مرة يفتح التطبيق | anonymous_id, device_type, city_id | كم يبقى من أول جلسة؟ |

**Properties المشتركة لكل event:**
```json
{
  "user_id": 123 | null,
  "anonymous_id": "uuid-device",
  "session_id": "uuid-session",
  "app_version": "1.0.0",
  "device_type": "android|ios",
  "occurred_at": "2026-04-04T14:30:00Z"
}
```

---

## 3.2 Discovery Events

| Event | متى يُطلق | Properties إضافية | القرار المنتجي |
|-------|----------|------------------|----------------|
| `location_selected` | اختار المنطقة/المدينة | city_id, area_id, method: 'gps'\|'manual' | هل الـ onboarding geography يعمل؟ |
| `search_started` | بدأ بالكتابة في search bar | query_length, has_filters | كم نسبة البحث من الزيارات؟ |
| `search_results_viewed` | رأى نتائج البحث | results_count, area_id, category_id, has_filters | هل النتائج كافية؟ |
| `filters_applied` | طبّق فلتر | filter_types: ['category','price','rating'], area_id | أي فلاتر تُستخدم أكثر؟ |
| `venue_list_viewed` | رأى قائمة الملاعب | area_id, category_id, results_count, sort_method | هل العرض مناسب؟ |
| `venue_card_clicked` | ضغط على بطاقة ملعب | venue_id, club_id, position_in_list, is_flash_deal | أي الملاعب تجذب أكثر؟ |
| `venue_details_viewed` | رأى صفحة الملعب | venue_id, club_id, category_id, has_available_slots | هل يخرج من هنا؟ |
| `venue_saved` | حفظ ملعب | venue_id, club_id | الحفظ يقود للحجز لاحقاً؟ |

---

## 3.3 Booking Funnel Events

| Event | متى يُطلق | Properties إضافية | القرار المنتجي |
|-------|----------|------------------|----------------|
| `slot_list_viewed` | رأى الـ slots المتاحة | venue_id, date, duration, slots_count, has_flash_deal | هل الـ slots كافية؟ |
| `slot_selected` | اختار slot | venue_id, start_time, duration_minutes, price, is_flash_deal | أي الأوقات تُختار أكثر؟ |
| `booking_payment_screen_viewed` | وصل لشاشة الدفع | venue_id, price, deposit_option_shown | هل يكمل؟ |
| `deposit_option_selected` | اختار العربون | venue_id, deposit_amount, remaining_amount, deposit_percentage | هل العربون يرفع الإتمام؟ |
| `full_payment_option_selected` | اختار الدفع الكامل | venue_id, total_price | نسبة full vs deposit؟ |
| `payment_method_selected` | اختار طريقة دفع | venue_id, payment_method, is_wallet, is_first_time | أي طرق تُكمَل أكثر؟ |
| `payment_started` | بدأ عملية الدفع | venue_id, payment_id, provider | بداية الـ payment funnel |
| `payment_otp_requested` | طلب OTP للدفع | provider | كم يكمل بعد OTP؟ |
| `payment_failed` | فشل الدفع | provider, failure_reason, attempt_number | أسباب الفشل |
| `payment_succeeded` | نجح الدفع | provider, is_deposit, amount, duration_seconds | conversion rate |
| `booking_confirmed` | تأكيد الحجز | booking_id, venue_id, source, is_deposit, is_first_booking | القيمة النهائية |
| `booking_abandoned` | خرج من شاشة الدفع | venue_id, abandonment_step, time_spent_seconds | أين يخرج؟ |

---

## 3.4 Post-Booking Events

| Event | متى يُطلق | Properties إضافية |
|-------|----------|------------------|
| `booking_cancel_initiated` | ضغط على إلغاء | booking_id, time_to_start_hours, is_deposit |
| `booking_cancel_completed` | أكمل الإلغاء | booking_id, refund_amount, time_to_start_hours |
| `rebook_clicked` | ضغط "احجز مجدداً" | previous_booking_id, venue_id |
| `rebook_completed` | أكمل إعادة الحجز | new_booking_id, previous_booking_id, same_time: bool |

---

## 3.5 Engagement / Retention Events

| Event | متى يُطلق | Properties إضافية | القرار المنتجي |
|-------|----------|------------------|----------------|
| `notification_received` | وصل الإشعار | notification_type, channel: 'fcm'\|'sms' | معدل التسليم |
| `notification_opened` | فتح من إشعار | notification_type, time_to_open_minutes | هل الإشعارات تعيد؟ |
| `waitlist_joined` | انضم لقائمة الانتظار | venue_id, booking_date, start_time | كم ينتهي بحجز؟ |
| `waitlist_alert_opened` | فتح من إشعار الـ waitlist | venue_id | conversion من waitlist؟ |
| `flash_deal_viewed` | رأى عرض last-minute | venue_id, discount_percentage | هل يحجز؟ |
| `flash_deal_booking_started` | بدأ حجز عرض | venue_id, deal_id | conversion من deals؟ |
| `wallet_viewed` | فتح صفحة الـ wallet | balance | يستخدم الـ wallet؟ |
| `wallet_used` | دفع بالـ wallet | amount, booking_id | هل الـ wallet يسرّع؟ |
| `refund_received_to_wallet` | وصل refund للـ wallet | amount, booking_id | هل يستخدم الـ refund؟ |

---

## 3.6 Auth Funnel Events

| Event | متى يُطلق | Properties إضافية |
|-------|----------|------------------|
| `login_screen_viewed` | وصل لشاشة التسجيل | trigger: 'booking_attempt'\|'direct' |
| `otp_requested` | طلب OTP | channel: 'sms'\|'whatsapp' |
| `otp_failed` | أدخل OTP خاطئ | attempt_number |
| `otp_verified` | نجح OTP | is_new_user: bool, time_to_verify_seconds |
| `google_signin_started` | ضغط Google | — |
| `google_signin_succeeded` | نجح Google | is_new_user: bool, had_phone: bool |
| `google_signin_failed` | فشل Google | failure_reason |
| `profile_completed` | أكمل الملف الشخصي | fields_filled: count |

---

# 4. Core Funnels

## Funnel 1 — Discovery → Booking → Confirmed
```
venue_list_viewed
  → venue_details_viewed          [drop-off A: هل يخرج من القائمة؟]
  → slot_list_viewed              [drop-off B: هل يصل للـ slots؟]
  → slot_selected                 [drop-off C: هل يختار وقتاً؟]
  → booking_payment_screen_viewed [drop-off D: هل يصل للدفع؟]
  → payment_started               [drop-off E: هل يبدأ الدفع؟]
  → payment_succeeded             [drop-off F: هل يكتمل الدفع؟]
  → booking_confirmed             ✅
```

**الاستخدام:** كل نقطة drop-off = قرار تحسين UX محدد.

## Funnel 2 — Auth Funnel (OTP)
```
login_screen_viewed
  → otp_requested
  → otp_verified (success) | otp_failed (up to 5x) [drop-off: OTP friction]
  → profile_completed | skipped
  → booking_started | app_explored
```

## Funnel 3 — Notification → Booking
```
notification_received (reminder/flash_deal/waitlist)
  → notification_opened
  → venue_details_viewed | booking_payment_screen_viewed
  → booking_confirmed | abandoned
```
**الاستخدام:** هل الإشعارات تُعيد اللاعبين للحجز؟ أي نوع إشعار أفضل؟

## Funnel 4 — Waitlist → Booking
```
waitlist_joined
  → waitlist_alert_opened
  → slot_selected
  → booking_confirmed | abandoned
```
**الاستخدام:** هل الـ waitlist يستعيد الطلب المفقود؟

## Funnel 5 — Flash Deal → Booking
```
flash_deal_viewed
  → flash_deal_booking_started
  → payment_succeeded
  → booking_confirmed | abandoned
```
**الاستخدام:** هل Last-Minute Deals ترفع الإتمام؟

## Funnel 6 — Deposit vs Full Payment
```
booking_payment_screen_viewed
  → deposit_option_selected | full_payment_option_selected
  → payment_succeeded | payment_failed
```
**الاستخدام:** هل العربون يرفع conversion rate؟

## Funnel 7 — Rebook Funnel
```
booking_confirmed (first)
  → [D+7, D+14, D+30] app_opened
  → rebook_clicked | venue_card_clicked (same venue)
  → rebook_completed
```
**الاستخدام:** ما مدى سرعة العودة بعد أول حجز؟

---

# 5. Retention Views

## D1/D7/D30 Retention
- من حجز في اليوم X → هل عاد وفتح التطبيق في D+1، D+7، D+30؟
- من حجز في اليوم X → هل حجز مجدداً في D+7، D+30؟
- الفرق بين retention الفتح وretention الحجز مهم جداً.

## Session Frequency
- متوسط جلسات/أسبوع لكل لاعب
- أيام الأسبوع الأكثر نشاطاً
- أوقات اليوم الأكثر نشاطاً

## Time Between Bookings
- متوسط الأيام بين حجز وحجز
- هل اللاعبون الذين استخدموا الـ waitlist يحجزون أسرع؟
- هل اللاعبون الذين استخدموا العربون يعودون أسرع؟

## Cohort Analysis
| Cohort | Dimension |
|--------|-----------|
| By Acquisition Week | من انضم في أسبوع معين → retention |
| By City/Area | هل محافظة أو منطقة أفضل retention؟ |
| By First Booking Type | deposit vs full → retention فرق؟ |
| By Auth Method | OTP vs Google → retention فرق؟ |
| By Source Channel | organic vs push notification → retention |
| By Flash Deal | حجز first book بعرض → retention مقارنة بالعادي |

## Saved Venue → Booking Conversion
- من حفظ ملعباً → كم نسبة حجزه لاحقاً؟
- متى يحجز بعد الحفظ (D+1، D+7...)؟

---

# 6. Drop-off Analysis

## Top Abandonment Points
قائمة مرتبة بأعلى نقاط الانسحاب في الـ booking funnel:
1. `venue_details_viewed` بدون `slot_list_viewed`
2. `slot_list_viewed` بدون `slot_selected`
3. `booking_payment_screen_viewed` بدون `payment_started`
4. `payment_started` بدون `payment_succeeded`

## Payment Failure Breakdown
- فشل بسبب: OTP timeout | رصيد غير كافٍ | خطأ في provider | انتهت session
- أي provider أعلى failure rate؟
- هل العربون يقلل payment failure مقارنة بالدفع الكامل؟

## Auth Friction Points
- نسبة من طلب OTP ولم يتحقق منه
- متوسط محاولات OTP قبل النجاح
- هل WhatsApp OTP أسرع نجاحاً من SMS؟

---

# 7. Admin Dashboard Reporting Modules

## Module 1: Acquisition & Activation
- لاعبون جدد يومياً / أسبوعياً
- مصدر التسجيل (organic, push, SMS, WhatsApp, referral)
- معدل إتمام onboarding (وصل لاختيار المنطقة؟)
- وقت من التسجيل لأول حجز

## Module 2: Funnel Performance
- Funnel visualization: كل خطوة + نسبة التسرب
- Heatmap: أين أكثر drop-off؟
- Trend: هل الـ funnel يتحسن أم يتراجع أسبوعياً؟

## Module 3: Payment Friction
- معدل إتمام الدفع لكل provider
- أسباب فشل الدفع
- مقارنة: deposit vs full payment completion rate
- متوسط وقت إتمام الدفع

## Module 4: Retention & Repeat Usage
- D1/D7/D30 retention charts
- Average bookings per player per month
- Cohort tables
- Players at risk (لم يعودوا منذ 30 يوم وكانوا نشطين)

## Module 5: Offer/Last-Minute Performance
- Flash deals created vs redeemed
- Conversion rate من flash deal notification
- متوسط وقت البيع (كم تبقى قبل انتهاء العرض؟)
- أي venues تُباع flash deals أسرع؟

## Module 6: Waitlist Recovery
- Waitlist joins per day
- Conversion: waitlist alert → booking
- متوسط وقت انتظار لاعب في الـ waitlist

## Module 7: Geographic Conversion
- Conversion rate per city / area
- هل المناطق التي فيها نوادٍ أكثر تعطي retention أعلى؟
- Flash deals activity per area

## Module 8: Cohort Analysis
- Cohort table: acquisition week × weeks retained
- Filterable by: auth method, payment type, area, acquisition source

## Module 9: Session Health
- متوسط جلسات/لاعب/أسبوع
- متوسط مدة الجلسة
- Bounce rate (دخل وخرج خلال 30 ثانية)
- أيام الأسبوع وأوقات اليوم الأكثر نشاطاً

---

# 8. Phase 1 Minimum Tracking (يجب إطلاقه من اليوم الأول)

هذه هي Events المطلوبة إطلاقها في Phase 1. بدونها لا يمكن اتخاذ أي قرار منتجي بعد الإطلاق:

```
MUST TRACK — Phase 1:
✅ app_opened
✅ session_started / session_ended
✅ venue_list_viewed
✅ venue_details_viewed
✅ slot_list_viewed
✅ slot_selected
✅ booking_payment_screen_viewed
✅ deposit_option_selected / full_payment_option_selected
✅ payment_method_selected
✅ payment_started
✅ payment_failed
✅ payment_succeeded
✅ booking_confirmed
✅ booking_abandoned
✅ otp_requested / otp_verified / otp_failed
✅ google_signin_succeeded / google_signin_failed
✅ notification_opened
✅ flash_deal_viewed / flash_deal_booking_started
✅ waitlist_joined / waitlist_alert_opened
✅ rebook_clicked / rebook_completed
```

**الـ Admin Dashboard يعرض في Phase 1:**
- Funnel 1 (Discovery → Booking)
- Payment Friction Module
- D7/D30 Retention
- Basic session stats

---

# 9. Phase 2 Enhancements

```
PHASE 2 ADDITIONS:
- venue_saved event tracking
- Cohort analysis by acquisition source
- Geographic conversion heatmap
- Full Waitlist Recovery module
- Offer Performance detailed module
- Session Health detailed view
- Players at risk alerting
- A/B test framework (لاختبار UX تغييرات)
```

---

# 10. Product Optimization Usage

| البيانات | القرار المنتجي المحتمل |
|---------|----------------------|
| drop-off عالٍ عند `slot_list_viewed` | الـ slots لا تعرض بشكل كافٍ — إعادة تصميم عرضها |
| payment failure rate > 20% لـ provider معين | إخفاء هذا الـ provider من الخيارات الافتراضية |
| deposit conversion rate أعلى من full payment | رفع نسبة العربون من 30% إلى 50% |
| wallet usage يقلل payment failure | تشجيع شحن الـ wallet أكثر (مثلاً: bonus wallet credit) |
| flash deal notification → booking > 30% | زيادة تكرار إرسال العروض |
| waitlist → booking < 10% | تحسين توقيت إرسال الـ waitlist alert |
| D7 retention أعلى لمن استخدم العربون | دفع العربون كـ default option للمستخدمين الجدد |
| bounce rate > 50% لمنطقة معينة | نقص العرض في هذه المنطقة — تركيز جهود supply acquisition |
| Google users أقل retention من OTP users | تحسين onboarding للـ Google users خاصة |

---

# 11. Privacy and Access Guardrails

## ما هو محمي
- `player_events` لا يمكن الوصول إليها من Club Dashboard أبداً
- لا يوجد API endpoint يكشف journey فردية للاعبين للنادي
- Super Admin فقط يملك صلاحية `admin.analytics.view`

## الحد الأدنى الضروري
- لا نتتبع ما لا نستطيع استخدامه في قرار منتجي
- لا نتتبع معلومات حساسة (content الرسائل، معلومات بطاقات الدفع)
- `anonymous_id` نستخدمه للمستخدمين غير المسجلين فقط — لا نربطه بهوية حقيقية

## الـ API
```
GET /api/admin/v1/analytics/funnel    → Funnel data (aggregated)
GET /api/admin/v1/analytics/retention → Retention cohorts
GET /api/admin/v1/analytics/sessions  → Session metrics
GET /api/admin/v1/analytics/payments  → Payment friction
GET /api/admin/v1/analytics/offers    → Flash deals + waitlist performance
```

جميعها تعطي بيانات aggregate. لا single-user journey API في Phase 1.

## من Mobile API
```
POST /api/v1/events/track
Auth: Optional (مع أو بدون token)
Body: {
  "events": [
    {
      "event_name": "slot_selected",
      "anonymous_id": "uuid",
      "session_id": "uuid",
      "occurred_at": "2026-04-04T14:30:00Z",
      "properties": { "venue_id": 5, "price": 350000 }
    }
  ]
}
```

Batch endpoint — يقبل حتى 20 event في request واحد. يُستخدم لتقليل network calls في بيئة الإنترنت الضعيف.

