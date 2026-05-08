<div class="v1-phone-content">
  <div class="v1-phone-header">
    <div>
      <div class="v1-phone-greeting">أهلاً، كريم</div>
      <div class="v1-phone-question">وين رح تلعب اليوم؟</div>
    </div>
    <div class="v1-phone-avatar"></div>
  </div>

  <div class="v1-phone-search">
    🔍 دوّر عن ملعب…
  </div>

  <div class="v1-phone-sports">
    @foreach(['كرة قدم','تنس','سلة','طائرة'] as $i => $sport)
      <div class="v1-phone-sport-badge @if($i === 0) v1-phone-sport-badge-active @endif">{{ $sport }}</div>
    @endforeach
  </div>

  <div class="v1-phone-section-title">ملاعب قريبة منك</div>

  @php
  $courts = [
    ['name' => 'ملعب الفيحاء', 'location' => 'دمشق · المزة', 'price' => '٦٠ ل.س / س'],
    ['name' => 'تشرين الرياضية', 'location' => 'دمشق · برزة', 'price' => '٨٠ ل.س / س'],
    ['name' => 'ملعب الجلاء', 'location' => 'دمشق · المالكي', 'price' => '٥٠ ل.س / س'],
  ];
  @endphp

  @foreach($courts as $court)
    <div class="v1-phone-court-card">
      <div class="v1-phone-court-image"></div>
      <div class="v1-phone-court-info">
        <div class="v1-phone-court-name">{{ $court['name'] }}</div>
        <div class="v1-phone-court-location">{{ $court['location'] }}</div>
        <div class="v1-phone-court-price">{{ $court['price'] }}</div>
      </div>
      <div class="v1-phone-court-rating">★ ٤.٨</div>
    </div>
  @endforeach
</div>
