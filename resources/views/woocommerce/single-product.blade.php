{{--
  Single product — rich landing page driven by product-catalog.json.

  @see https://woocommerce.com/document/template-structure/
  @version 3.2.0
--}}
@extends('layouts.app')

@section('content')
@php
  do_action('get_header', 'shop');

  $productId    = (int) get_the_ID();
  $productTitle = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $shopUrl      = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
  $projectsUrl  = \App\mh_theme_catalog_url();

  $wcProduct    = null;
  $projectId    = 0;
  $buyUrl       = '';
  $priceHtml    = '';
  $isFree       = false;

  if (function_exists('wc_get_product')) {
    $wcProduct = wc_get_product($productId);
    if ($wcProduct) {
      $projectId = (int) $wcProduct->get_meta('_mh_product_project_id');
      $priceHtml = $wcProduct->get_price_html();
      $isFree    = (float) $wcProduct->get_price() <= 0.0;
    }
  }

  // Load catalog data for rich content + SEO.
  $entry        = \App\mh_product_catalog_data($productId);
  $eyebrow      = trim((string) ($entry['eyebrow'] ?? ''));
  $summary      = trim((string) ($entry['summary'] ?? ''));
  $blurb        = trim((string) ($entry['blurb'] ?? ''));
  $challenge    = trim((string) ($entry['challenge'] ?? ''));
  $approach     = trim((string) ($entry['approach'] ?? ''));
  $result       = trim((string) ($entry['result'] ?? ''));
  $audience     = trim((string) ($entry['audience'] ?? ''));
  $architecture = trim((string) ($entry['architecture'] ?? ''));
  $handoff      = trim((string) ($entry['handoff'] ?? ''));
  $benefits     = is_array($entry['benefits'] ?? null) ? $entry['benefits'] : [];
  $deliverables = is_array($entry['deliverables'] ?? null) ? $entry['deliverables'] : [];
  $metrics      = is_array($entry['metrics'] ?? null) ? $entry['metrics'] : [];
  $faq          = is_array($entry['faq'] ?? null) ? $entry['faq'] : [];
  $screenshots  = is_array($entry['screenshots'] ?? null) ? $entry['screenshots'] : [];
  $productType  = (string) ($entry['product_type'] ?? 'theme');
  $demoUrl      = trim((string) ($entry['demo'] ?? ''));
  $githubUrl    = trim((string) ($entry['github'] ?? ''));
  $docs         = is_array($entry['docs'] ?? null) ? $entry['docs'] : [];
  $filesIncl    = is_array($entry['files_included'] ?? null) ? $entry['files_included'] : [];
  $tagline      = trim((string) ($entry['brand_tagline'] ?? ''));
  $version      = trim((string) ($entry['version'] ?? ''));
  $compatible   = trim((string) ($entry['compatible'] ?? ''));
  $license      = trim((string) ($entry['license'] ?? ''));
  $tech         = is_array($entry['tech'] ?? null) ? $entry['tech'] : [];

  $isTheme  = $productType === 'theme';
  $isPlugin = $productType === 'plugin';

  if ($eyebrow === '') {
    $eyebrow = $isPlugin ? __('WordPress plugin', 'sage') : __('WordPress theme', 'sage');
  }

  // Fallback: pull product payload for buy URL.
  $productPayload = \App\mh_shop_product_payload($productId);
  if (is_array($productPayload)) {
    $buyUrl  = (string) ($productPayload['add_to_cart_url'] ?? '');
    $isFree  = (bool) ($productPayload['is_free'] ?? false);
    if ($priceHtml === '') {
      $priceHtml = (string) ($productPayload['price_html'] ?? '');
    }
  }

  $helpUrl = home_url('/contact/');
  if ($projectId > 0) {
    $projectPost = get_post($projectId);
    if ($projectPost instanceof \WP_Post) {
      $helpUrl = \App\mh_work_help_url(\App\mh_project_post_to_card($projectPost));
    }
  }

  $primaryLabel = $isFree
    ? ($isPlugin ? __('Download the plugin', 'sage') : __('Get the theme', 'sage'))
    : ($isPlugin ? __('Buy plugin', 'sage')           : __('Buy theme', 'sage'));

  $crumbItems = [
    ['label' => __('Home', 'sage'),     'url' => home_url('/')],
    ['label' => __('Projects', 'sage'), 'url' => $projectsUrl],
    ['label' => $productTitle, 'current' => true],
  ];

  // FAQ JSON-LD for Rank Math / Google rich results.
  $faqSchema = array_map(
    fn ($f) => [
      '@type' => 'Question',
      'name'  => (string) ($f[0] ?? ''),
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) ($f[1] ?? '')],
    ],
    $faq
  );
  $faqJsonLd = $faqSchema !== []
    ? json_encode(
        ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqSchema],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
      )
    : '';

  // SoftwareApplication schema for product pages (Rank Math Product tab).
  $productSchema = [
    '@context' => 'https://schema.org',
    '@type'    => 'SoftwareApplication',
    'name'     => $productTitle,
    'applicationCategory' => 'WebApplication',
    'operatingSystem' => 'WordPress',
    'description' => $blurb ?: $summary,
  ];
  if ($version !== '') {
    $productSchema['softwareVersion'] = $version;
  }
  if ($demoUrl !== '') {
    $productSchema['url'] = $demoUrl;
  }
  if ($license !== '') {
    $productSchema['license'] = $license;
  }
  if ($priceHtml !== '') {
    $plainPrice = html_entity_decode(wp_strip_all_tags($priceHtml), ENT_QUOTES, 'UTF-8');
    $productSchema['offers'] = [
      '@type'         => 'Offer',
      'priceCurrency' => 'USD',
      'price'         => $isFree ? '0' : preg_replace('/[^0-9.]/', '', $plainPrice),
      'availability'  => 'https://schema.org/InStock',
    ];
  }
  $productJsonLd = json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp

