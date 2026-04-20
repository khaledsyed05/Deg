# club_demand_activation_strategy.md
## دق احجزلي — استراتيجية تنشيط الطلب للملاعب

---

## 1. ما معنى التحول تجاريًا

المشروع الحالي passive: اللاعب يبحث → النادي ينتظر.
المشروع المطلوب active: المنصة تولّد طلباً → النادي يملأ وقته الفارغ.

هذا الفرق هو الفرق بين أداة listing ومنصة تشغيل.

**القيمة الاقتصادية:** في سوريا، الملعب الذي يعمل 4 ساعات يومياً يترك 4-6 ساعات فارغة. هذه الساعات = إيراد صفر الآن. تحويل 2 ساعة منها إلى حجوزات = رفع إيراد النادي 33-50%.

---

## 2. Last-Minute Deals — الأولوية الأولى

### كيف تعمل
النادي يرى في Dashboard ملاعبه الفارغة اليوم أو غداً → يضغط "عرض خاص" → يختار:
- الوقت الفارغ (مثلاً: اليوم 4-6 م)
- نسبة الخصم (10-30-50%)
- مدة العرض (انتهى في 2 ساعة مثلاً)

المنصة ترسل FCM للاعبين في نفس المنطقة الذين:
- سبق وحجزوا هذا الملعب أو نادياً مشابهاً
- لديهم تفضيل لهذه الرياضة
- مفعّل لديهم إشعارات العروض

### ما يحتاجه تقنياً
```sql
-- إضافة بسيطة على venue_pricing_tiers أو جدول منفصل
venue_flash_deals:
  id
  venue_id FK
  start_datetime
  end_datetime
  original_price
  discounted_price
  max_bookings (nullable)
  expires_at
  is_active
```

### الأثر المتوقع
- النادي يرى قيمة فعلية وفورية
- اللاعبون يشعرون أن التطبيق يوفر لهم فرصاً حقيقية
- إيراد لحظات الذروة لا يتأثر

---

## 3. Waitlist — الأولوية الثانية

### كيف تعمل
اللاعب يجد الوقت ممتلئاً → يضغط "أبلغني عند التوفر" → إذا أُلغي حجز → FCM فوري له → يحجز مباشرة

### ما يحتاجه تقنياً
```sql
venue_waitlist:
  id
  venue_id FK
  user_id FK
  booking_date
  start_time
  duration_minutes
  created_at
  UNIQUE(venue_id, user_id, booking_date, start_time)
```

عند إلغاء حجز: `VenueAvailableNotificationJob` موجود بالفعل — يُعدَّل ليفحص الـ waitlist أولاً قبل اللاعبين العشوائيين القريبين.

---

## 4. Request Marketplace — الأولوية الثالثة

### كيف تعمل
اللاعب يكتب طلبه: "أريد ملعب كرة قدم يوم الجمعة 5-7م في منطقة المزة" → النوادي المناسبة تتلقى الطلب → يقدمون عرضاً بالسعر → اللاعب يختار → يحجز.

### لماذا مهم
- يعكس سلوك السوق السوري: الكثير من الحجوزات تبدأ بـ "في شيء متاح؟"
- يجعل النادي يركض وراء الطلب
- يحوّل الطلب الضائع (لم يجد متاحاً) إلى حجز محتمل

### ما يحتاجه تقنياً
```sql
booking_requests:
  id, user_id, category_id, area_id,
  requested_date, start_time, end_time, duration_minutes,
  budget_max (nullable),
  expires_at, status(open/fulfilled/expired)
  
booking_request_offers:
  id, request_id, venue_id,
  offered_price, note, expires_at, status(pending/accepted/rejected)
```

**أولوية:** Phase 2 — ليس الإطلاق الأول.

---

## 5. Reactivation Campaigns — للنوادي

### كيف تعمل
النادي يرى في Dashboard لاعبين سبق وحجزوا عنده آخر مرة قبل أكثر من 30 يوماً → يضغط "تذكيرهم" → يختار:
- رسالة نصية
- خصم مخصص لهم
- المنصة ترسل الإشعار

هذا يعطي النادي أداة CRM بسيطة دون أن تكون معقدة.

**تقنياً:** query من `bookings` WHERE `venue.club_id = X AND status = completed AND created_at < NOW() - 30 days`. بدون جدول إضافي.

---

## 6. Team Captain Tools — مستقبلاً

[اقتراح] شخص واحد ينظم فريقاً (7-11 لاعب) يمكنه:
- حجز ملعب باسم "الفريق"
- مشاركة رابط الحجز مع الفريق
- كل واحد يدفع حصته عبر التطبيق

هذا يحل مشكلة شائعة في سوريا: من يجمع المال؟

**أولوية:** Phase 3 — يحتاج split payment logic.

---

## 7. الأولويات الحقيقية

| الميزة | الأثر | التعقيد | الأولوية |
|--------|------|---------|---------|
| Last-Minute Deals | عالٍ جداً | بسيط | Phase 1 |
| Waitlist | عالٍ | بسيط | Phase 1 |
| Reactivation للنادي | عالٍ | بسيط جداً (query فقط) | Phase 1 |
| Request Marketplace | عالٍ | متوسط | Phase 2 |
| Featured Placement | متوسط | بسيط | Phase 2 |
| Team Captain | عالٍ مستقبلاً | معقد | Phase 3 |

