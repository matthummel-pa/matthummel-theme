@php
  $bookingUrl = \App\mh_booking_url();
  $bookingClass = $class ?? 'btn btn-outline';
  $bookingBlock = ! empty($block);
@endphp
@if ($bookingUrl !== '')
  <a
    class="{{ $bookingClass }}"
    href="{{ esc_url($bookingUrl) }}"
    rel="noopener noreferrer"
    target="_blank"
    @if ($bookingBlock) style="width:100%;justify-content:center;margin-top:.85rem" @endif
  >
    {!! \App\mh_svg_icon('calendar', 16) !!}
    {{ __('Book a 15-minute call', 'sage') }}
    <span class="visually-hidden">{{ __('(opens in a new window)', 'sage') }}</span>
  </a>
@endif