{{-- Schema JSON-LD (before any HTML output) --}}
@if ($faqJsonLd !== '')
  <script type="application/ld+json">{!! $faqJsonLd !!}</script>
@endif
@if ($productJsonLd !== '')
  <script type="application/ld+json">{!! $productJsonLd !!}</script>
@endif

{{-- HERO --}}
@component('partials.page-hero', ['split' => true, 'asideLabel' => __('Product details', 'sage'), 'extra' => 'page-header--product'])
  @include('partials.woocommerce-crumb', ['items' => $crumbItems])
  <p class="eyebrow">{{ $eyebrow }}</p>
  <h1 class="display-title is-hero">{{ $productTitle }}</h1>
  @if ($tagline !== '')
    <p class="product-tagline">{{ $tagline }}</p>
  @endif
  <p class="lead">{{ $summary ?: $blurb }}</p>

  <div class="page-header-split__actions">
    @if ($buyUrl !== '')
      <a class="btn" href="{{ esc_url($buyUrl) }}">
        {!! \App\mh_svg_icon($isPlugin ? 'download' : 'cart', 16) !!}
        {{ $primaryLabel }}@if ($priceHtml !== '') &nbsp;<span class="btn-price">{!! wp_kses_post($priceHtml) !!}</span>@endif
      </a>
    @endif
    <a class="btn btn-outline" href="{{ esc_url($helpUrl) }}">
      {!! \App\mh_svg_icon('mail', 16) !!} {{ __('Get help', 'sage') }}
    </a>
    @if ($demoUrl !== '')
      <a class="h-text-arrow" href="{{ esc_url($demoUrl) }}" target="_blank" rel="noopener">
        {{ __('View demo', 'sage') }} <span aria-hidden="true">→</span>
      </a>
    @endif
  </div>

  @slot('aside')
    @php
      $panelStats = [];
      foreach ($metrics as $m) {
        $panelStats[] = ['value' => (string) ($m[0] ?? ''), 'label' => (string) ($m[1] ?? '')];
      }
      if ($panelStats === []) {
        $panelStats = [
          ['value' => $isPlugin ? __('Free', 'sage') : '$79', 'label' => $isPlugin ? __('download', 'sage') : __('one-time', 'sage')],
          ['value' => 'GPL', 'label' => __('licensed', 'sage')],
          ['value' => 'WordPress', 'label' => $isPlugin ? __('plugin', 'sage') : __('theme', 'sage')],
        ];
      }
    @endphp
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/shop',
      'icon'   => $isPlugin ? 'plugin' : 'palette',
      'title'  => $productTitle,
      'meta'   => $eyebrow,
      'stats'  => $panelStats,
      'link'   => $demoUrl !== '' ? ['label' => __('Open live demo', 'sage'), 'href' => $demoUrl] : ['label' => __('Browse all products', 'sage'), 'href' => $projectsUrl],
    ])
  @endslot
