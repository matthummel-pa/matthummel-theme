{{--
  Template Name: Projects (redirects to Shop)

  The project listing has moved to the WooCommerce shop archive (/shop/).
  This template issues a 301 so any bookmarked or indexed /projects/ URL
  lands on the new page. The WordPress page itself can be deleted in wp-admin
  once Google has indexed the redirect.
--}}
@php
  $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
  if (! headers_sent()) {
      header('Location: '.esc_url_raw($shopUrl), true, 301);
  }
  exit;
@endphp
