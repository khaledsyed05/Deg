# Marketing Site

Public-facing pages for يلا حجيز (Yalla Hjeez). Lives at `/` and related routes when deployed to `https://yallaehjez.com`.

## Structure

```
resources/views/
├── layouts/
│   └── marketing.blade.php        # Master layout (RTL, meta tags, fonts)
├── marketing/
│   ├── home.blade.php             # Landing page (7+ sections)
│   ├── about.blade.php            # Company story + team placeholder
│   ├── contact.blade.php          # Contact form + methods
│   ├── privacy.blade.php          # Privacy policy
│   ├── terms.blade.php            # Terms of service
│   ├── for-venues.blade.php       # B2B partnership page
│   ├── partials/
│   │   ├── nav.blade.php          # Sticky header with mobile menu
│   │   └── footer.blade.php       # 4-column footer
│   └── README.md                  # This file
└── errors/
    └── 404.blade.php              # Custom 404 page

components/marketing/
├── button.blade.php               # Primary/secondary/ghost button
├── section.blade.php              # Consistent spacing wrapper
├── feature-card.blade.php         # Numbered feature box
└── stat.blade.php                 # Large number + label

app/Http/Controllers/Marketing/
├── HomeController.php
├── AboutController.php
├── LegalController.php
├── ContactController.php
└── ForVenuesController.php

lang/ar/marketing.php              # All Arabic copy (900+ strings)
resources/css/marketing.css        # Tailwind directives + custom styles
tailwind.config.js                 # Brand tokens + theme
```

## Adding a New Page

### 1. Create the view
```bash
touch resources/views/marketing/{page}.blade.php
```

Edit:
```blade
@extends('layouts.marketing')

@section('title', __('marketing.meta.{page}_title'))
@section('description', __('marketing.meta.{page}_description'))

<!-- Content here -->
```

### 2. Create the controller
```bash
php artisan make:class "Http/Controllers/Marketing/{Page}Controller" --no-interaction
```

Example:
```php
class FeaturesController extends Controller {
    public function index() {
        return view('marketing.features');
    }
}
```

### 3. Add the route
Edit `routes/web.php`:
```php
Route::get('/features', [FeaturesController::class, 'index'])->name('features');
```

### 4. Add copy to the lang file
Edit `lang/ar/marketing.php`:
```php
'meta' => [
    '{page}_title' => 'عنوان الصفحة',
    '{page}_description' => 'وصف مختصر',
],
'{page}' => [
    'title' => 'العنوان الرئيسي',
    // ... more strings
],
```

## Updating Copy

**Never** inline strings in Blade. All copy lives in `lang/ar/marketing.php`.

Example:
```blade
<!-- ✗ Don't do this -->
<h1>احجز ملعبك</h1>

<!-- ✓ Do this -->
<h1>{{ __('marketing.home.hero.title') }}</h1>
```

This makes future copy iteration (A/B testing, tone adjustments) trivial.

## Updating Design System

**Colors & spacing:** Edit `tailwind.config.js`
```js
colors: {
  brand: { green: '#22D26A', /* ... */ },
  navy: { DEFAULT: '#050E1C', /* ... */ },
}
```

**Custom CSS:** Edit `resources/css/marketing.css`
```css
.gradient-mesh { /* ... */ }
.text-gradient-green { /* ... */ }
.glass-effect { /* ... */ }
```

**Typography:** Fonts are Google Fonts (Tajawal, Cairo) loaded in `marketing.blade.php`

## Components

All components use Laravel Blade component syntax: `<x-marketing.{name} />`.

### Button
```blade
<x-marketing.button variant="primary" size="lg" href="/download">
    Download
</x-marketing.button>
```

**Variants:** primary, secondary, ghost
**Sizes:** sm, md, lg

### Section
```blade
<x-marketing.section dark>
    <div>Content with consistent padding</div>
</x-marketing.section>
```

**Props:** `dark` (white background, dark text)

### Feature Card
```blade
<x-marketing.feature-card :number="1" heading="Title" body="Description" />
```

### Stat
```blade
<x-marketing.stat number="+500" label="Venues" />
```

## Brand Identity

- **Name:** يلا حجيز (Arabic), Yalla Hjeez (English contexts)
- **Tagline:** احجز ملعبك بدمشق بـ 30 ثانية
- **Domain:** yallaehjez.com
- **Primary Color:** #22D26A (emerald green)
- **Background:** #050E1C (deep navy)
- **Accent:** #FFFFFF (white)
- **Text Accent:** #3FE085 (lighter green)

## Testing

Visit in development:
```bash
# Terminal 1: Start dev server
npm run dev

# Terminal 2: Start Laravel
php artisan serve

# Terminal 3 (optional): Watch Blade files
php artisan config:cache
```

Then browse:
- `/` → home
- `/about` → about
- `/privacy` → privacy policy
- `/terms` → terms
- `/contact` → contact form
- `/for-venues` → B2B
- `/random-bad-url` → 404

## Performance

- Tailwind CSS (v4) handles all styling — no bloat
- Alpine.js (CDN) only for interactivity (nav toggle, FAQ accordion)
- No JavaScript framework (Vue/React/Inertia)
- Blade templates render server-side → fast first paint
- Google Fonts with `display=swap` → no FOUT
- Images: SVG inline where possible, omit otherwise

Lighthouse targets:
- Performance ≥ 90
- Accessibility ≥ 95
- Best Practices ≥ 95
- SEO ≥ 95

## RTL & i18n

The site is **Arabic-only RTL in v1**. No English fallback.

- All `<html dir="rtl" lang="ar">`
- All text in `lang/ar/marketing.php`
- Tailwind utilities work RTL (e.g., `mr-4` becomes `ml-4`)
- Form inputs explicitly `text-align: right` in CSS

Future: If expanding to English, create `lang/en/marketing.php` and a locale switcher in nav.

## Known TODOs

- [ ] Replace placeholder Facebook/Twitter/IG links with real ones
- [ ] Replace WhatsApp/email links with actual contact details
- [ ] Add real team member info and photos when available
- [ ] App Store and Google Play links (currently `#`)
- [ ] Generate favicon.ico from logo-mark.svg
- [ ] QR code to app download (or link to app marketing landing)
- [ ] Contact form could save to database instead of just logging
- [ ] Analytics (Google Analytics / Mixpanel) in future version

## Deployment

When deploying:
1. Run `npm run build` to build Tailwind + JS assets
2. Clear cache: `php artisan config:cache && php artisan route:cache`
3. The marketing site will be live at `/`
