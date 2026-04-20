# final_locked_decisions.md
## دق احجزلي — القرارات المقفلة النهائية
> هذا الملف يكسب عند أي تعارض مع أي ملف آخر.
> Version: FINAL — بعد جولة التنظيف الكامل.

---

## LOCK-001 — العمولة: deducted فقط، إلى الأبد في Phase 1

- `apply_as` محذوف من `commission_configs` نهائياً.
- `total_price = venue_price` دائماً. اللاعب يدفع سعر الملعب المعلن.
- `club_payout_amount = venue_price - commission_amount` دائماً.
- Phase 1: `scope = 'global'` فقط. نسبة 7% افتراضية. Admin يعدّل من Dashboard.
- Phase 2: يُضاف scope per-club وper-venue.

---

## LOCK-002 — خيار العربون

- اللاعب يختار: `payment_mode: 'full' | 'deposit'`.
- Deposit = دفع إلكتروني جزئي. Remaining = نقداً للنادي عند الوصول (خارج نظام الدفع).
- `commission_amount` يُحسب على `deposit_amount` فقط.
- Refund عند الإلغاء: `deposit_amount - cancellation_deduction` للـ wallet. `remaining_amount` لا يُلمس.
- Club Admin يؤكد استلام الباقي من Dashboard.

---

## LOCK-003 — تأكيد الإلغاء

- `POST /bookings/{id}/cancel {confirmed: true}`. لا phrase. لا نص.

---

## LOCK-004 — WhatsApp OTP

- Auto-detect. لا choice screen. Baileys يفحص → إذا متاح يرسل → إذا لا يرسل SMS.
- `otp/choose-channel` endpoint ملغى من Phase 1.

---

## LOCK-005 — Recurring Booking

- Phase 2. لا mobile API في Phase 1. الحقول في schema موجودة. لا scheduler jobs في Phase 1.

---

## LOCK-006 — Reviews

- Rating (1-5) إجباري. Body اختياري. لا حد أدنى للحروف.
- مرة واحدة لكل (user_id, club_id).

---

## LOCK-007 — Commission Scope في Phase 1

- `global` فقط. لا per-venue، لا per-club في Phase 1.

---

## LOCK-008 — Behavioral Tracking

- `player_events` table — append-only.
- Super Admin فقط. النادي لا يرى behavioral journey اللاعب.
- Batch API: `POST /api/v1/events/track` (حتى 20 event/request).

---

## LOCK-009 — البنية التقنية

| الجانب | القرار |
|--------|--------|
| Database | MySQL 8.0+ / utf8mb4 |
| Cache | File (لا Redis) |
| Queue | Database Queue (لا Redis) |
| TOTP | Custom TotpService — PHP native — لا package |
| FCM | Official HTTP v1 — لا package |
| Google Auth | JWKS مباشر — لا kreait |
| OTP Hash | SHA-256 — لا plain text |
| Permissions | Spatie |
| Geography | dr5hn/countries-states-cities-database (ODbL) — seeded مرة واحدة |

---

## LOCK-010 — Geography

- 3 مستويات: `countries` → `states` → `cities` — من dr5hn.
- Admin يُفعّل/يوقف. التطبيق يجيب فقط ما فعّله Admin.
- `clubs.city_id` → `cities` (المدينة/المنطقة الدقيقة).
- `users.default_state_id` + `users.default_city_id`.

---

## LOCK-011 — Settlement Status

`ENUM('draft','pending','completed','cancelled')`. لا `processing`.

---

## LOCK-012 — venue_categories.type

`ENUM('sports','hall','court','outdoor','other')`. لا `room`، لا `restaurant`.

---

## LOCK-013 — الإطلاق

منطقة واحدة في دمشق. 20 نادٍ. ثم توسع.
30 يوم مجاني للنادي → commission تبدأ.

---

## LOCK-014 — SMS Confirmation

كل حجز مؤكد يُرسل تأكيد SMS — دائماً — بغض النظر عن FCM.

---

## LOCK-015 — Progressive Club Onboarding

أول 7 أيام: 3 أقسام فقط (الملاعب، الحجوزات، الإيراد). باقي الأقسام تُفتح تدريجياً.

