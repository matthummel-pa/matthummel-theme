{{--
  Template Name: Cobble & Candle landing page
  A stand-alone marketing page for the Cobble & Candle theme (built for ad traffic). It has no matthummel.com header or footer:
  the theme's own brand, colours and fonts only. Every claim comes from the product catalog (resources/data/product-catalog.json),
  the theme's BRAND.md (style names and colours) and the Freemius plan, so prices and copy stay in one place.
--}}
@extends('layouts.landing')

@php
  $slug = 'cobbleandcandle';
  $p = \App\mh_product_catalog_seed_data()[$slug] ?? [];
  $fs = \App\mh_project_freemius($slug);
  $logo = static fn (string $f): string => esc_url(\App\mh_theme_image_uri('brand/cobble-candle/'.$f));
  $shots = (array) ($p['screenshots'] ?? []);
  $shot = fn (int $i): string => esc_url(\App\mh_product_image_url((string) ($shots[$i][0] ?? '')));
  $alt = fn (int $i): string => (string) ($shots[$i][1] ?? '');

  $sentences = preg_split('/(?<=[.!?])\s+/u', (string) ($p['summary'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
  $lead = trim(($sentences[1] ?? '').' '.($sentences[2] ?? ''));
  $benefits = array_values((array) ($p['benefits'] ?? []));
  $faq = (array) ($p['faq'] ?? []);
  $demo = (string) ($p['demo'] ?? 'https://cobbleandcandle.matthummel.com/');

  $tiers = $fs['tiers'] ?? [];
  $lowest = $tiers[0]['price'] ?? '';
  $highest = $tiers ? $tiers[count($tiers) - 1]['price'] : '';
  $amount = static fn (string $s): string => number_format((float) preg_replace('/[^0-9.]/', '', $s), 2, '.', '');

  // Style names and colours: BRAND.md in the theme repository.
  $styles = [
    ['Lampwright', 'Dark and candlelit', ['#15110D', '#C79A55']],
    ['Ember & Arch', 'Warm and rustic', ['#1F1410', '#E2692A']],
    ['Ashlar & Iron', 'Stone and oxblood', ['#EFE6D6', '#7A2A2E']],
    ['Daylight', 'A bright café style', ['#FBF7F0', '#B14E25', '#4E6644']],
  ];
  $cards = [
    [0, 'Live opening hours'],
    [1, 'Menus guests can filter'],
    [2, 'Table bookings, three ways'],
    [3, 'Rooms with a calendar'],
    [4, 'Calendar sync'],
    [6, 'Set up in five steps'],
  ];
  $usedBenefits = array_column($cards, 0);
  $moreBenefits = array_values(array_diff_key($benefits, array_flip($usedBenefits)));
  $tierNotes = [
    1 => __('Activate on 1 site', 'sage'),
    5 => __('Activate on up to 5 sites', 'sage'),
    0 => __('Activate on unlimited sites, white-label', 'sage'),
  ];
  $links = [
    [__('License', 'sage'), home_url('/license/')],
    [__('Refunds', 'sage'), home_url('/refunds/')],
    [__('Terms', 'sage'), home_url('/terms/')],
    [__('Privacy', 'sage'), \App\mh_privacy_policy_url()],
    [__('Owner guide', 'sage'), 'https://github.com/matthummel-pa/wp-cobbleandcandle-docs'],
  ];
@endphp

@section('content')
<header class="cc-header">
  <div class="cc-wrap cc-header__in">
    <a class="cc-logo" href="#top" aria-label="{{ esc_attr__('Cobble & Candle, back to the top', 'sage') }}">
      <img src="{{ $logo('cobble-candle-logo-horizontal-dark.svg') }}" width="640" height="120" alt="Cobble &amp; Candle: Restaurant · Tavern · Inn">
    </a>
    <nav class="cc-nav" aria-label="{{ esc_attr__('Page sections', 'sage') }}">
      <a href="#features">{{ __('Features', 'sage') }}</a>
      <a href="#screens">{{ __('Screens', 'sage') }}</a>
      <a href="#styles">{{ __('Styles', 'sage') }}</a>
      <a href="#pricing">{{ __('Pricing', 'sage') }}</a>
      <a href="#faq">{{ __('FAQ', 'sage') }}</a>
    </nav>
    <a class="cc-btn" href="#pricing" data-cc-track="header_see_plans">{{ __('See plans', 'sage') }}</a>
  </div>
</header>

<main id="main" tabindex="-1">
  <section class="cc-hero" id="top" aria-labelledby="cc-h1">
    <div class="cc-wrap cc-hero__grid">
      <div>
        <p class="cc-eyebrow">{{ __('WordPress block theme · Restaurant · Tavern · Inn', 'sage') }}</p>
        <h1 id="cc-h1">{{ __('A WordPress theme for restaurants, taverns and', 'sage') }} <em>{{ __('inns', 'sage') }}</em></h1>
        <p class="cc-hero__lead">{{ $lead }}</p>
        @if ($lowest !== '')
          <p class="cc-hero__price"><strong>{{ $lowest }}</strong> <span>{{ __('a year, for 1 site. Plans for 5 sites and unlimited sites.', 'sage') }}</span></p>
        @endif
        <div class="cc-actions">
          <a class="cc-btn" href="#pricing" data-cc-track="hero_see_plans">{{ __('See plans and pricing', 'sage') }}</a>
          <a class="cc-btn cc-btn--ghost" href="{{ esc_url($demo) }}" rel="noopener" target="_blank" data-cc-track="hero_live_demo">{{ __('Try the live demo', 'sage') }}<span class="cc-vh"> {{ __('(opens in a new window)', 'sage') }}</span></a>
        </div>
        <ul class="cc-proof">
          @foreach ((array) ($p['metrics'] ?? []) as $m)
            <li>{{ $m[0] }} {{ $m[1] }}</li>
          @endforeach
          <li>{{ __('30-day refund', 'sage') }}</li>
        </ul>
      </div>
      <div class="cc-hero__shot">
        <div class="cc-shot">
          <div class="cc-shot__bar" aria-hidden="true"><i></i><i></i><i></i></div>
          <img src="{{ $shot(0) }}" width="1280" height="800" fetchpriority="high" decoding="async" alt="{{ esc_attr($alt(0)) }}">
        </div>
      </div>
    </div>
  </section>

  <section class="cc-strip" aria-label="{{ esc_attr__('Quality', 'sage') }}">
    <div class="cc-wrap">
      <ul>
        @foreach ((array) ($p['quality'] ?? []) as $q)
          <li><strong>{{ $q[0] }}</strong><span>{{ $q[1] }}</span></li>
        @endforeach
      </ul>
    </div>
  </section>

  <section class="cc-section" id="features" aria-labelledby="cc-features-h">
    <div class="cc-wrap">
      <div class="cc-head">
        <p class="cc-eyebrow">{{ __('What it does', 'sage') }}</p>
        <h2 id="cc-features-h">{{ __('Everything a dining room or an inn needs online', 'sage') }}</h2>
        <p>{{ __('Hours, menus, bookings, rooms and events, built into the theme. No page builder, no extra plugins to wire together.', 'sage') }}</p>
      </div>
      <div class="cc-cards">
        @foreach ($cards as $i => [$bi, $title])
          @if (! empty($benefits[$bi]))
            <article class="cc-card">
              <span class="cc-card__n" aria-hidden="true">{{ $i + 1 }}</span>
              <h3>{{ $title }}</h3>
              <p>{{ $benefits[$bi] }}</p>
            </article>
          @endif
        @endforeach
      </div>
      @if ($moreBenefits)
        <ul class="cc-also" aria-label="{{ esc_attr__('And also', 'sage') }}">
          @foreach ($moreBenefits as $b)
            <li>{{ $b }}</li>
          @endforeach
        </ul>
      @endif
    </div>
  </section>

  <section class="cc-section cc-section--smoke" id="screens" aria-labelledby="cc-screens-h">
    <div class="cc-wrap">
      <div class="cc-head">
        <p class="cc-eyebrow">{{ __('See it', 'sage') }}</p>
        <h2 id="cc-screens-h">{{ __('What guests and owners see', 'sage') }}</h2>
        <p>{{ __('Real screens from the theme. The live demo has the full thing to click through.', 'sage') }}</p>
      </div>
      <div class="cc-screens">
        @foreach ([2, 3, 4, 6, 8, 12] as $i)
          @if ($shot($i) !== '')
            <div class="cc-screen">
              <figure>
                <div class="cc-shot"><img src="{{ $shot($i) }}" width="1280" height="800" loading="lazy" decoding="async" alt="{{ esc_attr($alt($i)) }}"></div>
                <figcaption>{{ $alt($i) }}</figcaption>
              </figure>
            </div>
          @endif
        @endforeach
      </div>
      <p class="cc-actions" style="margin-top:2rem"><a class="cc-btn cc-btn--ghost" href="{{ esc_url($demo) }}" rel="noopener" target="_blank" data-cc-track="screens_live_demo">{{ __('Open the live demo', 'sage') }}<span class="cc-vh"> {{ __('(opens in a new window)', 'sage') }}</span></a></p>
    </div>
  </section>

  <section class="cc-section" id="styles" aria-labelledby="cc-styles-h">
    <div class="cc-wrap">
      <div class="cc-head">
        <p class="cc-eyebrow">{{ __('Four styles', 'sage') }}</p>
        <h2 id="cc-styles-h">{{ __('Pick the look that fits the room', 'sage') }}</h2>
        <p>{{ __('Switch between four style directions in the Site Editor. Colours, type and spacing change together.', 'sage') }}</p>
      </div>
      <ul class="cc-styles">
        @foreach ($styles as [$name, $note, $colors])
          <li class="cc-style">
            <span class="cc-style__sw" aria-hidden="true">@foreach ($colors as $c)<i style="background:{{ esc_attr($c) }}"></i>@endforeach</span>
            <div><h3>{{ $name }}</h3><p>{{ $note }}</p></div>
          </li>
        @endforeach
      </ul>
      <div class="cc-shot"><img src="{{ $shot(18) }}" width="1280" height="800" loading="lazy" decoding="async" alt="{{ esc_attr($alt(18)) }}"></div>
    </div>
  </section>

  <section class="cc-section cc-section--smoke" id="setup" aria-labelledby="cc-setup-h">
    <div class="cc-wrap">
      <div class="cc-head">
        <p class="cc-eyebrow">{{ __('Setup', 'sage') }}</p>
        <h2 id="cc-setup-h">{{ __('From zip file to a working site', 'sage') }}</h2>
      </div>
      <ol class="cc-steps">
        <li class="cc-step"><h3>{{ __('Upload and install', 'sage') }}</h3><p>{{ __('Upload the theme, then click once to install the bundled Cobble & Candle Core plugin.', 'sage') }}</p></li>
        <li class="cc-step"><h3>{{ __('Answer the wizard', 'sage') }}</h3><p>{{ __('The setup wizard asks for your name, style, currency and clock, imports the demo or your first location, and builds the pages and menus.', 'sage') }}</p></li>
        <li class="cc-step"><h3>{{ __('Add your hours and menu', 'sage') }}</h3><p>{{ __('Hours and holiday hours are set per location, and menus import from a CSV. A Status page checks the setup and gives a system report for support.', 'sage') }}</p></li>
      </ol>
    </div>
  </section>

  <section class="cc-section" id="who" aria-labelledby="cc-who-h">
    <div class="cc-wrap">
      <div class="cc-head">
        <p class="cc-eyebrow">{{ __('Who it is for', 'sage') }}</p>
        <h2 id="cc-who-h">{{ __('Made for hospitality sites', 'sage') }}</h2>
      </div>
      <div class="cc-who">
        <div class="cc-who__card"><h3>{{ __('Owners', 'sage') }}</h3><p>{{ __('Restaurant, tavern and inn owners who want menus, hours and bookings that stay right without a developer.', 'sage') }}</p></div>
        <div class="cc-who__card"><h3>{{ __('Agencies', 'sage') }}</h3><p>{{ __('Agencies that ship hospitality sites. The Unlimited sites plan is white-label, so you can ship it under your own name.', 'sage') }}</p></div>
      </div>
    </div>
  </section>

  @if ($fs)
    <section class="cc-section cc-section--parch" id="pricing" aria-labelledby="cc-pricing-h"
      data-fs-checkout
      data-product="{{ esc_attr($fs['product_id']) }}"
      data-plan="{{ esc_attr($fs['plan_id']) }}"
      data-key="{{ esc_attr($fs['public_key']) }}"
      data-account="{{ esc_url($fs['account_url']) }}"
      data-title="{{ esc_attr($p['title'] ?? 'Cobble & Candle') }}"
      @if ($fs['sandbox'] !== null)
        data-sandbox-token="{{ esc_attr($fs['sandbox']['token']) }}"
        data-sandbox-ctx="{{ esc_attr($fs['sandbox']['ctx']) }}"
      @endif
    >
      <div class="cc-wrap">
        <div class="cc-head">
          <p class="cc-eyebrow">{{ __('Pricing', 'sage') }}</p>
          <h2 id="cc-pricing-h">{{ __('Pick a plan', 'sage') }}</h2>
          <p>{{ __('Every plan is a yearly license with updates and email support. Pay by card; the receipt and your license key come from Freemius.', 'sage') }}</p>
        </div>
        <div class="cc-tiers">
          @foreach ($tiers as $tier)
            <div class="cc-tier">
              <h3>{{ $tier['label'] }}</h3>
              <p class="cc-tier__price"><strong>{{ $tier['price'] }}</strong> <span>{{ __('per year', 'sage') }}</span></p>
              <ul>
                <li>{{ $tierNotes[(int) $tier['licenses']] ?? '' }}</li>
                <li>{{ __('The theme and the bundled Core plugin', 'sage') }}</li>
                <li>{{ __('Updates and email support for the year', 'sage') }}</li>
                <li>{{ __('Owner guide and support guide', 'sage') }}</li>
              </ul>
              <button type="button" class="cc-btn cc-btn--dark cc-btn--wide" data-fs-buy data-licenses="{{ (int) $tier['licenses'] }}" data-plan-label="{{ esc_attr($tier['label']) }}" data-price="{{ esc_attr($amount($tier['price'])) }}">
                {{ __('Buy', 'sage') }} {{ $tier['label'] }}<span class="cc-vh">, {{ $tier['price'] }} {{ __('per year', 'sage') }}</span>
              </button>
            </div>
          @endforeach
        </div>
        <p class="cc-status" data-fs-status role="status" hidden></p>
        <p class="cc-fine">
          {{ __('Billed once a year. Plans renew each year. If it has a bug or problem I can’t resolve, you get a refund within 30 days.', 'sage') }}
          <a href="{{ esc_url(home_url('/refunds/')) }}">{{ __('Refund policy', 'sage') }}</a> ·
          <a href="{{ esc_url(home_url('/license/')) }}">{{ __('License', 'sage') }}</a> ·
          <a href="{{ esc_url(home_url('/terms/#terms-store-heading')) }}">{{ __('Terms', 'sage') }}</a>.
          {{ __('The code is GPL v2 or later. The key covers updates and support, and never locks your site.', 'sage') }}
        </p>
      </div>
    </section>
  @endif

  <section class="cc-section" id="faq" aria-labelledby="cc-faq-h">
    <div class="cc-wrap">
      <div class="cc-head">
        <p class="cc-eyebrow">{{ __('Questions', 'sage') }}</p>
        <h2 id="cc-faq-h">{{ __('Before you buy', 'sage') }}</h2>
      </div>
      <div class="cc-faq">
        @foreach ($faq as $n => $qa)
          <details @if ($n === 0) open @endif>
            <summary>{{ $qa[0] }}</summary>
            <p>{{ $qa[1] }}</p>
          </details>
        @endforeach
      </div>
    </div>
  </section>

  <section class="cc-band" aria-labelledby="cc-band-h">
    <div class="cc-wrap">
      <h2 id="cc-band-h">{{ __('Ready to build your site?', 'sage') }}</h2>
      <p>{{ __('Look around the live demo, then pick the plan that fits. If it is not right, the 30-day refund covers bugs I can’t resolve.', 'sage') }}</p>
      <div class="cc-actions">
        <a class="cc-btn" href="#pricing" data-cc-track="band_see_plans">{{ __('See plans', 'sage') }}</a>
        <a class="cc-btn cc-btn--ghost" href="{{ esc_url($demo) }}" rel="noopener" target="_blank" data-cc-track="band_live_demo">{{ __('Try the live demo', 'sage') }}<span class="cc-vh"> {{ __('(opens in a new window)', 'sage') }}</span></a>
      </div>
    </div>
  </section>
</main>

@if ($lowest !== '')
  <div class="cc-sticky" data-cc-sticky>
    <p><strong>{{ $lowest }}</strong> <span>{{ __('a year', 'sage') }}</span></p>
    <a class="cc-btn" href="#pricing" data-cc-track="sticky_see_plans">{{ __('See plans', 'sage') }}</a>
  </div>
@endif

<footer class="cc-footer">
  <div class="cc-wrap cc-footer__in">
    <img src="{{ $logo('cobble-candle-icon.svg') }}" width="512" height="512" alt="" loading="lazy">
    <div>
      <p>&copy; {{ date('Y') }} Cobble &amp; Candle. {{ __('Made and supported by Matt Hummel, Gettysburg, Pennsylvania. Payments are processed by Freemius, Inc.', 'sage') }}</p>
      <p>{{ __('Support:', 'sage') }} <a href="mailto:hello@matthummel.com">hello@matthummel.com</a>. {{ __('I reply within one business day, Eastern Time.', 'sage') }}</p>
    </div>
    <nav aria-label="{{ esc_attr__('Legal and help', 'sage') }}">
      @foreach ($links as [$label, $url])
        <a href="{{ esc_url($url) }}">{{ $label }}</a>
      @endforeach
    </nav>
  </div>
</footer>

@if ($lowest !== '')
  <script type="application/ld+json">{!! wp_json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => 'Cobble & Candle',
    'description' => (string) ($p['seo_description'] ?? ''),
    'brand' => ['@type' => 'Brand', 'name' => 'Cobble & Candle'],
    'image' => $shot(0),
    'offers' => [
      '@type' => 'AggregateOffer',
      'priceCurrency' => 'USD',
      'lowPrice' => $amount($lowest),
      'highPrice' => $amount($highest),
      'offerCount' => count($tiers),
      'url' => get_permalink(),
    ],
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endsection
