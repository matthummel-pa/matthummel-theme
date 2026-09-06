{{--
  Product archive — the shop listing page, styled as the primary projects catalog.

  This serves as the main product listings page. WooCommerce products drive the
  catalog; the Projects CPT is no longer the primary source after this change.

  @see https://woocommerce.com/document/template-structure/
  @version 3.2.0
--}}
@extends('layouts.app')

@section('content')
@php
  do_action('get_header', 'shop');

  $isShop     = function_exists('is_shop') && is_shop();
  $archiveTitle = apply_filters('woocommerce_show_page_title', true)
    ? html_entity_decode((string) woocommerce_page_title(false), ENT_QUOTES | ENT_HTML5, 'UTF-8')
    : __('Shop', 'sage');
  $catalogUrl = \App\mh_theme_catalog_url();
  $shopUrl    = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');

  $productCount = 0;
  $forSaleCount = 0;
  if (function_exists('wc_get_products')) {
    $all = wc_get_products(['limit' => -1, 'status' => 'publish', 'return' => 'ids']);
    $productCount = count($all);
    foreach ($all as $pid) {
      $wcp = wc_get_product($pid);
      if ($wcp && $wcp->is_purchasable() && $wcp->is_in_stock()) {
        $forSaleCount++;
      }
    }
  }

  $fitCards = \App\mh_work_page_fit();
  $howSteps = \App\mh_work_page_how();
  $workFaqs = \App\mh_work_page_faq();

  // FAQ JSON-LD for Rank Math.
  $faqSchema = array_map(
    fn ($f) => ['@type' => 'Question', 'name' => $f['title'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['text']]],
    $workFaqs
  );
  $faqJsonLd = json_encode(
    ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqSchema],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
  );

  $crumbItems = [
    ['label' => __('Home', 'sage'), 'url' => home_url('/')],
  ];
  if ($isShop) {
    $crumbItems[] = ['label' => $archiveTitle, 'current' => true];
  } else {
    $crumbItems[] = ['label' => __('Shop', 'sage'), 'url' => $shopUrl];
    $crumbItems[] = ['label' => $archiveTitle, 'current' => true];
  }
@endphp

<script type="application/ld+json">{!! $faqJsonLd !!}</script>

{{-- HERO --}}
@component('partials.page-hero', ['extra' => 'page-header--shop', 'split' => true, 'asideLabel' => __('Catalog snapshot', 'sage')])
  @include('partials.woocommerce-crumb', ['items' => $crumbItems])
  <p class="eyebrow">{{ \App\field('work_kicker', __('Themes & plugins', 'sage')) }}</p>
  @if (apply_filters('woocommerce_show_page_title', true))
    <h1 class="display-title is-hero woocommerce-products-header__title">
      {{ \App\field('work_h1', __('WordPress themes and plugins for sale.', 'sage')) }}
    </h1>
  @endif
  <p class="lead">
    {{ \App\field('work_lede', __('Browse Sage 11 themes and plugins with live demos. Buy a pack from the shop, or hire me to adapt one for your business. Employer work stays private unless a shop asks to be featured.', 'sage')) }}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} {{ \App\field('work_hero_cta_primary', __('Say hello', 'sage')) }}
    </a>
    <a class="h-text-arrow" href="{{ esc_url(home_url('/portfolio/')) }}">
      {{ __('GitHub portfolio', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/shop',
      'icon'   => 'briefcase',
      'title'  => __('Themes & plugins', 'sage'),
      'meta'   => __('Demos, packs, checkout', 'sage'),
      'stats'  => [
        ['value' => number_format_i18n($productCount), 'label' => __('Listed products', 'sage')],
        ['value' => number_format_i18n($forSaleCount), 'label' => __('Ready to buy', 'sage')],
        ['value' => 'Sage 11',  'label' => __('Tailwind · Vite', 'sage')],
        ['value' => 'WordPress', 'label' => __('Themes & plugins', 'sage')],
      ],
      'link' => [
        'label' => __('Browse GitHub portfolio', 'sage'),
        'href'  => home_url('/portfolio/'),
      ],
    ])
  @endslot
@endcomponent

{{-- CONTEXT --}}
<section class="pf-section work-guide" aria-labelledby="shop-context-heading">
  <div class="container wide">
    <h2 id="shop-context-heading" class="display-title is-section">
      {{ \App\field('work_context_h2', __('What you can buy or hire me to build.', 'sage')) }}
    </h2>
    <div class="work-guide__prose">
      <p>{{ \App\field('work_context_p1', __('Each product is a WordPress theme or plugin with screenshots, stack notes, and a live demo when available. Buy the pack, or hire me to adapt it for your shop.', 'sage')) }}</p>
      {!! \App\field_html('work_context_p2', __('Production client and in-house work stays private unless a shop asks to be featured. If one fits what you run, <a href="/contact/">write and say which</a>. Hiring managers can ask for a private walkthrough of constrained employer work under NDA.', 'sage')) !!}
    </div>
  </div>
</section>

{{-- PRODUCT LOOP --}}
<div class="container wide woo-catalog-shell page-block" data-work-hub>
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
    <div class="woo-empty work-empty" role="status">
      <div class="work-empty__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 28) !!}</div>
      <h2 class="work-empty__title">
        {{ \App\field('work_empty_h2', __('Themes and plugins are on the way.', 'sage')) }}
      </h2>
      <p class="work-empty__text">
        {{ \App\field('work_empty_text', __('I am listing the first packs for sale here. Write and tell me what kind of shop you run, or what plugin you need.', 'sage')) }}
      </p>
      <div class="work-empty__actions">
        <a class="btn" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 16) !!} {{ \App\field('work_empty_cta', __('Say hello', 'sage')) }}
        </a>
      </div>
    </div>
  @endif

  @php
    do_action('woocommerce_after_main_content');
  @endphp

  <div class="work-footer-links">
    {!! \App\field_html('work_foot', __('Code and repos: <a href="/portfolio/">Portfolio page</a>. Live demos open from each product page when available.', 'sage')) !!}
  </div>
