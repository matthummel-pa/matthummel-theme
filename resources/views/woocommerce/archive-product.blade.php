{{--
  Product archive — the WooCommerce shop page, serving as the primary product catalog.

  All editable copy lives on the WooCommerce "Shop" page in wp-admin (Pages → Shop →
  Page content (theme)). The fields use the same work_* keys as the old Projects template
  so existing saved values carry forward unchanged.

  @see https://woocommerce.com/document/template-structure/
  @version 4.0.0
--}}
@extends('layouts.app')

@section('content')
@php
  do_action('get_header', 'shop');

  // The WC shop page owns the editable work_* fields from here on.
  $shopPostId = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;

  $isShop       = function_exists('is_shop') && is_shop();
  $archiveTitle = apply_filters('woocommerce_show_page_title', true)
    ? html_entity_decode((string) woocommerce_page_title(false), ENT_QUOTES | ENT_HTML5, 'UTF-8')
    : __('Shop', 'sage');
  $shopUrl      = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');

  $catalog      = \App\mh_shop_listing_snapshot();
  $productCount = $catalog['count'];
  $forSaleCount = $catalog['for_sale'];
  $themeCount   = $catalog['theme'];
  $pluginCount  = $catalog['plugin'];
  $serviceCount = $catalog['service'];

  $fitCards = \App\mh_work_page_fit($shopPostId);
  $howSteps = \App\mh_work_page_how($shopPostId);
  $workFaqs = \App\mh_work_page_faq($shopPostId);

  // FAQ JSON-LD for Rank Math — only output when there are real FAQ items.
  $faqJsonLd = '';
  if ($workFaqs !== []) {
    $faqSchema = array_map(
      fn ($f) => ['@type' => 'Question', 'name' => $f['title'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['text']]],
      $workFaqs
    );
    $faqJsonLd = json_encode(
      ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqSchema],
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
    );
  }

  // ItemList / CollectionPage JSON-LD: gives Google a structured product listing
  // and improves Rank Math's CollectionPage signal on the shop archive.
  $listItems = $catalog['list_items'];
  $collectionJsonLd = json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'CollectionPage',
    'name'            => __('WordPress Themes, Plugins & Web Apps', 'sage'),
    'description'     => __('Ready-to-buy WordPress themes, plugins, and web apps built on Sage 11, Tailwind v4, and Gutenberg. Live demos and instant download.', 'sage'),
    'url'             => $shopUrl,
    'hasPart'         => $listItems,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

  $crumbItems = [
    ['label' => __('Home', 'sage'), 'url' => home_url('/')],
  ];
  if ($isShop) {
    $crumbItems[] = ['label' => $archiveTitle, 'current' => true];
  } else {
    $crumbItems[] = ['label' => __('Shop', 'sage'), 'url' => $shopUrl];
    $crumbItems[] = ['label' => $archiveTitle, 'current' => true];
  }

  // Stack tiles shown in the "Built on modern WordPress" section.
  $stackTiles = [
    ['name' => 'Sage 11',       'desc' => __('Roots Sage — Blade templates, Acorn, and a clean PHP namespace. Not a child theme.', 'sage')],
    ['name' => 'Tailwind v4',   'desc' => __('CSS-native design tokens, container queries, and fluid type — no utility sprawl.', 'sage')],
    ['name' => 'Gutenberg',     'desc' => __('Server-rendered Core blocks with live editor previews. No page builder required.', 'sage')],
    ['name' => 'Vite 8',        'desc' => __('Fast HMR in dev, hashed production bundles, and a zero-commit public/ folder.', 'sage')],
    ['name' => 'WooCommerce',   'desc' => __('Cart and checkout on themes that need it — tours, shops, digital downloads.', 'sage')],
    ['name' => 'GPL licensed',  'desc' => __('GPLv2 or later on every product. You own the code outright after checkout.', 'sage')],
  ];
@endphp

@if ($faqJsonLd !== '')
  <script type="application/ld+json">{!! $faqJsonLd !!}</script>
@endif
<script type="application/ld+json">{!! $collectionJsonLd !!}</script>

