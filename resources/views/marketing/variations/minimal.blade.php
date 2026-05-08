@extends('layouts.marketing-variations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/v3-minimal.css') }}">
@endpush

@php
$C = __('marketing');
@endphp

<div class="v3-page">
  {{-- NAV --}}
  <nav class="v3-nav">
    <div class="v3-nav-logo">
      <x-marketing.yh-logo-lockup :size="0.8" />
    </div>
    <div class="v3-nav-menu">
      @foreach($C['nav'] as $item)
        <a href="#" class="v3-nav-link">{{ $item }}</a>
      @endforeach
    </div>
    <button class="v3-nav-cta">{{ $C['download'] }}</button>
  </nav>

  {{-- HERO --}}
  <section class="v3-hero">
    <div class="v3-hero-tag">
      <span class="v3-hero-tag-dot"></span>
      {{ $C['hero_tag'] }}
    </div>

    <h1 class="v3-hero-title">
      احجز ملعبك المفضّل<br/>
      <em>بضغطة وحدة.</em>
    </h1>

    <p class="v3-hero-sub">{{ $C['hero_sub'] }}</p>

    <div class="v3-hero-ctas">
      <button class="v3-hero-btn-primary">{{ $C['cta1'] }} ↓</button>
      <button class="v3-hero-btn-secondary">{{ $C['cta2'] }}</button>
    </div>

    <div class="v3-hero-phone">
      <div class="v3-hero-phone-glow"></div>
      <div style="position: relative;">
        <x-marketing.yh-phone-mock :width="300">
          @include('marketing.components.phone-home-content')
        </x-marketing.yh-phone-mock>
      </div>
    </div>
  </section>

  {{-- LOGOS BAR --}}
  <section class="v3-logos">
    <div class="v3-logos-label">مستخدَم في أكبر النوادي والملاعب السورية</div>
    <div class="v3-logos-list">
      <span>الفيحاء</span><span>·</span><span>تشرين</span><span>·</span><span>الجلاء</span><span>·</span><span>الأهلي</span><span>·</span><span>الكرامة</span><span>·</span><span>السلام</span>
    </div>
  </section>

  {{-- STATS --}}
  <section class="v3-stats">
    <div class="v3-stats-grid">
      @foreach($C['stats'] as $stat)
        <div class="v3-stat-item">
          <div class="v3-stat-value">{{ $stat['num'] }}</div>
          <div class="v3-stat-label">{{ $stat['label'] }}</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- FEATURES --}}
  <section class="v3-features">
    <div class="v3-features-head">
      <div class="v3-features-label">المميزات</div>
      <h2 class="v3-features-title">كل شي بحاجتو لتلعب.</h2>
      <p class="v3-features-sub">من البحث للحجز للدفع — تجربة سلسة من البداية للنهاية.</p>
    </div>

    <div class="v3-features-grid">
      @foreach($C['features'] as $feature)
        <div class="v3-feature-card">
          <div class="v3-feature-icon">{{ $feature['icon'] }}</div>
          <div class="v3-feature-title">{{ $feature['title'] }}</div>
          <div class="v3-feature-desc">{{ $feature['desc'] }}</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- HOW IT WORKS --}}
  <section class="v3-how">
    <div class="v3-how-head">
      <div class="v3-how-label">كيف بيشتغل</div>
      <h2 class="v3-how-title">أربع خطوات. ثلاثين ثانية.</h2>
    </div>

    <div class="v3-how-grid">
      <div class="v3-how-connector"></div>
      @foreach($C['steps'] as $step)
        <div class="v3-step">
          <div class="v3-step-number">{{ $step['n'] }}</div>
          <div class="v3-step-title">{{ $step['title'] }}</div>
          <div class="v3-step-desc">{{ $step['desc'] }}</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- SHOWCASE --}}
  <section class="v3-showcase">
    <div class="v3-showcase-card">
      <div class="v3-showcase-dots"></div>
      <div class="v3-showcase-content">
        <div class="v3-showcase-label">للأندية والملاعب</div>
        <h2 class="v3-showcase-title">
          عندك ملعب؟<br/>زيد إيراداتك ٤٠٪.
        </h2>
        <p class="v3-showcase-sub">
          لوحة تحكم ذكية، تقويم تلقائي، ودفع آمن — اشتغل أقل واربح أكتر.
        </p>
        <button class="v3-showcase-btn">سجّل ملعبك معنا →</button>
      </div>

      <div class="v3-showcase-chart">
        <div class="v3-showcase-chart-box">
          <div class="v3-chart-label">إيرادات هذا الأسبوع</div>
          <div class="v3-chart-value">٢,٤٥٠,٠٠٠ ل.س</div>
          <div class="v3-chart-change">
            <span>↑ ٤٠٪</span> مقارنة بالشهر الماضي
          </div>
          <div class="v3-chart-bars">
            @foreach([40, 65, 50, 80, 70, 95, 88] as $height)
              <div class="v3-chart-bar" style="height: {{ $height }}%"></div>
            @endforeach
          </div>
          <div class="v3-chart-days">
            <span>س</span><span>أ</span><span>ث</span><span>أر</span><span>خ</span><span>ج</span><span>س</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- TESTIMONIALS --}}
  <section class="v3-testimonials">
    <div class="v3-testimonials-head">
      <div class="v3-testimonials-label">آراء العملاء</div>
      <h2 class="v3-testimonials-title">عم يحبّوه. منيح.</h2>
    </div>

    <div class="v3-testimonials-grid">
      @foreach($C['testimonials'] as $testimonial)
        <div class="v3-testimonial">
          <div class="v3-testimonial-stars">
            @for($i = 0; $i < $testimonial['rating']; $i++)
              <span class="v3-testimonial-star">★</span>
            @endfor
          </div>
          <div class="v3-testimonial-text">«{{ $testimonial['text'] }}»</div>
          <div class="v3-testimonial-author">
            <div class="v3-testimonial-avatar">{{ substr($testimonial['name'], 0, 1) }}</div>
            <div>
              <div class="v3-testimonial-name">{{ $testimonial['name'] }}</div>
              <div class="v3-testimonial-role">{{ $testimonial['role'] }}</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- PRICING --}}
  <section class="v3-pricing">
    <div class="v3-pricing-head">
      <div class="v3-pricing-label">الأسعار</div>
      <h2 class="v3-pricing-title">خطط بسيطة. بدون مفاجآت.</h2>
    </div>

    <div class="v3-pricing-grid">
      @foreach($C['pricing'] as $i => $pricing)
        <div class="v3-pricing-card @if($pricing['featured'] ?? false) featured @endif">
          @if($pricing['featured'] ?? false)
            <div class="v3-pricing-badge">الأكثر شعبية</div>
          @endif
          <div class="v3-pricing-name">{{ $pricing['name'] }}</div>
          <div class="v3-pricing-amount">
            <span class="v3-pricing-price">{{ $pricing['price'] }}</span>
            @if($pricing['unit'] ?? false)
              <span class="v3-pricing-unit">{{ $pricing['unit'] }}</span>
            @endif
          </div>
          <div class="v3-pricing-desc">{{ $pricing['desc'] }}</div>

          @foreach($pricing['features'] as $feature)
            <div class="v3-pricing-feature">
              <span class="v3-pricing-check">✓</span>
              {{ $feature }}
            </div>
          @endforeach

          <button class="v3-pricing-btn">ابدأ الآن</button>
        </div>
      @endforeach
    </div>
  </section>

  {{-- FAQ --}}
  <section class="v3-faq">
    <div class="v3-faq-container">
      <div class="v3-faq-head">
        <div class="v3-faq-label">أسئلة متكررة</div>
        <h2 class="v3-faq-title">اسأل، منجاوب.</h2>
      </div>

      @foreach($C['faq'] as $i => $item)
        <details class="v3-faq-item">
          <summary class="v3-faq-summary">
            {{ $item['q'] }}
            <span class="v3-faq-toggle">+</span>
          </summary>
          <div class="v3-faq-answer">{{ $item['a'] }}</div>
        </details>
      @endforeach
    </div>
  </section>

  {{-- FINAL CTA --}}
  <section class="v3-cta">
    <h2 class="v3-cta-title">يلا، شو عم تستنى؟</h2>
    <p class="v3-cta-sub">حمّل التطبيق وابدا حجزك الأول الآن — مجاناً.</p>
    <div class="v3-cta-buttons">
      <button class="v3-cta-btn">App Store ↓</button>
      <button class="v3-cta-btn green">Google Play ↓</button>
    </div>
  </section>

  {{-- FOOTER --}}
  <footer class="v3-footer">
    <div class="v3-footer-grid">
      <div class="v3-footer-logo">
        <x-marketing.yh-logo-lockup :size="0.7" />
      </div>
      <div class="v3-footer-links">
        @foreach(['الشروط','الخصوصية','تواصل','مساعدة'] as $link)
          <a href="#" class="v3-footer-link">{{ $link }}</a>
        @endforeach
      </div>
      <div class="v3-footer-copyright">{{ $C['footer']['copyright'] }}</div>
    </div>
  </footer>
</div>
