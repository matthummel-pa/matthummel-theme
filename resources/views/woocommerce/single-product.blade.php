{{--
  Single product.

  @see https://woocommerce.com/document/template-structure/
  @version 1.6.4
--}}
@extends('layouts.app')

@section('content')
  @php
    do_action('get_header', 'shop');

    $productTitle = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $shopUrl = function_exists('wc_get_page_permalink')
      ? wc_get_page_permalink('shop')
      : home_url('/shop/');
    $projectId = 0;
    if (function_exists('wc_get_product')) {
      $product = wc_get_product(get_the_ID());
      if ($product) {
        $projectId = (int) $product->get_meta('_mh_product_project_id');
      }
    }
    $crumbItems = [
      ['label' => __('Home', 'sage'), 'url' => home_url('/')],
      ['label' => __('Shop', 'sage'), 'url' => $shopUrl],
      ['label' => $productTitle, 'current' => true],
    ];
  @endphp

  @component('partials.page-hero', ['extra' => 'page-header--product'])
    @include('partials.woocommerce-crumb', ['items' => $crumbItems])
    <p class="eyebrow">{{ __('Theme', 'sage') }}</p>
    <h1 class="display-title is-hero">{{ $productTitle }}</h1>
    @if ($projectId > 0)
      <p class="about-hero-links" style="margin-top:1rem">
        <a href="{{ esc_url(get_permalink($projectId)) }}">{{ __('View project page', 'sage') }}</a>
        <a href="{{ esc_url(\App\mh_theme_catalog_url()) }}">{{ __('All work', 'sage') }}</a>
      </p>
    @endif
  @endcomponent

  @php
    do_action('woocommerce_before_main_content');
  @endphp

  @while (have_posts())
    @php
      the_post();
      wc_get_template_part('content', 'single-product');
    @endphp
  @endwhile

  @php
    do_action('woocommerce_after_main_content');
    do_action('get_footer', 'shop');
  @endphp
@endsection
