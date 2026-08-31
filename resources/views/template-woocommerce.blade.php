{{--
  Template Name: WooCommerce
  Cart, Checkout, and My account — classic shortcodes, not WooCommerce blocks.
--}}
@extends('layouts.app')

@php
  $title = get_the_title();
  $shortcode = \App\mh_woocommerce_page_shortcode();
  if ($shortcode === '') {
    $shortcode = trim((string) get_post_field('post_content', get_the_ID()));
  }
@endphp

@section('content')
  @component('partials.page-hero')
    <p class="eyebrow">
      <a class="concept-crumb" href="{{ esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')) }}">{{ __('Shop', 'sage') }}</a>
      <span aria-hidden="true"> / </span>
      {{ $title }}
    </p>
    <h1 class="display-title is-hero">{{ $title }}</h1>
  @endcomponent

  <div class="container wide page-block woocommerce-wrap">
    @if ($shortcode !== '')
      {!! do_shortcode($shortcode) !!}
      @if (function_exists('is_checkout') && is_checkout() && function_exists('WC') && WC()->cart && WC()->cart->is_empty())
        <p class="woocommerce-info">{{ __('Checkout is empty until something is in the cart.', 'sage') }} <a href="{{ esc_url(wc_get_page_permalink('shop')) }}">{{ __('Return to shop', 'sage') }}</a></p>
      @endif
    @else
      <p>{{ __('WooCommerce is not active, so this page has nothing to show yet.', 'sage') }}</p>
    @endif
  </div>
@endsection
