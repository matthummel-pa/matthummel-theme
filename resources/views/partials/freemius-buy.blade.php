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
  <div class="project-section__head">
    <p class="eyebrow">{{ __('Pricing', 'sage') }}</p>
    <h2 id="fs-buy-heading" class="display-title is-section">{{ __('Choose a plan', 'sage') }}</h2>
    <p class="project-section__intro">{{ __('A yearly license with updates and email support. Pay by card; the receipt and your license key come from Freemius.', 'sage') }}</p>
  </div>

  <ul class="fs-tiers">
    @foreach ($fs['tiers'] as $tier)
      <li class="fs-tier">
        <h3 class="fs-tier__name">{{ $tier['label'] }}</h3>
        <p class="fs-tier__price">
          <strong>{{ $tier['price'] }}</strong>
          <span>{{ __('per year', 'sage') }}</span>
        </p>
        <button type="button" class="btn" data-fs-buy data-licenses="{{ (int) $tier['licenses'] }}">
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
