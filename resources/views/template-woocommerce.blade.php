{{--
  Template Name: WooCommerce
  Cart, Checkout, and My account — classic shortcodes, not WooCommerce blocks.

  Cart / Checkout / Account use a post-like desk: main column left, sticky tools aside right.
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

  $accountHeader = $isAccount ? \App\mh_account_desk_header() : ['title' => $title, 'lead' => ''];
  if ($isAccount) {
    $title = $accountHeader['title'];
  }

  $crumbItems = [
    ['label' => __('Home', 'sage'), 'url' => home_url('/')],
    ['label' => __('Shop', 'sage'), 'url' => $shopUrl],
  ];
  if ($isAccount && $isLoggedIn && \App\mh_account_endpoint() !== '') {
    $crumbItems[] = [
      'label' => __('My account', 'sage'),
      'url' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/'),
    ];
  }
  $crumbItems[] = ['label' => $title, 'current' => true];

  $servicesOnly = \App\mh_cart_is_services_only();
  $startHere    = \App\mh_checkout_start_here_products();
  $hasDesk      = (($isCart || $isCheckout) && $cartCount > 0) || $isAccount;

  $lead = '';
  if ($isCart) {
    $lead = $cartCount > 0
      ? ($servicesOnly
        ? __('Review the work, then checkout. I write back after payment.', 'sage')
        : __('Review your pack, then checkout on the next screen. Guest checkout is fine.', 'sage'))
      : __('Nothing here yet. Grab a theme or an Acreline add-on and come back.', 'sage');
  } elseif ($isCheckout) {
    $lead = $servicesOnly
      ? __('Pay once on the left. Order tools stay on the right.', 'sage')
      : __('Pay once on the left. The zip is in the receipt email.', 'sage');
  } elseif ($isAccount) {
    $lead = $accountHeader['lead'];
  }
@endphp

@section('content')

{{-- Compact page header --}}
<div class="woo-page-header{{ $isCart ? ' woo-page-header--cart' : '' }}{{ $isCheckout ? ' woo-page-header--checkout' : '' }}{{ $isAccount ? ' woo-page-header--account' : '' }}{{ $hasDesk ? ' woo-page-header--desk' : '' }}">
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
    @elseif ($isAccount && $isLoggedIn)
      @php
        $accountLinks = [
          ['endpoint' => 'dashboard', 'label' => __('Dashboard', 'sage'), 'url' => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('dashboard') : home_url('/my-account/')],
          ['endpoint' => 'downloads', 'label' => __('Downloads', 'sage'), 'url' => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('downloads') : home_url('/my-account/downloads/')],
          ['endpoint' => 'orders', 'label' => __('Orders', 'sage'), 'url' => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('orders') : home_url('/my-account/orders/')],
          ['endpoint' => 'edit-account', 'label' => __('Details', 'sage'), 'url' => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('edit-account') : home_url('/my-account/edit-account/')],
        ];
        $currentEndpoint = \App\mh_account_endpoint();
        if ($currentEndpoint === '') {
          $currentEndpoint = 'dashboard';
        }
      @endphp
      <nav class="woo-account-pills" aria-label="{{ __('Account sections', 'sage') }}">
        <ul class="woo-account-pills__list">
          @foreach ($accountLinks as $link)
            <li>
              <a
                class="woo-account-pills__link{{ $currentEndpoint === $link['endpoint'] ? ' is-current' : '' }}"
                href="{{ esc_url($link['url']) }}"
                @if ($currentEndpoint === $link['endpoint']) aria-current="page" @endif
              >{{ $link['label'] }}</a>
            </li>
          @endforeach
        </ul>
      </nav>
    @endif

  </div>
</div>

@php
  $wooPills = [];
  if ($isCart) {
      $wooPills[] = ['woo-page-main', __('Cart', 'sage')];
      if ($cartCount === 0 && $startHere !== []) {
          $wooPills[] = ['woo-start-here', __('Easy start', 'sage')];
      } elseif ($hasDesk) {
          $wooPills[] = ['woo-desk-help-h', __('Help', 'sage')];
      }
  } elseif ($isCheckout) {
      $wooPills[] = ['woo-page-main', __('Payment', 'sage')];
      if ($hasDesk) {
          $wooPills[] = ['woo-desk-next-h', __('Next', 'sage')];
      }
  } elseif ($isAccount) {
      $wooPills[] = ['woo-page-main', __('Account', 'sage')];
      if ($isLoggedIn) {
          $wooPills[] = ['woo-account-jump-h', __('Shortcuts', 'sage')];
      } else {
          $wooPills[] = ['woo-login-next-h', __('Next', 'sage')];
      }
  }
@endphp
@include('partials.page-nav', ['pills' => $wooPills])

{{-- Main WC content --}}
<div id="woo-page-main" class="container wide page-block woocommerce-wrap{{ $isCheckout ? ' woocommerce-wrap--checkout' : '' }}{{ $isAccount ? ' woocommerce-wrap--account' : '' }}{{ $isCart ? ' woocommerce-wrap--cart' : '' }}{{ $hasDesk ? ' woocommerce-wrap--desk' : '' }}">

  @if ($shortcode !== '')
    {!! do_shortcode($shortcode) !!}

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

    @if ($isCart && $cartCount === 0)
      @if ($startHere !== [])
        <div id="woo-start-here" class="woo-start-here" aria-label="{{ __('Start here', 'sage') }}">
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

@endsection
