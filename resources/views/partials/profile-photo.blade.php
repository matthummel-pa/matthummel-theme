@php
  $size = isset($size) ? (int) $size : 80;
  $class = $class ?? 'profile-photo';
  $decorative = ! empty($decorative);
  $eager = ! empty($eager);
  $url = \App\mh_profile_photo_url(max(64, $size * 2));
@endphp
@if ($url)
  <img
    class="{{ $class }}"
    src="{{ esc_url($url) }}"
    width="{{ $size }}"
    height="{{ $size }}"
    decoding="async"
    @if ($decorative)
      alt=""
    @else
      alt="{{ __('Matt Hummel', 'sage') }}"
    @endif
    @if ($eager)
      fetchpriority="high"
    @else
      loading="lazy"
    @endif
  >
@endif
