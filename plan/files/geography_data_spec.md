# geography_data_spec.md
## دق احجزلي — مواصفة نظام البيانات الجغرافية

---

## 1. المصدر الرسمي

**`dr5hn/countries-states-cities-database`**
- GitHub: https://github.com/dr5hn/countries-states-cities-database
- رخصة: ODbL-1.0 (مجاني تجارياً، لا قيود)
- تحديث: شهري من المجتمع
- حجم البيانات: 250 دولة / 5,299 محافظة / 153,765+ مدينة

**لا API خارجي.** البيانات تُنزَّل مرة واحدة كـ SQL dump وتُخزَّن في DB المشروع. لا اعتماد على أي خدمة خارجية في production.

---

## 2. الهيكل الجغرافي الجديد

```
countries (250 دولة — seeded)
  └── states (5,299 محافظة — seeded)
        └── cities (153,765+ مدينة/منطقة — seeded)
              └── clubs (النوادي — يديرها Admin)
                    └── venues (الملاعب — يديرها Club Admin)
```

**مقارنة مع الهيكل القديم:**
| القديم | الجديد | الفرق |
|--------|--------|-------|
| cities (=المحافظات يدوياً) | states (=محافظات من dr5hn) | مصدر موثوق + phone_code |
| areas (=المناطق يدوياً) | cities (=مدن/مناطق من dr5hn) | 153K+ منطقة جاهزة |
| لا دول | countries (ISO2/ISO3/phone_code) | قابلية التوسع الجغرافي |

---

## 3. كيف تعمل العملية

### الخطوة 1: Seeding (مرة واحدة عند الـ setup)

```bash
# تنزيل آخر SQL dump من GitHub Releases
# https://github.com/dr5hn/countries-states-cities-database/releases/latest

# ملف world.sql يحتوي: countries + states + cities
# حجمه ~80MB مضغوط

php artisan db:seed --class=WorldGeographySeeder
# يملأ: countries, states, cities
# كل is_active = 0 افتراضياً
```

### الخطوة 2: Admin يُفعّل من Dashboard

```
Admin Dashboard → Geography:

1. Countries List → ضغط "تفعيل" على سوريا → is_active = 1
2. Syria → States → ضغط "تفعيل" على: دمشق، حلب، حمص، اللاذقية...
3. Damascus → Cities → ضغط "تفعيل" على: المزة، المالكي، كفرسوسة...

كل تفعيل = لحظي. Mobile API ترجع is_active = 1 فقط.
```

### الخطوة 3: Mobile API تُرجع فقط ما فعّله Admin

```
GET /api/v1/countries          → is_active = 1 فقط
GET /api/v1/countries/{iso2}/states  → is_active = 1 فقط
GET /api/v1/states/{id}/cities       → is_active = 1 فقط
```

**التطبيق لا يختار أي شيء بنفسه. ما يُرسله Admin هو ما يظهر.**

---

## 4. استخدام `phone_code` في Mobile

```json
// GET /api/v1/countries?is_active=1
[
  {
    "id": 213,
    "iso2": "SY",
    "name": "Syria",
    "name_ar": "سوريا",
    "phone_code": "+963",
    "flag_emoji": "🇸🇾"
  }
]
```

عند شاشة "أدخل رقم هاتفك" → Mobile تجلب هذه القائمة → تعرض country code selector → سوريا +963 هي الافتراضية.

---

## 5. Syrian States من dr5hn

البيانات الموجودة فعلاً لسوريا في dr5hn:
- Damascus (دمشق)
- Aleppo (حلب)
- Homs (حمص)
- Hama (حماة)
- Latakia (اللاذقية)
- Tartus (طرطوس)
- Idlib (إدلب)
- Deir ez-Zor (دير الزور)
- Raqqa (الرقة)
- Al-Hasakah (الحسكة)
- Daraa (درعا)
- As-Suwayda (السويداء)
- Quneitra (القنيطرة)
- Rural Damascus (ريف دمشق)

---

## 6. WorldGeographySeeder — المنهجية

```php
// database/seeders/WorldGeographySeeder.php

class WorldGeographySeeder extends Seeder
{
    public function run(): void
    {
        // 1. نحمّل JSON من dr5hn (أخف من SQL import)
        // countries.json: ~150KB
        // states.json: ~800KB
        // cities.json: ~15MB (نستورد بـ chunks)
        
        // 2. للدول: نستورد كلها مع is_active = 0
        Country::upsert($countries, ['iso2'], ['name','phone_code','iso3']);
        
        // 3. للمحافظات: نستورد كلها مع is_active = 0
        State::upsert($states, ['id'], ['name','country_id','state_code']);
        
        // 4. للمدن: نستورد بـ chunks من 1000
        foreach (array_chunk($cities, 1000) as $chunk) {
            City::upsert($chunk, ['id'], ['name','state_id','latitude','longitude']);
        }
        
        // 5. نُفعّل سوريا فوراً
        Country::where('iso2', 'SY')->update(['is_active' => 1]);
    }
}
```

---

## 7. تحديث البيانات مستقبلاً

عند صدور تحديث جديد من dr5hn:
```bash
# نزّل الـ SQL الجديد
# شغّل Seeder مع upsert (لا يحذف البيانات الموجودة)
php artisan db:seed --class=WorldGeographySeeder
# يحتفظ بـ is_active settings الحالية
```

---

## 8. التأثير على الـ API

### APIs الجديدة للـ Mobile

```
GET /api/v1/countries                    → active countries + phone_code
GET /api/v1/countries/{iso2}/states      → active states for country
GET /api/v1/states/{id}/cities           → active cities for state
```

### APIs المحدّثة

```
GET /api/v1/clubs?state_id=X&city_id=Y  → بدل area_id
GET /api/v1/clubs/price-range?city_id=Y → بدل city_id القديم
```

---

## 9. التأثير على الـ Schema الإجمالي

| الجدول القديم | الجدول الجديد | الفرق |
|--------------|--------------|-------|
| `cities` (يدوي) | `countries` + `states` | أكبر + مصدر موثوق |
| `areas` (يدوي) | `cities` | 153K+ منطقة |
| `users.default_city_id` | `users.default_state_id` + `users.default_city_id` | مستويان |
| `clubs.area_id` | `clubs.city_id` | نفس الفكرة، مصدر أفضل |

