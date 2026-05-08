@extends('layouts.marketing-variations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/v4-stadium.css') }}">
@endpush

@php
$C = __('marketing');
@endphp

<div class="v4-page">
  {{-- NAV --}}
  <nav class="v4-nav">
    <div class="v4-nav-logo">
      <x-marketing.yh-logo-lockup :size="0.8" :fg="'white'" :accent="'#22D26A'" :markBg="'#22D26A'" :markFg="'#050E1C'" />
    </div>
    <div class="v4-nav-menu">
      @foreach($C['nav'] as $i => $item)
        <a href="#" class="v4-nav-link">{{ $item }}</a>
      @endforeach
    </div>
    <button class="v4-nav-cta">{{ $C['download'] }}</button>
  </nav>

  {{-- HERO --}}
  <section class="v4-hero">
    <div class="v4-hero-glow"></div>
    <div class="v4-hero-grid"></div>

    <div class="v4-hero-content">
      <div>
        <div class="v4-hero-tag">
          <span class="v4-hero-tag-dot"></span>
          مباشر · ٢,٠٠٠ ملعب الآن
        </div>

        <h1 class="v4-hero-title">
          يلا<br/>
          <em>حجيز.</em><br/>
          <span class="v4-hero-title-light">والملعب لك.</span>
        </h1>

        <p class="v4-hero-sub">{{ $C['hero_sub'] }}</p>

        <div class="v4-hero-ctas">
          <button class="v4-hero-btn-primary">{{ $C['cta1'] }} ↓</button>
          <button class="v4-hero-btn-secondary">{{ $C['cta2'] }} →</button>
        </div>
      </div>

      <div class="v4-hero-phone">
        <div class="v4-hero-phone-glow"></div>
        <div style="position: relative;">
          <x-marketing.yh-phone-mock :width="300">
            @include('marketing.components.phone-home-content')
          </x-marketing.yh-phone-mock>
          <div class="v4-hero-phone-status">
            <div class="v4-phone-status-label">تم الحجز</div>
            <div class="v4-phone-status-value">✓ ملعب الفيحاء · ٧:٠٠م</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- STATS --}}
  <section class="v4-stats">
    <div class="v4-stats-grid">
      @foreach($C['stats'] as $stat)
        <div>
          <div class="v4-stat-value">{{ $stat['num'] }}</div>
          <div class="v4-stat-label">{{ $stat['label'] }}</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- FEATURES --}}
  <section class="v4-features">
    <div class="v4-features-head">
      <div class="v4-features-label">// المميزات</div>
      <h2 class="v4-features-title">
        بُني <em>للاعبين</em>.<br/>صُنع للسرعة.
      </h2>
    </div>

    <div class="v4-features-grid">
      @foreach($C['features'] as $i => $feature)
        <div class="v4-feature-card">
          <div class="v4-feature-icon">{{ $feature['icon'] }}</div>
          <div class="v4-feature-title">{{ $feature['title'] }}</div>
          <div class="v4-feature-desc">{{ $feature['desc'] }}</div>
          <div class="v4-feature-count">0{{ $i + 1 }} / 06</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- HOW IT WORKS --}}
  <section class="v4-how">
    <div class="v4-how-head">
      <div class="v4-how-label">// كيف بيشتغل</div>
      <h2 class="v4-how-title">أربع خطوات. ثلاثين ثانية.</h2>
    </div>

    <div class="v4-how-grid">
      @foreach($C['steps'] as $step)
        <div class="v4-step">
          <div class="v4-step-number">{{ $step['n'] }}</div>
          <div class="v4-step-title">{{ $step['title'] }}</div>
          <div class="v4-step-desc">{{ $step['desc'] }}</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- TESTIMONIALS --}}
  <section class="v4-testimonials">
    <div class="v4-testimonials-head">
      <div class="v4-testimonials-label">// آراء العملاء</div>
      <h2 class="v4-testimonials-title">
        كلام من <em>الأرض</em>.
      </h2>
    </div>

    <div class="v4-testimonials-grid">
      @foreach($C['testimonials'] as $testimonial)
        <div class="v4-testimonial">
          <div class="v4-testimonial-stars">
            @for($i = 0; $i < $testimonial['rating']; $i++)
              <span class="v4-testimonial-star">★</span>
            @endfor
          </div>
          <div class="v4-testimonial-text">«{{ $testimonial['text'] }}»</div>
          <div class="v4-testimonial-author">
            <div class="v4-testimonial-avatar">{{ substr($testimonial['name'], 0, 1) }}</div>
            <div>
              <div class="v4-testimonial-name">{{ $testimonial['name'] }}</div>
              <div class="v4-testimonial-role">{{ $testimonial['role'] }}</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- PRICING --}}
  <section class="v4-pricing">
    <div class="v4-pricing-head">
      <div class="v4-pricing-label">// الأسعار</div>
      <h2 class="v4-pricing-title">اختار خطّتك.</h2>
    </div>

    <div class="v4-pricing-grid">
      @foreach($C['pricing'] as $i => $pricing)
        <div class="v4-pricing-card @if($pricing['featured'] ?? false) featured @endif">
          <div class="v4-pricing-name">0{{ $i + 1 }} · {{ $pricing['name'] }}</div>
          <div class="v4-pricing-amount">
            <span class="v4-pricing-price">{{ $pricing['price'] }}</span>
            @if($pricing['unit'] ?? false)
              <span class="v4-pricing-unit">{{ $pricing['unit'] }}</span>
            @endif
          </div>
          <div class="v4-pricing-desc">{{ $pricing['desc'] }}</div>

          @foreach($pricing['features'] as $feature)
            <div class="v4-pricing-feature">
              <span class="v4-pricing-check">✓</span>
              {{ $feature }}
            </div>
          @endforeach

          <button class="v4-pricing-btn">ابدأ الآن</button>
        </div>
      @endforeach
    </div>
  </section>

  {{-- FAQ --}}
  <section class="v4-faq">
    <div class="v4-faq-container">
      <div class="v4-faq-head">
        <div class="v4-faq-label">// أسئلة متكررة</div>
        <h2 class="v4-faq-title">اسأل، منجاوب.</h2>
      </div>

      @foreach($C['faq'] as $i => $item)
        <details class="v4-faq-item">
          <summary class="v4-faq-summary">
            {{ $item['q'] }}
            <span class="v4-faq-toggle">+</span>
          </summary>
          <div class="v4-faq-answer">{{ $item['a'] }}</div>
        </details>
      @endforeach
    </div>
  </section>

  {{-- FINAL CTA --}}
  <section class="v4-cta">
    <div class="v4-cta-glow"></div>
    <div class="v4-cta-content">
      <h2 class="v4-cta-title">
        يلا.<br/>
        <em>حجيز.</em>
      </h2>
      <p class="v4-cta-sub">الملعب فاضي. والوقت عم يمشي.</p>
      <div class="v4-cta-buttons">
        <button class="v4-cta-btn">App Store ↓</button>
        <button class="v4-cta-btn secondary">Google Play ↓</button>
      </div>
    </div>
  </section>

  {{-- FOOTER --}}
  <footer class="v4-footer">
    <div class="v4-footer-grid">
      <div class="v4-footer-logo">
        <x-marketing.yh-logo-lockup :size="0.7" :fg="'white'" :accent="'#22D26A'" :markBg="'#22D26A'" :markFg="'#050E1C'" />
      </div>
      <div class="v4-footer-links">
        @foreach(['الشروط','الخصوصية','تواصل','مساعدة'] as $link)
          <a href="#" class="v4-footer-link">{{ $link }}</a>
        @endforeach
      </div>
      <div class="v4-footer-copyright">{{ $C['footer_copyright'] }}</div>
    </div>
  </footer>
</div>
