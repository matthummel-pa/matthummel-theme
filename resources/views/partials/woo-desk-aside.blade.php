{{--
  Sticky tools sidebar for Cart / Checkout (mirrors post-sidebar).
  Expects: $context ('cart'|'checkout'), $servicesOnly (bool).
--}}
@php
  $context = $context ?? 'cart';
  $servicesOnly = $servicesOnly ?? \App\mh_cart_is_services_only();
  $steps = \App\mh_checkout_next_steps();
  $addons = \App\mh_cart_sidebar_addons(3);
  $trust = \App\mh_checkout_trust_items();
  $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
  $contactUrl = home_url('/contact/');
  $faq = \App\mh_checkout_sidebar_faq();
@endphp

<div class="woo-desk-tools">

  @if ($context === 'checkout')
    @php
      $suggestedWants = \App\mh_cart_suggested_want_keys();
      $liveBrief = \App\mh_install_brief_text($suggestedWants);
      if ($liveBrief === '') {
        $liveBrief = $servicesOnly
          ? __('Tap what applies. This sentence becomes your kickoff.', 'sage')
          : __('The zip lands in the receipt email. Tap below if you want help installing.', 'sage');
      }
    @endphp
    <article class="side-card woo-desk-card woo-desk-card--brief mh-ticket" data-mh-live-ticket aria-label="{{ __('Kickoff brief preview', 'sage') }}">
      <p class="side-card-title">{{ __('Kickoff brief', 'sage') }}</p>
      <h3 class="woo-desk-brief__subject">
        {{ $servicesOnly ? __('What I will start with', 'sage') : __('After you pay', 'sage') }}
      </h3>
      <p class="woo-desk-brief__body" data-mh-live-brief>{{ $liveBrief }}</p>
      <p class="woo-desk-brief__site" data-mh-live-site hidden></p>
    </article>
  @endif

  <section class="side-card woo-desk-card" aria-labelledby="woo-desk-next-h">
    <h2 id="woo-desk-next-h" class="side-card-title">{{ __('What happens next', 'sage') }}</h2>
    <ol class="woo-desk-steps">
      @foreach ($steps as $i => $step)
        <li class="woo-desk-steps__item">
          <span class="woo-desk-steps__num" aria-hidden="true">{{ $i + 1 }}</span>
          <span class="woo-desk-steps__copy">
            <strong>{{ $step['title'] }}</strong>
            <span>{{ $step['text'] }}</span>
          </span>
        </li>
      @endforeach
    </ol>
  </section>

  <section class="side-card woo-desk-card" aria-labelledby="woo-desk-trust-h">
    <h2 id="woo-desk-trust-h" class="side-card-title">{{ __('Included', 'sage') }}</h2>
    <ul class="woo-desk-trust">
      @foreach ($trust as $item)
        <li class="woo-desk-trust__item">
          {!! \App\mh_svg_icon($item['icon'], 14) !!}
          @if (! empty($item['href']))
            <a href="{{ esc_url($item['href']) }}">{{ $item['label'] }}</a>
          @else
            <span>{{ $item['label'] }}</span>
          @endif
        </li>
      @endforeach
    </ul>
  </section>

  @if ($faq !== [])
    <details class="side-card side-card--fold woo-desk-card">
      <summary class="side-card-title">{{ __('Quick answers', 'sage') }}</summary>
      <dl class="woo-desk-faq">
        @foreach ($faq as $row)
          <div class="woo-desk-faq__row">
            <dt>{{ $row['q'] }}</dt>
            <dd>{{ $row['a'] }}</dd>
          </div>
        @endforeach
      </dl>
    </details>
  @endif

  @if ($addons !== [])
    <section class="side-card woo-desk-card" aria-labelledby="woo-desk-addons-h">
      <h2 id="woo-desk-addons-h" class="side-card-title">{{ __('Often added', 'sage') }}</h2>
      <ul class="woo-desk-addons">
        @foreach ($addons as $addon)
          <li class="woo-desk-addons__item">
            <div class="woo-desk-addons__copy">
              <a class="woo-desk-addons__title" href="{{ esc_url($addon['permalink']) }}">{{ $addon['title'] }}</a>
              @if ($addon['price'] !== '')
                <span class="woo-desk-addons__price">{{ $addon['price'] }}</span>
              @endif
            </div>
            @if ($addon['add_to_cart_url'] !== '')
              <a class="woo-desk-addons__add" href="{{ esc_url($addon['add_to_cart_url']) }}">{{ __('Add', 'sage') }}</a>
            @endif
          </li>
        @endforeach
      </ul>
    </section>
  @endif

  <section class="side-card woo-desk-card woo-desk-card--help" aria-labelledby="woo-desk-help-h">
    <h2 id="woo-desk-help-h" class="side-card-title">{{ __('Need a hand?', 'sage') }}</h2>
    <p class="woo-desk-help__text">
      {{ $servicesOnly
        ? __('Questions about scope or timing? Ask before you pay.', 'sage')
        : __('Questions about the zip or install? Ask anytime.', 'sage') }}
    </p>
    <div class="woo-desk-help__actions">
      <a class="woo-desk-help__btn" href="{{ esc_url($contactUrl) }}">
        {!! \App\mh_svg_icon('mail', 14) !!}
        {{ __('Say hello', 'sage') }}
      </a>
      @if ($context === 'cart')
        <a class="woo-desk-help__link" href="{{ esc_url($shopUrl) }}">{{ __('Keep shopping', 'sage') }}</a>
      @endif
    </div>
  </section>

</div>
