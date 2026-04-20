# conflicts_closed.md
## دق احجزلي — سجل إغلاق التناقضات النهائي

---

## CC-001 — Commission apply_as: deducted فقط

**التناقض:** `commission_configs.apply_as` يقبل `'added' | 'deducted'` في الـ Schema. الـ implementation_spec §10.1 يوثّق كلا الحالتين في كود PHP. الـ patch_instructions و final_locked_decisions يقولان deducted فقط في Phase 1.

**القرار النهائي:**
- `apply_as` يُحذف من `commission_configs` نهائياً.
- لا enum. لا خيار. العمولة دائماً تُخصم من النادي.
- `total_price = venue_price` دائماً — اللاعب يدفع سعر الملعب المعلن بالضبط.
- `club_payout_amount = venue_price - commission_amount` دائماً.
- الكود: لا `if apply_as = added`. `CommissionService::calculate()` لا يقبل إلا `deducted`.
- **الحقل يُحذف من الـ schema. يُحذف من الكود.**

---

## CC-002 — commission_configs: تعريف مكرر في الـ Schema

**التناقض:** `commission_configs` معرّف مرتين في database_schema.md:
- تعريف أول (سطر ~381): يشمل `apply_as ENUM('added','deducted')`
- تعريف ثانٍ (سطر ~541): بدون `apply_as` لكن بنسخة مختلفة من الحقول

**القرار النهائي:**
يبقى تعريف واحد فقط. الحقول النهائية محددة في database_schema.md المنقّح أدناه. لا `apply_as`.

---

## CC-003 — venue_categories: تعريف مكرر في الـ Schema

**التناقض:** `venue_categories` معرّف مرتين:
- تعريف أول: `type ENUM('sport','hall','restaurant','other')` + `icon VARCHAR(255)`
- تعريف ثانٍ: `type ENUM('sports','hall','court','room','outdoor','other')` بدون `icon`

**القرار النهائي:**
تعريف واحد. `type ENUM('sports','hall','court','outdoor','other')`. بدون `room` (تُدمج في `hall` أو `other`). بدون `restaurant` (خارج نطاق Phase 1). `icon` يُدار عبر spatie/medialibrary لا كـ varchar.

---

## CC-004 — العربون: تعارض مع "cash payment out of scope"

**التناقض:**
- implementation_spec §2 يقول: "Cash payment at venue — out of scope"
- monetization_model_v2 و transformation_decision_log يقولان: خيار العربون + الباقي نقداً عند الوصول

**القرار النهائي:**
"Cash payment at venue" الذي كان خارج الـ scope يعني: لا بوابة دفع نقدي إلكترونية، لا cash collection system. هذا لا يزال صحيحاً.

العربون لا علاقة له بذلك: اللاعب يدفع جزءاً إلكترونياً عبر بوابات الدفع الموجودة (MTN/Syriatel/Fatora/Wallet)، والباقي هو التزام مباشر بين اللاعب والنادي عند الوصول — خارج نظام الدفع الإلكتروني للمنصة.

المنصة تتتبع `remaining_status` لأغراض تشغيلية فقط. لا تحصّل المنصة المبلغ المتبقي.

---

## CC-005 — settlements: حقل `status` مختلف في تعريفين

**التناقض:**
- تعريف أول للـ `settlements` (من implementation_spec): `ENUM('draft','pending','completed','cancelled')`
- تعريف ثانٍ (من database_schema قسم قديم): `ENUM('pending','processing','completed','cancelled')`

**القرار النهائي:** `ENUM('draft','pending','completed','cancelled')`. لا `processing`.

---

## CC-006 — clubs.area_id مقابل clubs.city_id

**التناقض:** بعض أقسام الـ schema تحتفظ بـ `area_id` في index list بعد إعادة التسمية.

**القرار النهائي:** الحقل `city_id` فقط. كل mention لـ `area_id` على جدول clubs تُحذف.

---

## CC-007 — bookings: commission_type enum

**التناقض:** `bookings.commission_type ENUM('added','deducted')` — بعد حذف `apply_as` من commission_configs و ثبوت deducted دائماً، هذا الحقل يصبح ثابتاً دائماً على `deducted` = معلومة غير مفيدة.

**القرار النهائي:** `commission_type` على `bookings` يتغير لـ `ENUM('fixed','percentage')` — أي يُسجّل نوع الحساب (ثابت أو نسبة مئوية)، وهي المعلومة المفيدة فعلاً للـ audit trail. لا `'added'|'deducted'`.

---

## CC-008 — cancellation: phrase validation

**التناقض:**
- implementation_spec §8.3: "Player types 'cancel' or 'الغاء'"
- patch_instructions PATCH-005: حذف phrase validation
- ux_simplification_spec: confirmation dialog فقط

**القرار النهائي:** لا phrase validation. `POST /bookings/{id}/cancel {confirmed: true}` فقط. implementation_spec يُعدَّل.

---

## CC-009 — bookings: notes section يذكر gross_amount / venue_amount

**التناقض:** في notes جدول `bookings` يُذكر `gross_amount` و `venue_amount` كأسماء حقول بينما الحقول الفعلية هي `total_price` و `club_payout_amount`.

**القرار النهائي:** notes تُعدَّل لتشير للأسماء الصحيحة. لا `gross_amount`، لا `venue_amount`.

---

## CC-010 — recurring bookings في Phase 1

**التناقض:**
- implementation_spec §2 يذكر recurring booking في scope
- product_scope_reset و phase1_lock يؤجلانها لـ Phase 2

**القرار النهائي:** Recurring booking = Phase 2. حقول `is_recurring`، `recurrence_pattern`، `recurrence_parent_id` تبقى في schema لكن status `scheduled` لا يُنشأ من mobile API. لا scheduler jobs للـ recurring في Phase 1.

---

## CC-011 — events tab في Phase 1

**التناقض:**
- transformation_decision_log T-16: يبقى (habit loop)
- product_scope_reset: listed under "ما يجب إبقاؤه"

**القرار النهائي:** Events tab يبقى في Phase 1 لأنه يخلق habit loop يومي. البناء بسيط — proxy + 5min cache.

---

## CC-012 — deposit في refund calculation

**التناقض:** إذا اللاعب دفع عربون فقط ثم ألغى — هل يُرجع العربون للـ wallet؟

**القرار النهائي:**
- إذا ألغى قبل 30 دقيقة من البداية: يُرجع `deposit_amount - cancellation_deduction_on_deposit` للـ wallet
- `cancellation_deduction` يُطبَّق على `deposit_amount` فقط (لا على remaining الذي لم يُدفع بعد)
- `remaining_amount` = لا يُلمس (لم يُدفع أصلاً)