{{-- HERO --}}
@component('partials.page-hero', ['extra' => 'page-header--shop', 'split' => true, 'asideLabel' => __('Catalog snapshot', 'sage')])
  @include('partials.woocommerce-crumb', ['items' => $crumbItems])
  <p class="eyebrow">{{ \App\field('work_kicker', __('Digital products', 'sage'), $shopPostId) }}</p>
  @if (apply_filters('woocommerce_show_page_title', true))
    <h1 class="display-title is-hero woocommerce-products-header__title">
      {{ \App\field('work_h1', __('WordPress themes, plugins, and web apps.', 'sage'), $shopPostId) }}
    </h1>
  @endif
  <p class="lead">
    {{ \App\field('work_lede', __('Ready-to-buy digital products built on Sage 11 and Tailwind v4. Live demos, instant download, GPL license. Buy a pack from the shop, or hire me to adapt one for your business.', 'sage'), $shopPostId) }}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="#shop-products">
      {!! \App\mh_svg_icon('briefcase', 16) !!} {{ \App\field('work_hero_cta_primary', __('Browse products', 'sage'), $shopPostId) }}
    </a>
    <a class="h-text-arrow" href="{{ home_url('/contact/') }}">
      {{ __('Say hello', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/shop',
      'icon'   => 'briefcase',
      'title'  => __('Digital products', 'sage'),
      'meta'   => __('Demos, packs, checkout', 'sage'),
      'stats'  => [
        ['value' => number_format_i18n($productCount), 'label' => __('Listed products', 'sage')],
        ['value' => number_format_i18n($forSaleCount), 'label' => __('Ready to buy', 'sage')],
        ['value' => 'GPL',       'label' => __('Open license', 'sage')],
        ['value' => 'Instant',   'label' => __('Download & install', 'sage')],
      ],
      'link' => [
        'label' => __('Browse GitHub portfolio', 'sage'),
        'href'  => home_url('/portfolio/'),
      ],
    ])
  @endslot
@endcomponent

{{-- TRUST STRIP --}}
<div class="shop-trust-strip" aria-label="{{ __('Why buy direct', 'sage') }}">
  <div class="container wide shop-trust-strip__inner">
    <span class="shop-trust-item">
      {!! \App\mh_svg_icon('check', 14) !!} {{ __('GPL license — you own the code', 'sage') }}
    </span>
    <span class="shop-trust-item">
      {!! \App\mh_svg_icon('download', 14) !!} {{ __('Instant digital download', 'sage') }}
    </span>
    <span class="shop-trust-item">
      {!! \App\mh_svg_icon('github', 14) !!} {{ __('Full source on GitHub', 'sage') }}
    </span>
    <span class="shop-trust-item">
      {!! \App\mh_svg_icon('mail', 14) !!} {{ __('Custom builds available', 'sage') }}
    </span>
    <span class="shop-trust-item">
      {!! \App\mh_svg_icon('code', 14) !!} {{ __('Sage 11 + Tailwind v4', 'sage') }}
    </span>
  </div>
</div>

{{-- PRODUCT LOOP — appears first so buyers reach products immediately --}}
<div id="shop-products" class="container wide woo-catalog-shell page-block" data-work-hub>
  @php
    do_action('woocommerce_before_main_content');
  @endphp

  @if (woocommerce_product_loop())
    @php
      $filterAll      = $productCount;
      $filterThemes   = $themeCount;
      $filterPlugins  = $pluginCount;
      $filterServices = $serviceCount;

      // Suppress default WC result-count + ordering dropdowns; we render our own toolbar.
      remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
      remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
    @endphp

    {{-- Catalog toolbar: filter tabs + result count + sort --}}
    <div class="catalog-toolbar" role="region" aria-label="{{ __('Catalog controls', 'sage') }}">

      {{-- Type filter tabs --}}
      <nav class="catalog-filter-nav" aria-label="{{ __('Filter by product type', 'sage') }}">
        <ul class="catalog-filter-nav__links" role="list">
          <li>
            <button type="button" class="catalog-filter-nav__link" data-filter-type="all" aria-pressed="true">
              {{ __('All', 'sage') }}
              @if ($filterAll > 0)
                <span class="catalog-filter-nav__count">{{ $filterAll }}</span>
              @endif
            </button>
          </li>
          @if ($filterThemes > 0)
          <li>
            <button type="button" class="catalog-filter-nav__link" data-filter-type="theme" aria-pressed="false">
              {!! \App\mh_svg_icon('home', 11) !!} {{ __('Themes', 'sage') }}
              <span class="catalog-filter-nav__count">{{ $filterThemes }}</span>
            </button>
          </li>
          @endif
          @if ($filterPlugins > 0)
          <li>
            <button type="button" class="catalog-filter-nav__link" data-filter-type="plugin" aria-pressed="false">
              {!! \App\mh_svg_icon('code', 11) !!} {{ __('Plugins', 'sage') }}
              <span class="catalog-filter-nav__count">{{ $filterPlugins }}</span>
            </button>
          </li>
          @endif
          @if ($filterServices > 0)
          <li>
            <button type="button" class="catalog-filter-nav__link" data-filter-type="service" aria-pressed="false">
              {!! \App\mh_svg_icon('briefcase', 11) !!} {{ __('Services', 'sage') }}
              <span class="catalog-filter-nav__count">{{ $filterServices }}</span>
            </button>
          </li>
          @endif
        </ul>
      </nav>

      {{-- Result count + sort order --}}
      <div class="catalog-toolbar__right">
        <span
          class="catalog-toolbar__count"
          id="catalog-filter-count"
          data-label-one="{{ esc_attr(__('%d product', 'sage')) }}"
          data-label-many="{{ esc_attr(__('%d products', 'sage')) }}"
        >{{ sprintf(_n('%d product', '%d products', $filterAll, 'sage'), $filterAll) }}</span>
        <span id="catalog-filter-live" class="visually-hidden" role="status" aria-live="polite"></span>
        <div class="catalog-toolbar__sort">
          <label class="visually-hidden" for="mh-catalog-orderby">{{ __('Sort products', 'sage') }}</label>
          @php
            do_action('woocommerce_catalog_ordering');
          @endphp
        </div>
      </div>

    </div>

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
    <div class="woo-empty work-empty" role="status">
      <div class="work-empty__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 28) !!}</div>
      <h2 class="work-empty__title">
        {{ \App\field('work_empty_h2', __('Themes and plugins are on the way.', 'sage'), $shopPostId) }}
      </h2>
      <p class="work-empty__text">
        {{ \App\field('work_empty_text', __('I am listing the first packs for sale here. Write and tell me what kind of shop you run, or what plugin you need.', 'sage'), $shopPostId) }}
      </p>
      <div class="work-empty__actions">
        <a class="btn" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 16) !!} {{ \App\field('work_empty_cta', __('Say hello', 'sage'), $shopPostId) }}
        </a>
      </div>
    </div>
  @endif

  @php
    do_action('woocommerce_after_main_content');
  @endphp

  <div class="work-footer-links">
    {!! \App\field_html('work_foot', __('Full GitHub portfolio: <a href="/portfolio/">repos and open-source code</a>. Live demos open from each product page when available.', 'sage'), $shopPostId) !!}
  </div>
