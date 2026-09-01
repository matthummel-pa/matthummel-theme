{{--
  Template Name: WooCommerce
  Cart, Checkout, and My account — classic shortcodes, not WooCommerce blocks.
--}}
@extends('layouts.app')

@php
  $title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $shortcode = \App\mh_woocommerce_page_shortcode();
  if ($shortcode === '') {
    $shortcode = trim((string) get_post_field('post_content', get_the_ID()));
  }
  $shopUrl = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');
  $catalogUrl = \App\mh_theme_catalog_url();
  $isCart = function_exists('is_cart') && is_cart();
  $isCheckout = function_exists('is_checkout') && is_checkout();
  $isAccount = function_exists('is_account_page') && is_account_page();
  $cartCount = function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
  $eyebrow = __('Shop', 'sage');
  $panelTitle = __('Secure checkout', 'sage');
  $panelMeta = __('Digital WordPress themes', 'sage');
  $panelStats = [
    ['value' => __('SSL', 'sage'), 'label' => __('Encrypted checkout', 'sage')],
    ['value' => __('Email', 'sage'), 'label' => __('Delivery after payment', 'sage')],
    ['value' => __('Support', 'sage'), 'label' => __('Questions welcome', 'sage')],
    ['value' => __('Work', 'sage'), 'label' => __('Concept pages first', 'sage')],
  ];
  $panelLink = ['label' => __('Browse work', 'sage'), 'href' => $catalogUrl];
  if ($isCart) {
    $eyebrow = __('Cart', 'sage');
    $panelTitle = __('Your cart', 'sage');
    $panelMeta = __('Review before checkout', 'sage');
    $panelStats = [
      ['value' => number_format_i18n($cartCount), 'label' => __('Items in cart', 'sage')],
      ['value' => __('Edit', 'sage'), 'label' => __('Quantities below', 'sage')],
      ['value' => __('Digital', 'sage'), 'label' => __('No shipping', 'sage')],
      ['value' => __('Help', 'sage'), 'label' => __('Say hello anytime', 'sage')],
    ];
    $panelLink = ['label' => __('Continue shopping', 'sage'), 'href' => $shopUrl];
  } elseif ($isCheckout) {
    $eyebrow = __('Checkout', 'sage');
    $panelTitle = __('Almost done', 'sage');
    $panelMeta = __('Secure payment', 'sage');
    $panelStats = [
      ['value' => __('Secure', 'sage'), 'label' => __('Encrypted fields', 'sage')],
      ['value' => __('Digital', 'sage'), 'label' => __('Instant access', 'sage')],
      ['value' => __('Email', 'sage'), 'label' => __('Receipt + files', 'sage')],
      ['value' => __('Questions', 'sage'), 'label' => __('Contact me', 'sage')],
    ];
    $panelLink = ['label' => __('Back to cart', 'sage'), 'href' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')];
  } elseif ($isAccount) {
    $eyebrow = __('Account', 'sage');
    $panelTitle = __('Your orders', 'sage');
    $panelMeta = __('Downloads & history', 'sage');
    $panelStats = [
      ['value' => __('Orders', 'sage'), 'label' => __('Purchase history', 'sage')],
      ['value' => __('Files', 'sage'), 'label' => __('Theme downloads', 'sage')],
      ['value' => __('Profile', 'sage'), 'label' => __('Billing details', 'sage')],
      ['value' => __('Help', 'sage'), 'label' => __('Say hello', 'sage')],
    ];
    $panelLink = ['label' => __('Open shop', 'sage'), 'href' => $shopUrl];
  }
  $lead = '';
  if ($isCart) {
    $lead = __('Review your themes, update quantities, then continue to checkout.', 'sage');
  } elseif ($isCheckout) {
    $lead = __('Secure checkout for digital themes. Access details arrive by email after payment.', 'sage');
  } elseif ($isAccount) {
    $lead = __('Orders, downloads, and account details in one place.', 'sage');
  }
  $crumbItems = [
    ['label' => __('Home', 'sage'), 'url' => home_url('/')],
    ['label' => __('Shop', 'sage'), 'url' => $shopUrl],
    ['label' => $title, 'current' => true],
  ];
  $heroExtra = 'page-header--woo';
  if ($isCart) {
    $heroExtra .= ' page-header--cart';
  } elseif ($isCheckout) {
    $heroExtra .= ' page-header--checkout';
  } elseif ($isAccount) {
    $heroExtra .= ' page-header--account';
  }
@endphp

@section('content')
  @component('partials.page-hero', ['extra' => $heroExtra, 'split' => true, 'asideLabel' => __('Shop details', 'sage')])
    @include('partials.woocommerce-crumb', ['items' => $crumbItems])
    <p class="eyebrow">{{ $eyebrow }}</p>
    <h1 class="display-title is-hero">{{ $title }}</h1>
    @if ($lead !== '')
      <p class="lead">{{ $lead }}</p>
    @endif
    @slot('aside')
      @include('partials.hero-panel', [
        'chrome' => 'matthummel.com'.($isCart ? '/cart' : ($isCheckout ? '/checkout' : ($isAccount ? '/account' : '/shop'))),
        'icon' => $isAccount ? 'user' : 'briefcase',
        'title' => $panelTitle,
        'meta' => $panelMeta,
        'stats' => $panelStats,
        'link' => $panelLink,
      ])
    @endslot
  @endcomponent

  <div class="container wide page-block woocommerce-wrap woo-checkout-shell{{ $isCheckout ? ' woocommerce-wrap--checkout' : '' }}{{ $isAccount ? ' woocommerce-wrap--account' : '' }}{{ $isCart ? ' woocommerce-wrap--cart' : '' }}">
    @if ($shortcode !== '')
      {!! do_shortcode($shortcode) !!}
      @if ($isCheckout && function_exists('WC') && WC()->cart && WC()->cart->is_empty())
        <div class="woo-empty" role="status">
          <p class="woo-empty__title">{{ __('Your cart is empty', 'sage') }}</p>
          <p class="woo-empty__text">{{ __('Add a theme from the shop or work grid, then come back to checkout.', 'sage') }}</p>
          <p class="woo-empty__actions">
            <a class="btn" href="{{ esc_url($catalogUrl) }}">{{ __('Browse work', 'sage') }}</a>
            <a class="btn btn-outline" href="{{ esc_url($shopUrl) }}">{{ __('Open shop', 'sage') }}</a>
          </p>
        </div>
      @endif
    @else
      <div class="woo-empty" role="status">
        <p class="woo-empty__title">{{ __('Shop tools are offline', 'sage') }}</p>
        <p class="woo-empty__text">{{ __('WooCommerce is not active, so this page has nothing to show yet.', 'sage') }}</p>
        <p class="woo-empty__actions">
          <a class="btn" href="{{ esc_url($catalogUrl) }}">{{ __('Browse work', 'sage') }}</a>
        </p>
      </div>
    @endif
  </div>
@endsection
