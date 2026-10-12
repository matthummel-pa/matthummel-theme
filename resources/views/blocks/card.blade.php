{{-- matthummel/card: uses the About service-card classes so it takes the theme card recipe as is. --}}
@php
  $icon = sanitize_key((string) ($attributes['icon'] ?? ''));
  $number = trim((string) ($attributes['number'] ?? ''));
@endphp
<article class="{{ trim('about-svc-card mh-card '.($attributes['className'] ?? '')) }}">
  @if ($number !== '')
    <span class="about-svc-card__n" aria-hidden="true">{{ $number }}</span>
  @endif
  @if ($icon !== '')
    <div class="about-svc-card__icon" aria-hidden="true">{!! \App\mh_svg_icon($icon, 22) !!}</div>
  @endif
  {!! $content !!}
</article>
