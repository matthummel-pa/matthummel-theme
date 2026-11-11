{{-- Freemius plan picker on a project page. Expects $fs from \App\mh_project_freemius() and $title. Checkout opens in an overlay, so there is no cart or checkout page. --}}
<section class="project-section fs-buy" id="buy" aria-labelledby="fs-buy-heading"
  data-fs-checkout
  data-product="{{ esc_attr($fs['product_id']) }}"
  data-plan="{{ esc_attr($fs['plan_id']) }}"
  data-key="{{ esc_attr($fs['public_key']) }}"
  data-account="{{ esc_url($fs['account_url']) }}"
  data-title="{{ esc_attr($title) }}"
  @if ($fs['sandbox'] !== null)
    data-sandbox-token="{{ esc_attr($fs['sandbox']['token']) }}"
    data-sandbox-ctx="{{ esc_attr($fs['sandbox']['ctx']) }}"
  @endif
>
  @php
    // Per-site price from the plan's own numbers; "best value" is only the lowest per-site price, nothing invented.
    $perSite = [];
    foreach ($fs['tiers'] as $i => $tier) {
      $amount = (float) preg_replace('/[^0-9.]/', '', (string) $tier['price']);
      $count = (int) $tier['licenses'];
      if ($amount > 0 && $count > 0) {
        $perSite[$i] = $amount / $count;
      }
    }
    $bestTier = count($perSite) > 1 ? (int) array_search(min($perSite), $perSite, true) : null;
    $included = [
      __('A year of updates', 'sage'),
      __('Email support from me', 'sage'),
      __('30-day refund if I can’t fix a problem', 'sage'),
    ];
  @endphp
  <div class="project-section__head">
    <p class="eyebrow">{{ __('Pricing', 'sage') }}</p>
    <h2 id="fs-buy-heading" class="display-title is-section">{{ __('Choose a plan', 'sage') }}</h2>
    <p class="project-section__intro">{{ __('Pick how many sites you will run it on. Every plan is a yearly license paid by card, and Freemius sends your receipt and license key.', 'sage') }}</p>
  </div>

  <ul class="fs-tiers">
    @foreach ($fs['tiers'] as $i => $tier)
      @php
        $count = (int) $tier['licenses'];
        $perSiteLabel = isset($perSite[$i]) && $count > 1 ? sprintf(__('About $%s per site', 'sage'), number_format($perSite[$i], 2)) : '';
      @endphp
      <li class="fs-tier{{ $bestTier === $i ? ' is-best' : '' }}">
        @if ($bestTier === $i)
          <span class="fs-tier__badge">{{ __('Lowest price per site', 'sage') }}</span>
        @endif
        <h3 class="fs-tier__name">{{ $tier['label'] }}</h3>
        <p class="fs-tier__for">
          @if ($count === 0)
            {{ __('Use it on as many of your sites as you like.', 'sage') }}
          @elseif ($count === 1)
            {{ __('One site, one license key.', 'sage') }}
          @else
            {{ sprintf(__('Up to %d sites, one license key.', 'sage'), $count) }}
          @endif
        </p>
        <p class="fs-tier__price">
          <strong>{{ $tier['price'] }}</strong>
          <span>{{ __('per year', 'sage') }}</span>
        </p>
        @if ($perSiteLabel !== '')
          <p class="fs-tier__per">{{ $perSiteLabel }}</p>
        @endif
        <ul class="fs-tier__list">
          @foreach ($included as $line)
            <li>{{ $line }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn fs-tier__buy" data-fs-buy data-licenses="{{ (int) $tier['licenses'] }}" data-plan-label="{{ esc_attr($tier['label']) }}" data-price="{{ esc_attr(number_format((float) preg_replace('/[^0-9.]/', '', $tier['price']), 2, '.', '')) }}">
          {!! \App\mh_svg_icon('cart', 15) !!}
          {{ __('Buy', 'sage') }}<span class="visually-hidden"> {{ sprintf(__('%1$s, %2$s per year', 'sage'), $tier['label'], $tier['price']) }}</span>
        </button>
      </li>
    @endforeach
  </ul>

  <p class="fs-buy__note" role="status" data-fs-status hidden></p>
  <p class="fs-buy__note">
    {{ __('If it has a bug or problem I can’t resolve, you get a refund within 30 days.', 'sage') }}
    <a href="{{ esc_url($fs['account_url']) }}">{{ __('Already bought? Open your account.', 'sage') }}</a>
  </p>
</section>
