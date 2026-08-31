{{--
  Product archives, including the Shop page.

  @see https://woocommerce.com/document/template-structure/
  @version 3.4.0
--}}
@extends('layouts.app')

@section('content')
  @php
    do_action('get_header', 'shop');
  @endphp

  @component('partials.page-hero')
    <p class="eyebrow">{{ __('Shop', 'sage') }}</p>
    @if (apply_filters('woocommerce_show_page_title', true))
      <h1 class="display-title is-hero woocommerce-products-header__title">{!! woocommerce_page_title(false) !!}</h1>
    @endif
    <div class="lead woocommerce-archive-desc">
      @php
        do_action('woocommerce_archive_description');
      @endphp
    </div>
  @endcomponent

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
    @php
      do_action('woocommerce_no_products_found');
    @endphp
  @endif

  @php
    do_action('woocommerce_after_main_content');
    do_action('get_footer', 'shop');
  @endphp
@endsection