@endcomponent

{{-- METRICS STRIP --}}
@if ($metrics !== [])
  <div class="container wide">
    <div class="product-metrics">
      @foreach ($metrics as $m)
        <div class="product-metric">
          <span class="product-metric__value">{{ $m[0] ?? '' }}</span>
          <span class="product-metric__label">{{ $m[1] ?? '' }}</span>
        </div>
      @endforeach
    </div>
  </div>
@endif

{{-- SCREENSHOTS --}}
@if ($screenshots !== [])
  <section class="pf-section product-screenshots" aria-labelledby="product-screenshots-heading">
    <div class="container wide">
      <h2 id="product-screenshots-heading" class="display-title is-section">{{ __('Screenshots', 'sage') }}</h2>
      <div class="product-screenshots__grid">
        @foreach ($screenshots as $shot)
          @php
            $src = (string) ($shot[0] ?? '');
            $alt = (string) ($shot[1] ?? '');
            if ($src !== '' && ! str_starts_with($src, 'http')) {
              $src = get_theme_file_uri('resources/images/'.$src);
            }
          @endphp
          @if ($src !== '')
            <figure class="product-screenshot">
              <img
                src="{{ esc_url($src) }}"
                alt="{{ esc_attr($alt) }}"
                width="1200"
                height="750"
                loading="lazy"
                decoding="async"
                class="product-screenshot__img"
              >
              @if ($alt !== '')
                <figcaption class="product-screenshot__cap">{{ $alt }}</figcaption>
              @endif
            </figure>
          @endif
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- BENEFITS --}}
@if ($benefits !== [])
  <section class="pf-section pf-section--alt product-benefits" aria-labelledby="product-benefits-heading">
    <div class="container wide">
      <p class="eyebrow">{{ __('Why it works', 'sage') }}</p>
      <h2 id="product-benefits-heading" class="display-title is-section">
        {{ $isPlugin ? __('Key benefits.', 'sage') : __('What makes it different.', 'sage') }}
      </h2>
      <ul class="product-benefits__list">
        @foreach ($benefits as $b)
          <li class="product-benefits__item">
            <span class="product-benefits__check" aria-hidden="true">{!! \App\mh_svg_icon('check', 16) !!}</span>
            <span>{{ $b }}</span>
          </li>
        @endforeach
      </ul>
    </div>
  </section>
@endif

{{-- PROBLEM / CHALLENGE --}}
@if ($challenge !== '' || $approach !== '')
  <section class="pf-section product-story" aria-labelledby="product-story-heading">
    <div class="container wide product-story__grid">
      @if ($challenge !== '')
        <div class="product-story__col">
          <p class="eyebrow">{{ __('The problem', 'sage') }}</p>
          <h2 id="product-story-heading" class="display-title is-section">
            {{ $isPlugin ? __('The problem it solves.', 'sage') : __('Built for this niche.', 'sage') }}
          </h2>
          <p class="product-story__body">{{ $challenge }}</p>
        </div>
      @endif
      @if ($approach !== '')
        <div class="product-story__col">
          <p class="eyebrow">{{ __('The approach', 'sage') }}</p>
          <h2 class="display-title is-section">
            {{ $isPlugin ? __('How it works.', 'sage') : __('How the theme works.', 'sage') }}
          </h2>
          <p class="product-story__body">{{ $approach }}</p>
        </div>
      @endif
    </div>
  </section>
