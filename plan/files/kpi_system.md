# kpi_system.md
## دق احجزلي — نظام القياس

---

## Supply KPIs (جانب العرض — النوادي)

| KPI | التعريف | حساب | جيد/سيئ | لماذا مهم |
|-----|--------|------|---------|-----------|
| Clubs Listed | النوادي النشطة في المنصة | COUNT WHERE status=active | جيد: >20 في أول شهر | حجم العرض |
| Onboarding Completion Rate | نسبة النوادي التي أكملت الإعداد | completed / started | جيد: >70% | يقيس فعالية onboarding |
| Club Weekly Active Rate | نوادٍ تستخدم Dashboard مرة/أسبوع | active_this_week / total | جيد: >60% | النادي الخامل لا قيمة له |
| Avg Venues per Club | متوسط الملاعب لكل نادٍ | SUM(venues) / clubs | جيد: >2 | تنوع العرض |
| Club Retention (30d) | نوادٍ مستمرة بعد 30 يوم | retained_30d / joined | جيد: >80% | النادي يرى قيمة |

---

## Demand KPIs (جانب الطلب — اللاعبون)

| KPI | التعريف | حساب | جيد/سيئ | لماذا مهم |
|-----|--------|------|---------|-----------|
| Registered Players | مستخدمون مسجلون | COUNT users WHERE account_status=active | نمو مطلوب | حجم الطلب |
| Player Booking Rate | نسبة المسجلين الذين حجزوا مرة | booked_once / registered | جيد: >40% في أول شهر | يقيس conversion |
| Time to First Booking | وقت من التسجيل لأول حجز | median(first_booking - registered_at) | جيد: <24 ساعة | تجربة onboarding |

---

## Liquidity KPIs (سيولة السوق)

| KPI | التعريف | حساب | جيد/سيئ | لماذا مهم |
|-----|--------|------|---------|-----------|
| Booking Fill Rate | نسبة الساعات المتاحة التي تُحجز | booked_hours / total_available_hours | جيد: >40% | السوق يعمل |
| Slot Availability per Area | متوسط الفتحات المتاحة في المنطقة | count per area per day | يجب أن يكون >10/يوم | يضمن تجربة جيدة للاعب |
| Search to Booking Rate | نسبة من بحث ثم حجز | bookings / unique_searches | جيد: >15% | فعالية الاكتشاف |

---

## Monetization KPIs

| KPI | التعريف | حساب | لماذا مهم |
|-----|--------|------|-----------|
| GMV (Gross Merchandise Value) | إجمالي قيمة الحجوزات | SUM(total_price) per period | حجم المعاملات |
| Net Revenue | إيراد المنصة | SUM(commission_amount) per period | الربح الفعلي |
| Avg Commission per Booking | متوسط العمولة | SUM(commission) / COUNT(bookings) | فعالية النموذج |
| Deposit Rate | نسبة من اختار العربون | deposit_bookings / total | يقيس friction الدفع |
| Wallet Usage Rate | نسبة دفعات عبر Wallet | wallet_payments / total_payments | يقيس سرعة العودة |

---

## Retention KPIs

| KPI | التعريف | حساب | جيد/سيئ | لماذا مهم |
|-----|--------|------|---------|-----------|
| Player 30d Retention | لاعبون عادوا بعد 30 يوم | returned_30d / first_booking | جيد: >35% | المنصة تخلق عادة |
| Booking Frequency | متوسط حجوزات لكل لاعب/شهر | bookings / active_players | جيد: >1.5 | استخدام متكرر |
| Rebooking Rate | نسبة من حجز مجدداً نفس الملعب | rebookings / total | جيد: >25% | رضا + سهولة إعادة الحجز |

---

## Operations KPIs

| KPI | التعريف | جيد/سيئ |
|-----|--------|---------|
| Booking Completion Rate | من بدأ الدفع وأكمله | جيد: >65% |
| Payment Failure Rate | فشل المدفوعات | جيد: <10% |
| Cancellation Rate | نسبة الإلغاء من الحجوزات | جيد: <15% |
| No-Show Rate | حضر أم لا | يتبع: هل العربون يقلله؟ |
| Last-Minute Deal Fill Rate | نسبة عروض اللحظة الأخيرة المباعة | جيد: >50% |

---

## Leakage / Disintermediation Indicators

| المؤشر | كيف يُقاس |
|--------|-----------|
| نوادٍ لا تستخدم Dashboard بعد الإعداد | weekly_active < 1 لمدة شهر |
| حجوزات تُلغى ثم نفس اللاعب لا يعود | cancelled + no_rebook في 30 يوم |
| لاعبون يبحثون كثيراً بدون حجز | search/booking ratio > 10:1 |
| تراجع حجز نادٍ رغم استمرار نشاطه | club bookings drop >30% شهرياً |

---

## Club Performance KPIs

| KPI | التعريف |
|-----|--------|
| Occupancy Rate | (booked_hours / available_hours) × 100 |
| Revenue per Venue | SUM(club_payout_amount) / COUNT(venues) |
| Last-Minute Fill Rate | LM deals sold / LM deals created |
| Pending Settlement Balance | SUM(unsettled club_payout_amount) |

