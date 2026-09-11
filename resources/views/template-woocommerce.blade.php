{{--
  Template Name: WooCommerce
  Cart, Checkout, and My account — classic shortcodes, not WooCommerce blocks.

  2026 redesign: purpose-built compact headers for each page type, replacing
  the generic hero-panel pattern. Cart → two-column with order summary. Checkout
  → secure form with trust signals. Account → dashboard with clear nav.
--}}
@extends('layouts.app')

@php
  $title     = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $shortcode = \App\mh_woocommerce_page_shortcode();
  if ($shortcode === '') {
    $shortcode = trim((string) get_post_field('post_content', get_the_ID()));
  }
  $shopUrl    = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
  $catalogUrl = \App\mh_theme_catalog_url();
  $isCart     = function_exists('is_cart') && is_cart();
  $isCheckout = function_exists('is_checkout') && is_checkout();
  $isAccount  = function_exists('is_account_page') && is_account_page();
  $isLoggedIn = is_user_logged_in();

  $cartCount = 0;
  $cartTotal = '';
  if (function_exists('WC') && WC()->cart) {
    $cartCount = (int) WC()->cart->get_cart_contents_count();
    $cartTotal = function_exists('wc_price') ? WC()->cart->get_cart_total() : '';
  }

  $crumbItems = [
    ['label' => __('Home', 'sage'), 'url' => home_url('/')],
    ['label' => __('Shop', 'sage'), 'url' => $shopUrl],
    ['label' => $title, 'current' => true],
  ];

  $servicesOnly = \App\mh_cart_is_services_only();
  $trustItems   = \App\mh_checkout_trust_items();
  $startHere    = \App\mh_checkout_start_here_products();

  $lead = '';
  if ($isCart) {
    $lead = $cartCount > 0
      ? ($servicesOnly
        ? __('Ready when you are. Checkout books the work — I write back after payment.', 'sage')
        : __('Ready when you are. Checkout is one screen. Guest checkout is fine.', 'sage'))
      : __('Nothing here yet. Grab a theme or an Acreline add-on and come back.', 'sage');
  } elseif ($isCheckout) {
    $lead = $servicesOnly
      ? __('Pay once. I email next steps. No surprise retainers.', 'sage')
      : __('Pay once. The zip is in the receipt email. Guest checkout is fine.', 'sage');
  } elseif ($isAccount) {
    $lead = $isLoggedIn
      ? __('Orders, downloads, and billing details in one place.', 'sage')
      : __('Log in to view your orders and download your themes.', 'sage');
  }

  // Step indicator for checkout progress.
  $steps = [
    __('Cart', 'sage')     => ['url' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'), 'done' => ! $isCart],
    __('Checkout', 'sage') => ['url' => '', 'done' => false],
    __('Complete', 'sage') => ['url' => '', 'done' => false],
  ];
@endphp

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════════════
     COMPACT PAGE HEADER — purpose-built per page type (no hero-panel)
════════════════════════════════════════════════════════════════════════════ --}}
<div class="woo-page-header{{ $isCart ? ' woo-page-header--cart' : '' }}{{ $isCheckout ? ' woo-page-header--checkout' : '' }}{{ $isAccount ? ' woo-page-header--account' : '' }}">
  <div class="container wide woo-page-header__inner">

    @include('partials.woocommerce-crumb', ['items' => $crumbItems])

    <div class="woo-page-header__row">
      <h1 class="woo-page-header__title">{{ $title }}</h1>

      @if ($isCheckout)
        <span class="woo-secure-badge" aria-label="{{ __('Secure checkout', 'sage') }}">
          {!! \App\mh_svg_icon('check', 12) !!}
          <span>{{ __('Secure checkout', 'sage') }}</span>
        </span>
      @elseif ($isCart && $cartCount > 0)
        <span class="woo-page-header__count">
          {{ sprintf(_n('%d item', '%d items', $cartCount, 'sage'), $cartCount) }}
          @if ($cartTotal !== '')
            <span class="woo-page-header__total">{!! wp_kses_post($cartTotal) !!}</span>
          @endif
        </span>
      @elseif ($isAccount && $isLoggedIn)
        <span class="woo-page-header__account-id">
          {!! \App\mh_svg_icon('user', 13) !!}
          {{ wp_get_current_user()->display_name }}
        </span>
      @endif
    </div>

    @if ($lead !== '')
      <p class="woo-page-header__lead">{{ $lead }}</p>
    @endif

    {{-- Checkout progress steps --}}
    @if ($isCheckout || $isCart)
      <nav class="woo-steps" aria-label="{{ __('Checkout steps', 'sage') }}">
        <ol class="woo-steps__list">
          <li class="woo-steps__item{{ $isCart ? ' is-current' : ' is-done' }}">
            @if (! $isCart)
              <a href="{{ function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/') }}" class="woo-steps__link">
                {!! \App\mh_svg_icon('check', 11) !!} {{ __('Cart', 'sage') }}
              </a>
            @else
              <span>{{ __('Cart', 'sage') }}</span>
            @endif
          </li>
          <li class="woo-steps__sep" aria-hidden="true">›</li>
          <li class="woo-steps__item{{ $isCheckout ? ' is-current' : '' }}">
            @if ($isCart)
              <a href="{{ function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/') }}" class="woo-steps__link">{{ __('Checkout', 'sage') }}</a>
            @else
              <span>{{ __('Checkout', 'sage') }}</span>
            @endif
          </li>
          <li class="woo-steps__sep" aria-hidden="true">›</li>
          <li class="woo-steps__item">
            <span>{{ __('Confirmation', 'sage') }}</span>
          </li>
        </ol>
      </nav>
    @endif

  </div>