@endif

{{-- WHAT YOU GET / RESULT --}}
@if ($result !== '')
  <section class="pf-section pf-section--alt product-result" aria-labelledby="product-result-heading">
    <div class="container wide">
      <h2 id="product-result-heading" class="display-title is-section">{{ __('What you get.', 'sage') }}</h2>
      <p class="product-result__body lead">{{ $result }}</p>
    </div>
  </section>
@endif

{{-- DELIVERABLES + INCLUDED --}}
@if ($deliverables !== [] || $filesIncl !== [])
  <section class="pf-section product-included" aria-labelledby="product-included-heading">
    <div class="container wide product-included__grid">
      @if ($deliverables !== [])
        <div>
          <h2 id="product-included-heading" class="display-title is-section">
            {{ $isPlugin ? __("What's included.", 'sage') : __("What's in the theme.", 'sage') }}
          </h2>
          <ul class="product-checklist">
            @foreach ($deliverables as $d)
              <li>{!! \App\mh_svg_icon('check', 14) !!} {{ $d }}</li>
            @endforeach
          </ul>
        </div>
      @endif
      @if ($filesIncl !== [])
        <div>
          <h3 class="product-included__sub">{{ __('Files in the pack', 'sage') }}</h3>
          <ul class="product-file-list">
            @foreach ($filesIncl as $f)
              <li>{!! \App\mh_svg_icon('file', 14) !!} {{ $f }}</li>
            @endforeach
          </ul>
          @if ($version !== '' || $compatible !== '' || $license !== '')
            <dl class="product-meta-list">
              @if ($version !== '')
                <dt>{{ __('Version', 'sage') }}</dt>
                <dd>{{ $version }}</dd>
              @endif
              @if ($compatible !== '')
                <dt>{{ __('Requires', 'sage') }}</dt>
                <dd>{{ $compatible }}</dd>
              @endif
              @if ($license !== '')
                <dt>{{ __('License', 'sage') }}</dt>
                <dd>{{ $license }}</dd>
              @endif
            </dl>
          @endif
        </div>
      @endif
    </div>
  </section>
@endif

{{-- AUDIENCE --}}
@if ($audience !== '')
  <section class="pf-section pf-section--alt product-audience" aria-labelledby="product-audience-heading">
    <div class="container wide">
      <h2 id="product-audience-heading" class="display-title is-section">{{ __('Who it is for.', 'sage') }}</h2>
      <p class="lead">{{ $audience }}</p>
    </div>
  </section>
@endif

{{-- ADD-TO-CART (WooCommerce) --}}
<section class="pf-section product-cart-section" aria-labelledby="product-buy-heading">
  <div class="container wide">
    <div class="product-buy-card">
      <div class="product-buy-card__copy">
        <h2 id="product-buy-heading" class="display-title is-section">
          {{ $isFree ? __('Download for free.', 'sage') : sprintf(__('Get %s.', 'sage'), $productTitle) }}
        </h2>
        <p>
          @if ($isFree)
            {{ __('Free download — install via Appearance → Plugins or copy the zip from GitHub Releases.', 'sage') }}
          @else
            {{ __('Instant digital download after checkout. Need it customized? Get help and I will ship the full build.', 'sage') }}
          @endif
        </p>
      </div>
      <div class="product-buy-card__actions">
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
        @endphp
        @if ($helpUrl !== '')
          <p class="product-buy-card__help">
            <a class="h-text-arrow" href="{{ esc_url($helpUrl) }}">
              {{ __('Or get help →', 'sage') }}
            </a>
          </p>
        @endif
      </div>
    </div>
  </div>
</section>

