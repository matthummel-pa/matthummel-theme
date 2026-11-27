{{--
  matthummel/cc-pricing: the Cobble & Candle plans and the Freemius checkout (data attributes read by resources/js/freemius-checkout.js).
  Plans, prices and Freemius ids come from the product catalog (resources/data/product-catalog.json), so a price change is one edit there.
--}}
@php
  $slug = 'cobbleandcandle';
  $p = \App\mh_product_catalog_seed_data()[$slug] ?? [];
  $fs = \App\mh_project_freemius($slug);
  $tiers = $fs['tiers'] ?? [];
  $amount = static fn (string $s): string => number_format((float) preg_replace('/[^0-9.]/', '', $s), 2, '.', '');
  $tierNotes = [
    1 => __('Activate on 1 site', 'sage'),
    5 => __('Activate on up to 5 sites', 'sage'),
    0 => __('Activate on unlimited sites, white-label', 'sage'),
  ];
@endphp
@if ($fs)
  <section class="cc-section cc-section--parch" id="pricing" aria-labelledby="cc-pricing-h"
    data-fs-checkout
    data-product="{{ esc_attr($fs['product_id']) }}"
    data-plan="{{ esc_attr($fs['plan_id']) }}"
    data-key="{{ esc_attr($fs['public_key']) }}"
    data-account="{{ esc_url($fs['account_url']) }}"
    data-title="{{ $p['title'] ?? 'Cobble & Candle' }}"
    data-subtitle="{{ __('A yearly license. Freemius emails your receipt and key. I never see the card.', 'sage') }}"
    data-modal-title="{{ sprintf(__('Checkout, %s', 'sage'), $p['title'] ?? __('Cobble & Candle', 'sage')) }}"
    @if ($fs['sandbox'] !== null)
      data-sandbox-token="{{ esc_attr($fs['sandbox']['token']) }}"
      data-sandbox-ctx="{{ esc_attr($fs['sandbox']['ctx']) }}"
    @endif
  >
    <div class="cc-wrap">
      <div class="cc-head">
        <p class="cc-eyebrow">{{ __('Pricing', 'sage') }}</p>
        <h2 id="cc-pricing-h">{{ __('Pick a plan', 'sage') }}</h2>
        <p>{{ __('Every plan is a yearly license with updates and email support. Pay by card; the receipt and your license key come from Freemius.', 'sage') }}</p>
      </div>
      <div class="cc-tiers">
        @foreach ($tiers as $tier)
          <div class="cc-tier">
            <h3>{{ $tier['label'] }}</h3>
            <p class="cc-tier__price"><strong>{{ $tier['price'] }}</strong> <span>{{ __('per year', 'sage') }}</span></p>
            <ul>
              <li>{{ $tierNotes[(int) $tier['licenses']] ?? '' }}</li>
              <li>{{ __('The theme and the bundled Core plugin', 'sage') }}</li>
              <li>{{ __('Updates and email support for the year', 'sage') }}</li>
              <li>{{ __('Owner guide and support guide', 'sage') }}</li>
            </ul>
            <button type="button" class="cc-btn cc-btn--dark cc-btn--wide" data-fs-buy data-licenses="{{ (int) $tier['licenses'] }}" data-plan-label="{{ esc_attr($tier['label']) }}" data-price="{{ esc_attr($amount($tier['price'])) }}">
              {{ __('Buy', 'sage') }} {{ $tier['label'] }}<span class="cc-vh">, {{ $tier['price'] }} {{ __('per year', 'sage') }}</span>
            </button>
          </div>
        @endforeach
      </div>
      <p class="cc-status" data-fs-status role="status" hidden></p>
      <p class="cc-fine">
        {{ __('Billed once a year. Plans renew each year. If it has a bug or problem I can’t resolve, you get a refund within 30 days.', 'sage') }}
        <a href="{{ esc_url(home_url('/refunds/')) }}">{{ __('Refund policy', 'sage') }}</a> ·
        <a href="{{ esc_url(home_url('/license/')) }}">{{ __('License', 'sage') }}</a> ·
        <a href="{{ esc_url(home_url('/terms/#terms-store-heading')) }}">{{ __('Terms', 'sage') }}</a>.
        {{ __('The code is GPL v2 or later. The key covers updates and support, and never locks your site.', 'sage') }}
      </p>
    </div>
  </section>
@endif
