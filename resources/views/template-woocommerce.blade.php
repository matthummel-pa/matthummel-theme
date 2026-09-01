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
  $eyebrow = __('Shop', 'sage');
  if ($isCart) {
    $eyebrow = __('Cart', 'sage');
  } elseif ($isCheckout) {
    $eyebrow = __('Checkout', 'sage');
  } elseif ($isAccount) {
    $eyebrow = __('Account', 'sage');
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
  @component('partials.page-hero', ['extra' => $heroExtra])
    @include('partials.woocommerce-crumb', ['items' => $crumbItems])
    <p class="eyebrow">{{ $eyebrow }}</p>
    <h1 class="display-title is-hero">{{ $title }}</h1>
    @if ($lead !== '')
      <p class="lead">{{ $lead }}</p>
    @endif
  @endcomponent

  <div class="container wide page-block woocommerce-wrap{{ $isCheckout ? ' woocommerce-wrap--checkout' : '' }}{{ $isAccount ? ' woocommerce-wrap--account' : '' }}{{ $isCart ? ' woocommerce-wrap--cart' : '' }}">
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