{{-- ARCHITECTURE / TECHNICAL --}}
@if ($architecture !== '' || $handoff !== '' || $tech !== [])
  <section class="pf-section product-technical" aria-labelledby="product-tech-heading">
    <div class="container wide">
      <p class="eyebrow">{{ __('Technical', 'sage') }}</p>
      <h2 id="product-tech-heading" class="display-title is-section">{{ __('Stack and handoff.', 'sage') }}</h2>
      @if ($tech !== [])
        <div class="product-tech-tags">
          @foreach ($tech as $t)
            <span class="tech-tag">{{ $t }}</span>
          @endforeach
        </div>
      @endif
      @if ($architecture !== '')
        <p class="product-technical__body">{{ $architecture }}</p>
      @endif
      @if ($handoff !== '')
        <h3 class="product-technical__sub">{{ __('Handoff', 'sage') }}</h3>
        <p class="product-technical__body">{{ $handoff }}</p>
      @endif
    </div>
  </section>
@endif

{{-- DOCUMENTATION LINKS --}}
@if ($docs !== [])
  <section class="pf-section pf-section--alt product-docs" aria-labelledby="product-docs-heading">
    <div class="container wide">
      <h2 id="product-docs-heading" class="display-title is-section">{{ __('Documentation.', 'sage') }}</h2>
      <ul class="product-docs-list">
        @foreach ($docs as $doc)
          @php $docLabel = (string) ($doc[0] ?? ''); $docUrl = (string) ($doc[1] ?? ''); @endphp
          @if ($docLabel !== '' && $docUrl !== '')
            <li>
              <a href="{{ esc_url($docUrl) }}" target="_blank" rel="noopener">
                {!! \App\mh_svg_icon('file', 14) !!} {{ $docLabel }} <span aria-hidden="true">↗</span>
              </a>
            </li>
          @endif
        @endforeach
      </ul>
      @if ($githubUrl !== '')
        <p class="product-docs__github">
          <a href="{{ esc_url($githubUrl) }}" target="_blank" rel="noopener">
            {!! \App\mh_svg_icon('github', 16) !!} {{ __('View source on GitHub', 'sage') }}
          </a>
        </p>
      @endif
    </div>
  </section>
@endif

{{-- FAQ --}}
@if ($faq !== [])
  <section class="pf-section product-faq" aria-labelledby="product-faq-heading" id="product-faq">
    <div class="container wide svc-faq-layout">
      <div class="svc-faq-aside">
        <p class="eyebrow">{{ __('Questions', 'sage') }}</p>
        <h2 id="product-faq-heading" class="display-title is-section">
          {{ __('Common questions.', 'sage') }}
        </h2>
        <div class="svc-faq-aside__cta">
          <p>{{ __('Question not here?', 'sage') }}</p>
          <a class="btn btn--sm" href="{{ esc_url($helpUrl) }}">
            {!! \App\mh_svg_icon('mail', 14) !!} {{ __('Ask me directly', 'sage') }}
          </a>
        </div>
      </div>
      <div class="faq-list">
        @foreach ($faq as $i => $f)
          <details {{ $i === 0 ? 'open' : '' }}>
            <summary>{{ $f[0] ?? '' }}</summary>
            <p>{{ $f[1] ?? '' }}</p>
          </details>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- BOTTOM CTA BAND --}}
@include('partials.cta-band', [
  'kicker'        => __('From this product', 'sage'),
  'title'         => $isFree
    ? sprintf(__('Install %s today — it is free.', 'sage'), $productTitle)
    : sprintf(__('Ready for %s?', 'sage'), $productTitle),
  'text'          => $isPlugin
    ? __('Download, activate, done. Or hire me to extend it for your stack.', 'sage')
    : __('Buy the pack for a self-serve install, or hire me to brand it, import your inventory, and hand off wp-admin.', 'sage'),
  'label'         => $primaryLabel,
  'href'          => $buyUrl ?: $helpUrl,
  'secondary'     => __('Browse all products', 'sage'),
  'secondaryHref' => $projectsUrl,
])

@php
  do_action('get_footer', 'shop');
@endphp
@endsection