</div>

@if ($isCheckout && $cartCount > 0)
  @php
    $suggestedWants = \App\mh_cart_suggested_want_keys();
    $liveBrief = \App\mh_install_brief_text($suggestedWants);
    if ($liveBrief === '') {
      $liveBrief = $servicesOnly
        ? __('Tap what applies. This sentence becomes your kickoff.', 'sage')
        : __('The zip lands in the receipt email. Tap below if you want help installing.', 'sage');
    }
  @endphp
  <div class="woo-receipt" aria-label="{{ __('Kickoff brief preview', 'sage') }}">
    <div class="container wide">
      <article class="woo-receipt__card mh-ticket" data-mh-live-ticket>
        <p class="woo-receipt__meta">{{ __('From Matt · kickoff brief', 'sage') }}</p>
        <h2 class="woo-receipt__subject">
          {{ $servicesOnly ? __('What I will start with', 'sage') : __('What happens after you pay', 'sage') }}
        </h2>
        <p class="woo-receipt__body" data-mh-live-brief>{{ $liveBrief }}</p>
        <p class="woo-receipt__site" data-mh-live-site hidden></p>
      </article>
    </div>
  </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     TRUST STRIP — Cart and Checkout only
════════════════════════════════════════════════════════════════════════════ --}}
@if ($isCart || $isCheckout)
  <div class="woo-trust-strip" aria-label="{{ __('Purchase assurance', 'sage') }}">
    <div class="container wide woo-trust-strip__inner">
      @foreach ($trustItems as $item)
        <span class="woo-trust-item">
          {!! \App\mh_svg_icon($item['icon'], 13) !!}
          @if (! empty($item['href']))
            <a href="{{ esc_url($item['href']) }}">{{ $item['label'] }}</a>
          @else
            {{ $item['label'] }}
          @endif
        </span>
      @endforeach
    </div>
  </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     MAIN WC CONTENT