</div>

{{-- WHO THIS IS FOR --}}
<section class="pf-section pf-section--alt work-guide" aria-labelledby="shop-fit-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('Browse by role', 'sage') }}</p>
    <h2 id="shop-fit-heading" class="display-title is-section">
      {{ \App\field('work_fit_h2', __('Who this catalog is for.', 'sage')) }}
    </h2>
    <p class="lead work-guide__intro">
      {{ \App\field('work_fit_intro', __('Shops buying a ready theme, agencies needing a solid base, developers evaluating plugins, and hiring managers reviewing my public work.', 'sage')) }}
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
<section class="pf-section work-guide" aria-labelledby="shop-how-heading">
  <div class="container wide">
    <p class="eyebrow">{{ __('From catalog to cart', 'sage') }}</p>
    <h2 id="shop-how-heading" class="display-title is-section">
      {{ \App\field('work_how_h2', __('How to buy or start a build.', 'sage')) }}
    </h2>
    <p class="lead work-guide__intro">
      {{ \App\field('work_how_intro', __('You do not need the perfect match first. Open a product page, buy the pack, or send a short note about what you would change.', 'sage')) }}
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
<section class="pf-section pf-section--alt work-guide" aria-labelledby="shop-faq-heading" id="shop-faq">
  <div class="container wide svc-faq-layout">
    <div class="svc-faq-aside">
      <p class="eyebrow">{{ __('Questions', 'sage') }}</p>
      <h2 id="shop-faq-heading" class="display-title is-section">
        {{ \App\field('work_faq_h2', __('Questions about themes and plugins.', 'sage')) }}
      </h2>
      <p class="svc-faq-aside__intro">
        {{ \App\field('work_faq_intro', __('Straight answers about buying a pack, licensing, demos, and hiring me for a custom build.', 'sage')) }}
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
        <details {{ $i === 0 ? 'open' : '' }}>
          <summary>{{ $faq['title'] }}</summary>
          <p>{{ $faq['text'] }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

{{-- BOTTOM CTA --}}
@include('partials.cta-band', [
  'kicker'        => __('Work with me', 'sage'),
  'title'         => __('Ready to buy or customize a pack?', 'sage'),
  'text'          => __('Tell me which theme or plugin fits your shop and what you'd change. I usually reply within a day.', 'sage'),
  'label'         => __('Say hello', 'sage'),
  'secondary'     => __('GitHub portfolio', 'sage'),
  'secondaryHref' => home_url('/portfolio/'),
])

@php
  do_action('get_footer', 'shop');
@endphp
@endsection