</div>

{{-- CONTEXT — what each pack includes and who buys them --}}
<section class="pf-section work-guide" aria-labelledby="shop-context-heading">
  <div class="container wide">
    <h2 id="shop-context-heading" class="display-title is-section">
      {{ \App\field('work_context_h2', __('What you can buy or hire me to build.', 'sage'), $shopPostId) }}
    </h2>
    <div class="work-guide__prose">
      <p>{{ \App\field('work_context_p1', __('Each product ships as a full theme or plugin pack — screenshots, a detailed tech summary, pricing, and a live demo when one exists. Buy the pack for an instant download and self-serve install, or hire me to adapt it for your business.', 'sage'), $shopPostId) }}</p>
      {!! \App\field_html('work_context_p2', __('These are studio builds, not agency client sites. Every product ships GPL-licensed so you own the code outright. If nothing here fits exactly, <a href="/contact/">write and tell me what you need</a>. I build custom from a brief.', 'sage'), $shopPostId) !!}
    </div>
  </div>
</section>

{{-- BUILT ON MODERN WORDPRESS — stack section for SEO and developer credibility --}}
<section class="pf-section pf-section--alt shop-stack-section" aria-labelledby="shop-stack-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('The stack', 'sage') }}</p>
    <h2 id="shop-stack-heading" class="display-title is-section">
      {{ __('Built on modern WordPress.', 'sage') }}
    </h2>
    <p class="lead work-guide__intro">
      {{ __('Every product in this catalog runs on the same production stack I use for client work. No page builders. No bloated frameworks. Clean PHP 8.3, Blade templates, and CSS design tokens.', 'sage') }}
    </p>
    <div class="shop-stack-grid">
      @foreach ($stackTiles as $tile)
        <div class="shop-stack-card">
          <strong class="shop-stack-card__name">{{ $tile['name'] }}</strong>
          <p class="shop-stack-card__desc">{{ $tile['desc'] }}</p>
        </div>
      @endforeach
    </div>
    <p class="work-guide__prose shop-stack-follow">
      {{ __('The same stack powers this site. Source is on GitHub if you want to evaluate the code before you buy.', 'sage') }}
      <a class="h-text-arrow" href="{{ home_url('/portfolio/') }}">{{ __('Browse the portfolio', 'sage') }} <span aria-hidden="true">→</span></a>
    </p>
  </div>
