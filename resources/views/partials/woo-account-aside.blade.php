{{-- Sticky account tools (mirrors post sidebar + cart desk helpers). --}}
@php
  $tools = \App\mh_account_desk_tools();
  $shopUrl = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
  $contactUrl = home_url('/contact/');
  $downloadsUrl = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('downloads') : home_url('/my-account/downloads/');
  $ordersUrl = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('orders') : home_url('/my-account/orders/');
@endphp

<div class="woo-desk-tools woo-account-tools">

  @if (! empty($tools['next']))
    <section class="side-card woo-desk-card" aria-labelledby="woo-account-next-h">
      <h2 id="woo-account-next-h" class="side-card-title">{{ __('On this page', 'sage') }}</h2>
      <ol class="woo-desk-steps">
        @foreach ($tools['next'] as $i => $step)
          <li class="woo-desk-steps__item">
            <span class="woo-desk-steps__num" aria-hidden="true">{{ $i + 1 }}</span>
            <span class="woo-desk-steps__copy">
              <strong>{{ $step['title'] }}</strong>
              <span>{{ $step['text'] }}</span>
            </span>
          </li>
        @endforeach
      </ol>
    </section>
  @endif

  @if (! empty($tools['facts']))
    <section class="side-card woo-desk-card" aria-labelledby="woo-account-facts-h">
      <h2 id="woo-account-facts-h" class="side-card-title">{{ __('Good to know', 'sage') }}</h2>
      <ul class="woo-desk-trust">
        @foreach ($tools['facts'] as $fact)
          <li class="woo-desk-trust__item">
            {!! \App\mh_svg_icon($fact['icon'] ?? 'check', 14) !!}
            <span>{{ $fact['label'] }}</span>
          </li>
        @endforeach
      </ul>
    </section>
  @endif

  @if (! empty($tools['faq']))
    <details class="side-card side-card--fold woo-desk-card">
      <summary class="side-card-title">{{ __('Quick answers', 'sage') }}</summary>
      <dl class="woo-desk-faq">
        @foreach ($tools['faq'] as $row)
          <div class="woo-desk-faq__row">
            <dt>{{ $row['q'] }}</dt>
            <dd>{{ $row['a'] }}</dd>
          </div>
        @endforeach
      </dl>
    </details>
  @endif

  <section class="side-card woo-desk-card" aria-labelledby="woo-account-jump-h">
    <h2 id="woo-account-jump-h" class="side-card-title">{{ __('Shortcuts', 'sage') }}</h2>
    <ul class="woo-account-shortcuts">
      <li><a href="{{ esc_url($downloadsUrl) }}">{!! \App\mh_svg_icon('download', 14) !!} {{ __('Downloads', 'sage') }}</a></li>
      <li><a href="{{ esc_url($ordersUrl) }}">{!! \App\mh_svg_icon('check', 14) !!} {{ __('Orders', 'sage') }}</a></li>
      <li><a href="{{ esc_url($shopUrl) }}">{!! \App\mh_svg_icon('briefcase', 14) !!} {{ __('Browse shop', 'sage') }}</a></li>
    </ul>
  </section>

  <section class="side-card woo-desk-card woo-desk-card--help" aria-labelledby="woo-account-help-h">
    <h2 id="woo-account-help-h" class="side-card-title">{{ __('Need a hand?', 'sage') }}</h2>
    <p class="woo-desk-help__text">{{ __('Install help, a new zip, or a billing question — say hello.', 'sage') }}</p>
    <div class="woo-desk-help__actions">
      <a class="woo-desk-help__btn" href="{{ esc_url($contactUrl) }}">
        {!! \App\mh_svg_icon('mail', 14) !!}
        {{ __('Say hello', 'sage') }}
      </a>
    </div>
  </section>

</div>
