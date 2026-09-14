{{--
  My Account desk: main content left, sticky nav + tools right (post layout).
--}}
@php
  defined('ABSPATH') || exit;
@endphp

<div class="woo-desk woo-desk--account">
  <div class="woo-desk__layout">
    <div class="woo-desk__main">
      <div class="woocommerce-MyAccount-content">
        @php
          /**
           * My Account content.
           *
           * @since 2.6.0
           */
          do_action('woocommerce_account_content');
        @endphp
      </div>
    </div>

    <aside class="woo-desk__aside" aria-label="{{ esc_attr__('Account tools', 'sage') }}">
      <nav class="woocommerce-MyAccount-navigation side-card woo-account-nav" aria-label="{{ esc_attr__('Account pages', 'sage') }}">
        <p class="side-card-title">{{ __('Account', 'sage') }}</p>
        <ul>
          @foreach (wc_get_account_menu_items() as $endpoint => $label)
            <li class="{{ wc_get_account_menu_item_classes($endpoint) }}">
              <a href="{{ esc_url(wc_get_account_endpoint_url($endpoint)) }}">{{ esc_html($label) }}</a>
            </li>
          @endforeach
        </ul>
      </nav>

      @include('partials.woo-account-aside')
    </aside>
  </div>
</div>
