{{--
  Template Name: Cobble & Candle landing page
  A stand-alone marketing page for the Cobble & Candle theme (built for ad traffic). It has no matthummel.com header or footer:
  the theme's own brand, colours and fonts only. The page copy is block content (core blocks, seeded from resources/data/cc-landing.html and
  edited in the block editor); the plans and prices come from the product catalog through the matthummel/cc-pricing block.
--}}
@extends('layouts.landing')

@php
  $slug = 'cobbleandcandle';
  $p = \App\mh_product_catalog_seed_data()[$slug] ?? [];
  $fs = \App\mh_project_freemius($slug);
  $logo = static fn (string $f): string => esc_url(\App\mh_theme_image_uri('brand/cobble-candle/'.$f));
  $shots = (array) ($p['screenshots'] ?? []);
  $image = esc_url(\App\mh_product_image_url((string) ($shots[0][0] ?? '')));

  $tiers = $fs['tiers'] ?? [];
  $lowest = $tiers[0]['price'] ?? '';
  $highest = $tiers ? $tiers[count($tiers) - 1]['price'] : '';
  $amount = static fn (string $s): string => number_format((float) preg_replace('/[^0-9.]/', '', $s), 2, '.', '');
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
      <a href="#look">{{ __('Look', 'sage') }}</a>
      <a href="#settings">{{ __('Settings', 'sage') }}</a>
      <a href="#pricing">{{ __('Pricing', 'sage') }}</a>
      <a href="#faq">{{ __('FAQ', 'sage') }}</a>
    </nav>
    <a class="cc-btn" href="#pricing" data-cc-track="header_see_plans">{{ __('See plans', 'sage') }}</a>
  </div>
</header>

<main id="main" tabindex="-1">
  @while (have_posts())
    @php(the_post())
    {{-- The page copy is block content (core blocks), edited in the block editor. Pricing is the matthummel/cc-pricing block. --}}
    @php(the_content())
  @endwhile
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
      @if (\App\mh_gtm_container_id() !== '')
        <button type="button" class="cc-linkbtn" data-consent-open>{{ __('Cookie choices', 'sage') }}</button>
      @endif
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
    'image' => $image,
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
