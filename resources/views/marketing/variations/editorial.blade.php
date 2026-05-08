@extends('layouts.marketing-variations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/v2-editorial.css') }}">
@endpush

@php
$C = __('marketing');
@endphp

<div class="v2-editorial">
  {{-- NAV --}}
  <nav class="v2-nav">
    <x-marketing.yh-logo-lockup :size="0.75" />
    <div class="v2-nav-center">
      @foreach($C['nav'] as $item)
        <a href="#" class="v2-nav-link">{{ $item }}</a>
      @endforeach
    </div>
    <div class="v2-nav-right">
      <div class="v2-nav-issue">ISSUE 01 · 2026</div>
    </div>
  </nav>

  {{-- HERO --}}
  <section class="v2-hero">
    <div class="v2-hero-grid">
      <div class="v2-hero-content">
        <div class="v2-hero-kicker">· العدد الأول · رياضة · مجتمع</div>

        <h1 class="v2-hero-title">
          يلا، <em>دق</em><br/>
          احجز،<br/>
          العب.
        </h1>

        <div class="v2-hero-intro">
          <div class="v2-hero-intro-label">المقدّمة<br/>—</div>
          <p class="v2-hero-intro-text">{{ $C['hero_sub'] }}</p>
        </div>

        <div class="v2-hero-ctas">
          <button class="v2-hero-btn-primary">{{ $C['cta1'] }} →</button>
          <button class="v2-hero-btn-secondary">{{ $C['cta2'] }}</button>
        </div>
      </div>

      <div class="v2-hero-image">
        <div class="v2-hero-image-box">
          <x-marketing.yh-stripe :label="'hero — football match'" :color="'#fff'" :dark="true" />
          <div class="v2-hero-image-label">NO. 01 / 2026</div>
          <div class="v2-hero-image-caption">
            <div class="v2-hero-caption-fig">FIG. 01</div>
            <div class="v2-hero-caption-text">مباراة عشية في ملعب الفيحاء، المزة، دمشق.</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- STATS --}}
  <section class="v2-stats">
    <div class="v2-stats-grid">
      <div class="v2-stats-label">· الأرقام / 2026</div>
      @foreach($C['stats'] as $stat)
        <div>
          <div class="v2-stat-value">{{ $stat['num'] }}</div>
          <div class="v2-stat-label">— {{ $stat['label'] }}</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- FEATURES --}}
  <section class="v2-features">
    <div class="v2-features-grid">
      <div class="v2-features-head">
        <div class="v2-features-head-label">· المميزات</div>
        <div class="v2-features-sticky">
          <h2 class="v2-features-title">
            <em>كل شي</em><br/>
            بحاجتو<br/>
            لتلعب.
          </h2>
          <p class="v2-features-subtitle">
            ست مميزات أساسية صُمّمت لتجربة لعب أبسط، أسرع، وأكثر متعة.
          </p>
        </div>
      </div>

      <div>
        @foreach($C['features'] as $i => $feature)
          <div class="v2-feature-item">
            <div class="v2-feature-number">0{{ $loop->iteration }}</div>
            <div class="v2-feature-content">
              <div class="v2-feature-content-title">{{ $feature['title'] }}</div>
              <div class="v2-feature-content-desc">{{ $feature['desc'] }}</div>
            </div>
            <div class="v2-feature-icon">{{ $feature['icon'] }}</div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- GALLERY --}}
  <section class="v2-gallery">
    <div class="v2-gallery-header">
      <div class="v2-gallery-label">· مقالة مصوّرة / ٢٠٢٦</div>
      <h2 class="v2-gallery-title">
        ملاعب <em>سوريا</em>،<br/>
        من الشمال للساحل.
      </h2>
    </div>

    <div class="v2-gallery-grid">
      <div class="v2-gallery-item span-7">
        <x-marketing.yh-stripe :label="'ملعب الفيحاء · دمشق'" :color="'#0BA84A'" />
      </div>
      <div class="v2-gallery-item span-5">
        <x-marketing.yh-stripe :label="'نادي الكرامة · حمص'" :color="'#0BA84A'" />
      </div>
      <div class="v2-gallery-item span-5">
        <x-marketing.yh-stripe :label="'ملعب النورس · اللاذقية'" :color="'#0BA84A'" />
      </div>
      <div class="v2-gallery-item span-4">
        <x-marketing.yh-stripe :label="'ملعب الأهلي · حلب'" :color="'#0BA84A'" />
      </div>
      <div class="v2-gallery-item span-4">
        <x-marketing.yh-stripe :label="'مدينة حماة الرياضية'" :color="'#0BA84A'" />
      </div>
      <div class="v2-gallery-item span-4">
        <x-marketing.yh-stripe :label="'ملعب السلام · طرطوس'" :color="'#0BA84A'" />
      </div>
    </div>
  </section>

  {{-- HOW IT WORKS --}}
  <section class="v2-how">
    <div class="v2-how-grid">
      <div>
        <div class="v2-how-label">· كيف بيشتغل</div>
        <h2 class="v2-how-title">
          أربع<br/>خطوات.<br/>
          <em>ثلاثين</em><br/>ثانية.
        </h2>
      </div>

      <div>
        @foreach($C['steps'] as $step)
          <div class="v2-step">
            <div class="v2-step-header">
              <div class="v2-step-number">{{ $step['n'] }}</div>
              <div class="v2-step-title">{{ $step['title'] }}</div>
            </div>
            <div class="v2-step-desc">{{ $step['desc'] }}</div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- TESTIMONIALS --}}
  <section class="v2-testimonials">
    <div class="v2-testimonials-label">· شهادات</div>

    @foreach($C['testimonials'] as $testimonial)
      <div class="v2-testimonial">
        <div class="v2-testimonial-author">
          <div class="v2-testimonial-name">{{ $testimonial['name'] }}</div>
          <div class="v2-testimonial-role">{{ $testimonial['role'] }}</div>
        </div>
        <div class="v2-testimonial-text">«{{ $testimonial['text'] }}»</div>
        <div class="v2-testimonial-rating">
          @for($i = 0; $i < $testimonial['rating']; $i++)
            <span class="v2-testimonial-star">★</span>
          @endfor
        </div>
      </div>
    @endforeach
  </section>

  {{-- PRICING --}}
  <section class="v2-pricing">
    <div class="v2-pricing-header">
      <div class="v2-pricing-label">· الأسعار</div>
      <h2 class="v2-pricing-title">
        خطط <em>بسيطة</em>.<br/>
        بدون مفاجآت.
      </h2>
    </div>

    <div class="v2-pricing-grid">
      @foreach($C['pricing'] as $i => $pricing)
        <div class="v2-pricing-card @if($pricing['featured'] ?? false) featured @endif">
          <div class="v2-pricing-number">0{{ $i + 1 }} · {{ $pricing['name'] }}</div>
          <div class="v2-pricing-amount">
            <span class="v2-pricing-price">{{ $pricing['price'] }}</span>
            @if($pricing['unit'] ?? false)
              <span class="v2-pricing-unit">{{ $pricing['unit'] }}</span>
            @endif
          </div>
          <div class="v2-pricing-desc">{{ $pricing['desc'] }}</div>

          @foreach($pricing['features'] as $j => $feature)
            <div class="v2-pricing-feature">
              <span class="v2-pricing-feature-number">0{{ $j + 1 }}</span>
              {{ $feature }}
            </div>
          @endforeach
        </div>
      @endforeach
    </div>
  </section>

  {{-- FAQ --}}
  <section class="v2-faq">
    <div class="v2-faq-grid">
      <div class="v2-faq-head">
        <div class="v2-faq-label">· الأسئلة المتكررة</div>
        <h2 class="v2-faq-title">
          اسأل،<br/><em>منجاوب</em>.
        </h2>
      </div>

      <div>
        @foreach($C['faq'] as $i => $item)
          <details class="v2-faq-item">
            <summary class="v2-faq-summary">
              {{ $item['q'] }}
              <span class="v2-faq-number">0{{ $i + 1 }}</span>
            </summary>
            <div class="v2-faq-answer">{{ $item['a'] }}</div>
          </details>
        @endforeach
      </div>
    </div>
  </section>

  {{-- FINAL CTA --}}
  <section class="v2-cta">
    <div class="v2-cta-label">· COLOPHON · CALL TO PLAY</div>
    <h2 class="v2-cta-title">
      يلا.<br/>
      <em>حجيز</em>.
    </h2>
    <p class="v2-cta-subtitle">حمّل التطبيق وابدا حجزك الأول الآن.</p>
    <div class="v2-cta-buttons">
      <button class="v2-cta-btn v2-cta-btn-dark">App Store →</button>
      <button class="v2-cta-btn v2-cta-btn-green">Google Play →</button>
    </div>
  </section>

  {{-- FOOTER --}}
  <footer class="v2-footer">
    <div class="v2-footer-grid">
      <div>
        <x-marketing.yh-logo-lockup :size="0.7" />
      </div>
      <div>
        <div class="v2-footer-col-title">· المنصة</div>
        @foreach(['الرئيسية','المميزات','الأسعار','البطولات'] as $link)
          <div class="v2-footer-col-link">{{ $link }}</div>
        @endforeach
      </div>
      <div>
        <div class="v2-footer-col-title">· الشركة</div>
        @foreach(['من نحن','الوظائف','الأخبار','تواصل'] as $link)
          <div class="v2-footer-col-link">{{ $link }}</div>
        @endforeach
      </div>
      <div>
        <div class="v2-footer-col-title">· القانوني</div>
        @foreach(['الشروط','الخصوصية','الاسترداد','الكوكيز'] as $link)
          <div class="v2-footer-col-link">{{ $link }}</div>
        @endforeach
      </div>
    </div>
  </footer>
</div>