</section>

{{-- WHO THIS IS FOR --}}
<section class="pf-section work-guide" aria-labelledby="shop-fit-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('Browse by role', 'sage') }}</p>
    <h2 id="shop-fit-heading" class="display-title is-section">
      {{ \App\field('work_fit_h2', __('Who this catalog is for.', 'sage'), $shopPostId) }}
    </h2>
    <p class="lead work-guide__intro">
      {{ \App\field('work_fit_intro', __('Shops buying a ready WordPress theme, agencies needing a solid base, developers evaluating plugins, and hiring managers reviewing my public work.', 'sage'), $shopPostId) }}
    </p>
    <div class="svc-audience-grid">
      @foreach ($fitCards as $card)
        <article class="svc-audience-card">
          <div class="svc-audience-card__icon" aria-hidden="true">{!! \App\mh_svg_icon($card['icon'], 20) !!}</div>
          <h3 class="svc-audience-card__title">{{ $card['title'] }}</h3>
          <p class="svc-audience-card__body">{{ $card['body'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- HOW TO BUY --}}
<section class="pf-section pf-section--alt work-guide" aria-labelledby="shop-how-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('From catalog to cart', 'sage') }}</p>
    <h2 id="shop-how-heading" class="display-title is-section">
      {{ \App\field('work_how_h2', __('How to buy or start a build.', 'sage'), $shopPostId) }}
    </h2>
    <p class="lead work-guide__intro">
      {{ \App\field('work_how_intro', __('You do not need the perfect match first. Open a product page, review the demo, buy the pack, or send a short note about what you would change.', 'sage'), $shopPostId) }}
    </p>
    <div class="svc-process">
      @foreach ($howSteps as $step)
        <article class="svc-process__step">
          <div class="svc-process__step-head">
            <span class="svc-process__num" aria-hidden="true">{{ $step['num'] }}</span>
          </div>
          <h3 class="svc-process__title">{{ $step['title'] }}</h3>
          <p class="svc-process__body">{{ $step['body'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- FAQ --}}
@if ($workFaqs !== [])
  <section class="pf-section work-guide" aria-labelledby="shop-faq-heading" id="shop-faq">
    <div class="container wide svc-faq-layout">
      <div class="svc-faq-aside">
        <p class="eyebrow">{{ __('Questions', 'sage') }}</p>
        <h2 id="shop-faq-heading" class="display-title is-section">
          {{ \App\field('work_faq_h2', __('Questions about themes and plugins.', 'sage'), $shopPostId) }}
        </h2>
        <p class="svc-faq-aside__intro">
          {{ \App\field('work_faq_intro', __('Straight answers about buying a pack, licensing, demos, customization, and hiring me for a custom build.', 'sage'), $shopPostId) }}
        </p>
        <div class="svc-faq-aside__cta">
          <p>{{ __('Question not here?', 'sage') }}</p>
          <a class="btn btn--sm" href="{{ home_url('/contact/') }}">
            {!! \App\mh_svg_icon('mail', 14) !!} {{ __('Ask me directly', 'sage') }}
          </a>
        </div>
      </div>
      <div class="faq-list">
        @foreach ($workFaqs as $i => $faq)
          <details>
            <summary>{{ $faq['title'] }}</summary>
            <p>{{ $faq['text'] }}</p>
          </details>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- BOTTOM CTA --}}
@include('partials.cta-band', [
  'kicker'        => __('From this catalog', 'sage'),
  'title'         => __('Buy a product or hire me to build one.', 'sage'),
  'text'          => __('Each product ships as an instant digital download with GPL license. Need something custom built, branded, and handed off? Write and tell me what you need.', 'sage'),
  'label'         => __('Say hello', 'sage'),
  'href'          => home_url('/contact/'),
  'secondary'     => __('GitHub portfolio', 'sage'),
  'secondaryHref' => home_url('/portfolio/'),
])

@php
  do_action('get_footer', 'shop');
@endphp
@endsection
