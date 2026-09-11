@php
  if (! \App\mh_shop_ready() || ! function_exists('wc_get_cart_url')) {
    return;
  }
  $lines = \App\mh_studio_cart_lines();
  $count = \App\mh_cart_count();
  $total = \App\mh_studio_cart_total();
  $servicesOnly = \App\mh_cart_is_services_only();
  $cartUrl = wc_get_cart_url();
  $checkoutUrl = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
  $hideChip = (function_exists('is_cart') && is_cart()) || (function_exists('is_checkout') && is_checkout());
  $next = $servicesOnly
    ? __('I email kickoff after payment.', 'sage')
    : __('The zip is in the receipt email.', 'sage');
@endphp

<div class="mh-slip-overlay" data-mh-slip-overlay hidden></div>

<div
  id="mh-slip"
  class="mh-slip"
  role="dialog"
  aria-modal="true"
  aria-labelledby="mh-slip-title"
  aria-hidden="true"
  hidden
>
  <div class="mh-slip__ticket">
    <div class="mh-slip__head">
      <p class="mh-slip__kicker">{{ __('Studio slip', 'sage') }}</p>
      <button type="button" class="mh-slip__close" data-mh-slip-close aria-label="{{ __('Close cart', 'sage') }}">
        <span aria-hidden="true">✕</span>
      </button>
    </div>
    <h2 id="mh-slip-title" class="mh-slip__title">
      {{ $count > 0 ? __('Ready when you are.', 'sage') : __('Nothing on the slip yet.', 'sage') }}
    </h2>
    <p class="mh-slip__lede">
      {{ $count > 0 ? $next.' '.__('Guest checkout is fine.', 'sage') : __('Add a theme or an Acreline add-on. I will keep the slip here.', 'sage') }}
    </p>

    @if ($lines !== [])
      <ul class="mh-slip__lines">
        @foreach ($lines as $line)
          <li class="mh-slip__line">
            <a href="{{ esc_url($line['permalink']) }}">{{ $line['name'] }}</a>
            <span>{{ $line['price'] }}</span>
          </li>
        @endforeach
      </ul>
      @if ($total !== '')
        <p class="mh-slip__total">
          <span>{{ __('Total', 'sage') }}</span>
          <strong>{!! wp_kses_post($total) !!}</strong>
        </p>
      @endif
      <div class="mh-slip__actions">
        <a class="btn" href="{{ esc_url($checkoutUrl) }}">{{ __('Continue to checkout', 'sage') }}</a>
        <a class="h-text-arrow" href="{{ esc_url($cartUrl) }}">{{ __('Full cart', 'sage') }} <span aria-hidden="true">→</span></a>
      </div>
      <p class="mh-slip__stamp">{{ __('No mailing list · SSL', 'sage') }}</p>
    @else
      <div class="mh-slip__actions">
        <a class="btn" href="{{ esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')) }}">
          {{ __('Browse products', 'sage') }}
        </a>
      </div>
    @endif
  </div>
</div>

@if ($count > 0 && ! $hideChip)
  <button
    type="button"
    class="mh-slip-chip"
    data-mh-slip-open
    aria-controls="mh-slip"
  >
    <span class="mh-slip-chip__count">{{ $count }}</span>
    <span class="mh-slip-chip__copy">
      {{ $count === 1 ? $lines[0]['name'] : sprintf(__('%d on your slip', 'sage'), $count) }}
      <em>{{ __('Checkout', 'sage') }}</em>
    </span>
  </button>
@endif
