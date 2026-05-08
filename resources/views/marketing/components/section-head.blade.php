<div class="v1-section-head">
  <div class="v1-section-kicker @if($dark ?? false) v1-section-kicker-dark @endif">{{ $kicker }}</div>
  <h2 class="v1-section-title @if($dark ?? false) v1-section-title-dark @endif">{{ $title }}</h2>
  @if($sub ?? false)
    <p class="v1-section-sub @if($dark ?? false) v1-section-sub-dark @endif">{{ $sub }}</p>
  @endif
</div>
