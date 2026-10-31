{{--
  Header call to action: the Get updates pill (icon only on narrow screens, see studio.css).
--}}
@php
  $updatesUrl = home_url('/get-updates/');
@endphp
<div class="header-updates" data-updates>
  {{-- aria-label: below 900px (and 901 to 1139px, see studio.css) only the icon shows, so the visible label can't name the link. --}}
  <a class="header-avail header-updates__link" href="{{ esc_url($updatesUrl) }}" aria-label="{{ esc_attr__('Get updates', 'sage') }}">
    <span class="header-updates__icon" aria-hidden="true">{!! \App\mh_svg_icon('mail', 16) !!}</span>
    <span class="header-avail__label">{{ __('Get updates', 'sage') }}</span>
  </a>
</div>