════════════════════════════════════════════════════════════════════════════ --}}
<div class="container wide page-block woocommerce-wrap{{ $isCheckout ? ' woocommerce-wrap--checkout' : '' }}{{ $isAccount ? ' woocommerce-wrap--account' : '' }}{{ $isCart ? ' woocommerce-wrap--cart' : '' }}">

  @if ($shortcode !== '')
    {!! do_shortcode($shortcode) !!}

    {{-- Empty cart shown at checkout --}}
    @if ($isCheckout && function_exists('WC') && WC()->cart && WC()->cart->is_empty())
      <div class="woo-empty" role="status">
        <div class="woo-empty__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 28) !!}</div>
        <p class="woo-empty__title">{{ __('Your cart is empty', 'sage') }}</p>
        <p class="woo-empty__text">{{ __('Start with Acreline, or pick an add-on from Services. Checkout is one screen.', 'sage') }}</p>
        <p class="woo-empty__actions">
          <a class="btn" href="{{ esc_url($catalogUrl) }}">
            {!! \App\mh_svg_icon('briefcase', 15) !!} {{ __('Browse products', 'sage') }}
          </a>
          <a class="btn btn-outline" href="{{ esc_url($shopUrl) }}">{{ __('Open shop', 'sage') }}</a>
        </p>
      </div>
    @endif

    {{-- Empty cart page --}}
    @if ($isCart && $cartCount === 0)
      @if ($startHere !== [])
        <div class="woo-start-here" aria-label="{{ __('Start here', 'sage') }}">
          <p class="woo-start-here__label">{{ __('Easy start', 'sage') }}</p>
          <div class="woo-start-here__grid">
            @foreach ($startHere as $pick)
              <article class="woo-start-here__card">
                <h3 class="woo-start-here__title">
                  <a href="{{ esc_url($pick['permalink']) }}">{{ $pick['title'] }}</a>
                </h3>
                @if ($pick['price'] !== '')
                  <p class="woo-start-here__price">{{ $pick['price'] }}</p>
                @endif
                @if ($pick['add_to_cart_url'] !== '')
                  <a class="btn btn--sm" href="{{ esc_url($pick['add_to_cart_url']) }}">{{ __('Add to cart', 'sage') }}</a>
                @endif
              </article>
            @endforeach
          </div>
        </div>
      @endif
      <div class="woo-continue-shopping">
        <a class="btn btn-outline" href="{{ esc_url($shopUrl) }}">
          {!! \App\mh_svg_icon('arrow-left', 14) !!} {{ __('Browse the shop', 'sage') }}
        </a>
      </div>
    @endif

  @else
    <div class="woo-empty" role="status">
      <div class="woo-empty__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 28) !!}</div>
      <p class="woo-empty__title">{{ __('Shop tools are offline', 'sage') }}</p>
      <p class="woo-empty__text">{{ __('WooCommerce is not active, so this page has nothing to show yet.', 'sage') }}</p>
      <p class="woo-empty__actions">
        <a class="btn" href="{{ esc_url($catalogUrl) }}">{{ __('Browse work', 'sage') }}</a>
      </p>
    </div>
  @endif

</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     FOOTER TRUST / REASSURANCE BAND — Cart only
════════════════════════════════════════════════════════════════════════════ --}}
@if ($isCart && $cartCount > 0)
  <div class="woo-cart-footer-band">
    <div class="container wide woo-cart-footer-band__inner">
      @if ($servicesOnly)
        <div class="woo-cart-footer-item">
          {!! \App\mh_svg_icon('check', 16) !!}
          <div>
            <strong>{{ __('What happens next', 'sage') }}</strong>
            <span>{{ __('I email a short kickoff after payment. Checkout has a notes field for wants.', 'sage') }}</span>
          </div>
        </div>
        <div class="woo-cart-footer-item">
          {!! \App\mh_svg_icon('calendar', 16) !!}
          <div>
            <strong>{{ __('Timing', 'sage') }}</strong>
            <span>{{ __('Reply within one business day (ET)', 'sage') }}</span>
          </div>
        </div>
      @else
        <div class="woo-cart-footer-item">
          {!! \App\mh_svg_icon('download', 16) !!}
          <div>
            <strong>{{ __('Digital delivery', 'sage') }}</strong>
            <span>{{ __('Download link in your receipt email', 'sage') }}</span>
          </div>
        </div>
        <div class="woo-cart-footer-item">
          {!! \App\mh_svg_icon('check', 16) !!}
          <div>
            <strong>{{ __('GPL license', 'sage') }}</strong>
            <span>{{ __('You own the code after purchase', 'sage') }}</span>
          </div>
        </div>
      @endif
      <div class="woo-cart-footer-item">
        {!! \App\mh_svg_icon('mail', 16) !!}
        <div>
          <strong>{{ __('Need a custom build?', 'sage') }}</strong>
          <a href="{{ home_url('/contact/') }}">{{ __('Say hello →', 'sage') }}</a>
        </div>
      </div>
    </div>
  </div>
@endif

@endsection
