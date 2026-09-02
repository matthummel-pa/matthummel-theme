{{--
  Product archives, including the Shop page.

  @see https://woocommerce.com/document/template-structure/
  @version 3.4.0
--}}
@extends('layouts.app')

@section('content')
  @php
    do_action('get_header', 'shop');
    $isShop = function_exists('is_shop') && is_shop();
    $archiveTitle = apply_filters('woocommerce_show_page_title', true)
      ? html_entity_decode((string) woocommerce_page_title(false), ENT_QUOTES | ENT_HTML5, 'UTF-8')
      : __('Shop', 'sage');
    $catalogUrl = \App\mh_theme_catalog_url();
    $shopUrl = function_exists('wc_get_page_permalink')
      ? wc_get_page_permalink('shop')
      : home_url('/shop/');
    $productCount = 0;
    if (function_exists('wc_get_products')) {
        $productCount = count(wc_get_products([
            'limit' => -1,
            'status' => 'publish',
            'return' => 'ids',
        ]));
    }
    $crumbItems = [
      ['label' => __('Home', 'sage'), 'url' => home_url('/')],
      ['label' => __('Projects', 'sage'), 'url' => $catalogUrl],
    ];
    if ($isShop) {
      $crumbItems[] = ['label' => $archiveTitle, 'current' => true];
    } else {
      $crumbItems[] = ['label' => __('Shop', 'sage'), 'url' => $shopUrl];
      $crumbItems[] = ['label' => $archiveTitle, 'current' => true];
    }
  @endphp

  @component('partials.page-hero', ['extra' => 'page-header--shop', 'split' => true, 'asideLabel' => __('Shop snapshot', 'sage')])
    @include('partials.woocommerce-crumb', ['items' => $crumbItems])
    <p class="eyebrow">{{ __('Themes & plugins', 'sage') }}</p>
    @if (apply_filters('woocommerce_show_page_title', true))
      <h1 class="display-title is-hero woocommerce-products-header__title">{{ $archiveTitle }}</h1>
    @endif
    <div class="lead woocommerce-archive-desc">
      @php
        ob_start();
        do_action('woocommerce_archive_description');
        $archiveDesc = trim(ob_get_clean());
        echo $archiveDesc; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      @endphp
      @if ($archiveDesc === '' && $isShop)
        <p>{{ __('WordPress themes and plugins ready for checkout. Browse demos and product details on Projects, then buy the pack here — or hire me for a custom build.', 'sage') }}</p>
      @endif
    </div>
    <div class="page-header-split__actions">
      <a class="btn" href="{{ esc_url($catalogUrl) }}">{{ __('Browse catalog', 'sage') }}</a>
      <a class="h-text-arrow" href="{{ esc_url(home_url('/contact/')) }}">
        {{ __('Say hello', 'sage') }} <span aria-hidden="true">→</span>
      </a>
    </div>
    @slot('aside')
      @include('partials.hero-panel', [
        'chrome' => 'matthummel.com/shop',
        'icon' => 'briefcase',
        'title' => __('Themes & plugins', 'sage'),
        'meta' => __('Checkout for Projects packs', 'sage'),
        'stats' => [
          ['value' => number_format_i18n($productCount), 'label' => __('Products listed', 'sage')],
          ['value' => __('Instant', 'sage'), 'label' => __('Digital delivery', 'sage')],
          ['value' => 'Sage', 'label' => __('Tailwind · Vite', 'sage')],
          ['value' => __('Support', 'sage'), 'label' => __('Say hello anytime', 'sage')],
        ],
        'link' => [
          'label' => __('See demos on Projects', 'sage'),
          'href' => $catalogUrl,
        ],
      ])
    @endslot
  @endcomponent

  <div class="container wide woo-catalog-shell">
    @php
      do_action('woocommerce_before_main_content');
    @endphp

    @if (woocommerce_product_loop())
      @php
        do_action('woocommerce_before_shop_loop');
        woocommerce_product_loop_start();
      @endphp

      @if (wc_get_loop_prop('total'))
        @while (have_posts())
          @php
            the_post();
            do_action('woocommerce_shop_loop');
            wc_get_template_part('content', 'product');
          @endphp
        @endwhile
      @endif

      @php
        woocommerce_product_loop_end();
        do_action('woocommerce_after_shop_loop');
      @endphp
    @else
      <div class="woo-empty" role="status">
        <p class="woo-empty__title">{{ __('No products yet', 'sage') }}</p>
        <p class="woo-empty__text">{{ __('Themes and plugins sync here when they are marked for sale. Browse the Projects catalog in the meantime, or say hello about a build.', 'sage') }}</p>
        <p class="woo-empty__actions">
          <a class="btn" href="{{ esc_url($catalogUrl) }}">{{ __('Browse catalog', 'sage') }}</a>
          <a class="btn btn-outline" href="{{ esc_url(home_url('/contact/')) }}">{{ __('Say hello', 'sage') }}</a>
        </p>
      </div>
    @endif

    @php
      do_action('woocommerce_after_main_content');
      do_action('get_footer', 'shop');
    @endphp
  </div>
@endsection
