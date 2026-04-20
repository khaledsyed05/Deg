# product_scope_reset.md
## دق احجزلي — إعادة ضبط نطاق المنتج

---

## المبدأ: High Impact / Low Complexity أولاً

---

## ما يجب إبقاؤه في Phase 1

| الميزة | السبب |
|--------|------|
| حجز الملاعب الرياضية | الغرض الأساسي |
| 4 بوابات دفع (MTN, Syriatel, Fatora, SamaPay) | تغطية السوق السوري |
| Wallet داخلي | يحل مشكلة الـ refund |
| خيار العربون | ضروري للسوق السوري |
| Last-Minute Deals | أثر فوري على الإشغال |
| Waitlist | يستعيد الطلب المفقود |
| OTP SMS + WhatsApp Baileys | تغطية كاملة |
| Google Sign-In | يسهّل التسجيل |
| Club Dashboard بسيط | يمكّن النادي |
| Super Admin Dashboard | يدير المنصة |
| Reviews (rating فقط في البداية) | يبني الثقة |
| FCM + SMS notifications | يغطي كل المستخدمين |
| App startup API | حيوي للتشغيل |
| Geographic filtering (city + area) | أساسي للاكتشاف |
| Events tab (مباريات اليوم) | يخلق habit loop |

---

## ما يجب تبسيطه

| الميزة الحالية | التبسيط المطلوب |
|---------------|----------------|
| Reviews: rating + body + anonymous + venue_hint | Rating فقط + body اختياري بدون حد أدنى |
| تأكيد الإلغاء بكتابة نص | Confirmation dialog فقط |
| WhatsApp/SMS choice screen | Auto-detection بدون سؤال |
| Club onboarding معقد | 3 خطوات فقط في اليوم الأول |
| Commission config: venue/club/global | Global فقط في Phase 1، venue-level في Phase 2 |

---

## ما يجب تأجيله إلى Phase 2

| الميزة | السبب |
|--------|------|
| Recurring/Scheduled bookings | تعقيد غير ضروري في البداية |
| Request Marketplace | يحتاج supply كافٍ أولاً |
| Club Subscription Plans | يحتاج إثبات قيمة أولاً |
| Featured Placement | يحتاج traffic كافٍ أولاً |
| Reactivation Campaigns | يحتاج بيانات تاريخية |
| Advanced Commission per venue | يحتاج فهم النوادي أولاً |
| Settlement system معقد | يمكن تبسيطه في البداية |
| Roles & Permissions Dynamic | Club Admin ثابت في Phase 1 |

---

## ما يجب تأجيله إلى Phase 3

| الميزة | السبب |
|--------|------|
| صالات الأفراح والمناسبات | يحتاج نموذج تسعير مختلف |
| Team Captain / Split Payment | تعقيد تقني عالٍ |
| Multi-city expansion خارج سوريا | بعد إثبات النموذج |
| Analytics متقدمة للنادي | بعد تراكم البيانات |

---

## ما يجب حذفه أو عدم بنائه الآن

| الميزة | السبب |
|--------|------|
| تأكيد الإلغاء بكتابة "cancel" | UX سيء بدون مبرر |
| Recurring booking في Phase 1 | تعقيد لا ضرورة له الآن |
| Commission مضافة على اللاعب | يضر التبني |
| Email authentication | مقرر حذفه مسبقاً |

---

## مصفوفة التأثير / التعقيد

```
HIGH IMPACT + LOW COMPLEXITY = ابنِ الآن
  - خيار العربون
  - Last-Minute Deals
  - Waitlist
  - SMS confirmation دائم
  - Club Onboarding بسيط

HIGH IMPACT + HIGH COMPLEXITY = خطط جيداً
  - Request Marketplace (Phase 2)
  - Team Captain (Phase 3)

LOW IMPACT + LOW COMPLEXITY = افعل ببساطة أو تجاهل
  - تبسيط تأكيد الإلغاء
  - تبسيط Reviews

LOW IMPACT + HIGH COMPLEXITY = لا تبنِ
  - Commission per venue في Phase 1
  - Dynamic Roles في Phase 1
```

