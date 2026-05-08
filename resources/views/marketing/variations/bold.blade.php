@extends('layouts.marketing-variations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/v1-bold.css') }}">
@endpush

@php
$C = __('marketing');
@endphp

<div class="v1-bold">
  {{-- NAV --}}
  <nav class="v1-nav">
    <x-marketing.yh-logo-lockup :size="0.85" />
    <div class="v1-nav-links">
      @foreach($C['nav'] as $item)
        <a href="#" class="v1-nav-link">{{ $item }}</a>
      @endforeach
    </div>
    <button class="v1-nav-cta">{{ $C['download'] }} ↓</button>
  </nav>

  {{-- HERO --}}
  <section class="v1-hero">
    <div class="v1-hero-content">
      {{-- Hero tag --}}
      <div class="v1-hero-tag">{{ $C['hero_tag'] }}</div>

      {{-- Hero title --}}
      <h1 class="v1-hero-title">
        {{ $C['hero_h1_line1'] }}<br/>
        <span class="v1-hero-title-highlight">{{ $C['hero_h1_line2'] }}</span>
      </h1>

      {{-- Hero subtitle --}}
      <p class="v1-hero-sub">{{ $C['hero_sub'] }}</p>

      {{-- CTA buttons --}}
      <div class="v1-hero-ctas">
        <button class="v1-cta-primary">{{ $C['cta1'] }} <span>↓</span></button>
        <button class="v1-cta-secondary">{{ $C['cta2'] }} →</button>
      </div>

      {{-- Avatars + stat --}}
      <div class="v1-hero-avatars">
        <div class="v1-avatar-group">
          @php $colors = ['#0BA84A','#FFB800','#FF6B5B','#0A1A2F']; @endphp
          @foreach(['ف','ل','ر','ك'] as $i => $letter)
            <div class="v1-avatar" style="background-color: {{ $colors[$i] }};">{{ $letter }}</div>
          @endforeach
        </div>
        <div>
          <div class="v1-hero-stat-label">+50,000 لاعب</div>
          <div class="v1-hero-stat-desc">عم يحجزو معنا كل أسبوع</div>
        </div>
      </div>
    </div>

    {{-- Phone panel --}}
    <div class="v1-hero-panel">
      <div class="v1-panel-dots"></div>
      <div class="v1-panel-circle-1"></div>
      <div class="v1-panel-circle-2"></div>

      <div class="v1-phone-wrapper">
        <x-marketing.yh-phone-mock :width="300">
          @include('marketing.components.phone-home-content')
        </x-marketing.yh-phone-mock>

        <div class="v1-floating-tag-1">⚡ حجز فوري</div>
        <div class="v1-floating-tag-2">⭐ ٤.٩ تقييم</div>
      </div>
    </div>
  </section>

  {{-- TICKER --}}
  <div class="v1-ticker">
    <div class="v1-ticker-content">
      @for($i = 0; $i < 3; $i++)
        <div class="v1-ticker-item">
          <span class="v1-ticker-sport">★ كرة قدم</span>
          <span class="v1-ticker-sport">★ تنس</span>
          <span class="v1-ticker-sport">★ سلة</span>
          <span class="v1-ticker-sport">★ طائرة</span>
          <span class="v1-ticker-sport">★ بادل</span>
          <span class="v1-ticker-sport">★ سكواش</span>
          <span class="v1-ticker-sport">★ بليارد</span>
          <span class="v1-ticker-sport">★ بولينغ</span>
        </div>
      @endfor
    </div>
  </div>

  {{-- STATS --}}
  <section class="v1-stats">
    <div class="v1-stats-grid">
      @foreach($C['stats'] as $i => $stat)
        <div class="v1-stat-card @if($i === 1) featured @endif">
          <div class="v1-stat-number">{{ $stat['num'] }}</div>
          <div class="v1-stat-label">{{ $stat['label'] }}</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- FEATURES --}}
  <section class="v1-features">
    @include('marketing.components.section-head', [
      'kicker' => '// المميزات',
      'title' => 'كل شي بحاجتو لتلعب',
      'sub' => 'من البحث للحجز للدفع — يلا حجيز عم يخلّيها أسهل من أي وقت.',
    ])

    <div class="v1-features-grid">
      @foreach($C['features'] as $i => $feature)
        <div class="v1-feature-card @if($i === 0) featured @endif">
          <div class="v1-feature-icon">{{ $feature['icon'] }}</div>
          <div class="v1-feature-title">{{ $feature['title'] }}</div>
          <div class="v1-feature-desc">{{ $feature['desc'] }}</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- HOW IT WORKS --}}
  <section class="v1-how-it-works">
    @include('marketing.components.section-head', [
      'kicker' => '// كيف بيشتغل',
      'title' => '٤ خطوات. ثلاثين ثانية.',
      'dark' => true,
    ])

    <div class="v1-steps-grid">
      @foreach($C['steps'] as $step)
        <div class="v1-step-card">
          <div class="v1-step-number">{{ $step['n'] }}</div>
          <div class="v1-step-title">{{ $step['title'] }}</div>
          <div class="v1-step-desc">{{ $step['desc'] }}</div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- PRICING --}}
  <section class="v1-pricing">
    @include('marketing.components.section-head', [
      'kicker' => '// الأسعار',
      'title' => 'خطط بسيطة. بدون مفاجآت.',
      'sub' => 'ابدأ مجاناً وارقّي وقت ما تجاهز.',
    ])

    <div class="v1-pricing-grid">
      @foreach($C['pricing'] as $pricing)
        <div class="v1-pricing-card @if($pricing['featured'] ?? false) featured @endif">
          <div class="v1-pricing-name">{{ $pricing['name'] }}</div>
          <div class="v1-pricing-amount">
            <span class="v1-pricing-price">{{ $pricing['price'] }}</span>
            @if($pricing['unit'] ?? false)
              <span class="v1-pricing-unit">{{ $pricing['unit'] }}</span>
            @endif
          </div>
          <div class="v1-pricing-desc">{{ $pricing['desc'] }}</div>
          <div class="v1-pricing-divider"></div>
          <div>
            @foreach($pricing['features'] as $feature)
              <div class="v1-pricing-feature">
                <span class="v1-pricing-check">✓</span>
                {{ $feature }}
              </div>
            @endforeach
          </div>
          <button class="v1-pricing-button">ابدا الآن</button>
        </div>
      @endforeach
    </div>
  </section>

  {{-- TESTIMONIALS --}}
  <section class="v1-testimonials">
    @include('marketing.components.section-head', [
      'kicker' => '// آراء العملاء',
      'title' => 'عم يحبّوه. منيح.',
    ])

    <div class="v1-testimonials-grid">
      @foreach($C['testimonials'] as $testimonial)
        <div class="v1-testimonial-card">
          <div class="v1-testimonial-rating">
            @for($i = 0; $i < $testimonial['rating']; $i++)
              <span class="v1-testimonial-star">★</span>
            @endfor
          </div>
          <div class="v1-testimonial-text">«{{ $testimonial['text'] }}»</div>
          <div class="v1-testimonial-author">
            <div class="v1-testimonial-avatar">{{ substr($testimonial['name'], 0, 1) }}</div>
            <div>
              <div class="v1-testimonial-name">{{ $testimonial['name'] }}</div>
              <div class="v1-testimonial-role">{{ $testimonial['role'] }}</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- GALLERY --}}
  <section class="v1-gallery">
    @include('marketing.components.section-head', [
      'kicker' => '// معرض الملاعب',
      'title' => 'أكتر من ٢,٠٠٠ ملعب',
      'sub' => 'من ملاعب الحارة لمدن الرياضة الكبيرة.',
    ])

    <div class="v1-gallery-grid">
      @php
      $galleries = [
        ['row' => 'span 2', 'label' => 'ملعب الفيحاء · دمشق'],
        ['label' => 'تشرين · برزة'],
        ['label' => 'الجلاء · المالكي'],
        ['label' => 'النورس · اللاذقية'],
        ['row' => 'span 2', 'label' => 'الأهلي · حلب'],
        ['label' => 'الكرامة · حمص'],
      ];
      @endphp
      @foreach($galleries as $gallery)
        <div class="v1-gallery-item @if(($gallery['row'] ?? false) === 'span 2') span-2-row @endif">
          <x-marketing.yh-stripe :label="$gallery['label']" />
        </div>
      @endforeach
    </div>
  </section>

  {{-- FAQ --}}
  <section class="v1-faq">
    <div class="v1-faq-header">
      <div class="v1-faq-header-kicker">// أسئلة متكررة</div>
      <h2 class="v1-faq-header-title">اسأل،<br/>منجاوب.</h2>
      <p class="v1-faq-header-sub">ما لقيت إجابة لسؤالك؟ تواصل معنا مباشرة.</p>
    </div>
    <div class="v1-faq-items">
      @foreach($C['faq'] as $item)
        <details class="v1-faq-item">
          <summary class="v1-faq-summary">
            {{ $item['q'] }}
            <span class="v1-faq-toggle">+</span>
          </summary>
          <div class="v1-faq-answer">{{ $item['a'] }}</div>
        </details>
      @endforeach
    </div>
  </section>

  {{-- FINAL CTA --}}
  <section style="padding: 100px 64px; background: var(--yh-green); position: relative; overflow: hidden;">
    <div style="position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.2) 1.5px, transparent 1.5px); background-size: 32px 32px;"></div>
    <div style="position: relative; text-align: center; max-width: 800px; margin: 0 auto; color: var(--yh-white);">
      <h2 style="font-size: 96px; font-weight: 900; line-height: 0.95; letter-spacing: -3px; margin: 0;">
        يلا، شو<br/>عم تستنى؟
      </h2>
      <p style="font-size: 20px; margin-top: 24px; opacity: 0.95;">حمّل التطبيق وابدا حجزك الأول الآن — مجاناً.</p>
      <div style="display: flex; gap: 16px; justify-content: center; margin-top: 40px;">
        <button style="background: var(--yh-ink); color: var(--yh-green-glow); border: none; padding: 20px 36px; border-radius: 999px; font-weight: 800; font-size: 16px; font-family: var(--yh-font-ar); cursor: pointer;">App Store ↓</button>
        <button style="background: var(--yh-white); color: var(--yh-ink); border: none; padding: 20px 36px; border-radius: 999px; font-weight: 800; font-size: 16px; font-family: var(--yh-font-ar); cursor: pointer;">Google Play ↓</button>
      </div>
    </div>
  </section>

  {{-- FOOTER --}}
  <footer class="v1-footer">
    <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 48px; margin-bottom: 48px;">
      <div>
        <x-marketing.yh-logo-lockup :size="0.85" :fg="'#fff'" :accent="'#22D26A'" :markBg="'#22D26A'" :markFg="'#0F1A14'" />
        <p style="font-size: 14px; opacity: 0.6; line-height: 1.7; margin-top: 20px; max-width: 320px;">{{ $C['footer_about'] }}</p>
      </div>
      @include('marketing.components.footer-col', ['title' => 'المنصة', 'links' => ['الرئيسية','المميزات','الأسعار','البطولات']])
      @include('marketing.components.footer-col', ['title' => 'الشركة', 'links' => ['من نحن','الوظائف','الأخبار','تواصل']])
      @include('marketing.components.footer-col', ['title' => 'القانوني', 'links' => ['الشروط','الخصوصية','الاسترداد','الكوكيز']])
    </div>
    <div style="border-top: 1px solid rgba(255,255,255,0.15); padding-top: 24px; display: flex; justify-content: space-between; font-size: 13px; opacity: 0.6;">
      <div>{{ $C['footer_copyright'] }}</div>
      <div>صُنع بـ ❤ في دمشق</div>
    </div>
  </footer>
</div>
