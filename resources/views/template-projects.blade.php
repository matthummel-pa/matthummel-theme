{{--
  Template Name: Projects
--}}
@extends('layouts.app')

@section('content')
@php
  $cat = isset($_GET['cat']) ? sanitize_text_field(wp_unslash($_GET['cat'])) : '';
  $all = \App\mh_work_page_items();
  $counts = [];
  foreach ($all as $item) {
      $key = (string) ($item['cat'] ?? '');
      if ($key === '') {
          continue;
      }
      $counts[$key] = ($counts[$key] ?? 0) + 1;
  }
  $cats = array_keys($counts);
  sort($cats);
  $pageUrl = get_permalink();
  $shown = $cat === '' ? $all : array_values(array_filter($all, fn ($p) => ($p['cat'] ?? '') === $cat));
  $total = count($all);
  $shownCount = count($shown);
  $isEmpty = $total === 0;
  $countLabel = $cat === ''
    ? sprintf(_n('%d product', '%d products', $total, 'sage'), $total)
    : sprintf(_n('%d product', '%d products', $shownCount, 'sage'), $shownCount);
  $fitCards = \App\mh_work_page_fit();
  $howSteps = \App\mh_work_page_how();
  $workFaqs = \App\mh_work_page_faq();
  $shopUrl = function_exists('wc_get_page_permalink')
    ? wc_get_page_permalink('shop')
    : home_url('/shop/');
  $forSaleCount = count(array_filter($all, static fn ($p) => ($p['buy_url'] ?? '') !== ''));
@endphp

{{-- HERO --}}
@component('partials.page-hero', ['split' => true, 'asideLabel' => __('Catalog snapshot', 'sage')])
  <p class="eyebrow">{{ \App\field('work_kicker', __('Themes & plugins', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('work_h1', __('WordPress themes and plugins for sale.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('work_lede', __('Browse Sage 11 themes and plugins with live demos for tours, shops, and inns. Buy a pack from the shop, or hire me to adapt one for your business. Employer work stays private unless a shop asks to be featured.', 'sage')) }}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} {{ \App\field('work_hero_cta_primary', __('Say hello', 'sage')) }}
    </a>
    <a class="h-text-arrow" href="{{ esc_url($shopUrl) }}">
      {{ \App\field('work_hero_cta_secondary', __('Open shop', 'sage')) }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/projects',
      'icon' => 'briefcase',
      'title' => __('Themes & plugins', 'sage'),
      'meta' => __('Demos, packs, checkout', 'sage'),
      'stats' => [
        ['value' => number_format_i18n($total), 'label' => __('Listed products', 'sage')],
        ['value' => number_format_i18n($forSaleCount), 'label' => __('Ready to buy', 'sage')],
        ['value' => 'Sage', 'label' => __('Tailwind · Vite', 'sage')],
        ['value' => 'WordPress', 'label' => __('Themes & plugins', 'sage')],
      ],
      'link' => [
        'label' => __('Open shop checkout', 'sage'),
        'href' => $shopUrl,
      ],
    ])
  @endslot
@endcomponent

@if ($isEmpty)
  <div class="container wide page-block">
    <div class="work-empty" role="status">
      <div class="work-empty__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 28) !!}</div>
      <h2 class="work-empty__title">
        {{ \App\field('work_empty_h2', __('Themes and plugins are on the way.', 'sage')) }}
      </h2>
      <p class="work-empty__text">
        {{ \App\field('work_empty_text', __('I am listing the first packs for sale here. Write and tell me what kind of shop you run, or what plugin you need.', 'sage')) }}
      </p>
      <div class="work-empty__actions">
        <a class="btn" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 16) !!}
          {{ \App\field('work_empty_cta', __('Say hello', 'sage')) }}
        </a>
        <a class="btn btn-outline" href="{{ esc_url($shopUrl) }}">
          {!! \App\mh_svg_icon('cart', 15) !!}
          {{ \App\field('work_hero_cta_secondary', __('Open shop', 'sage')) }}
        </a>
      </div>
    </div>
  </div>

  @include('partials.cta-band', [
    'kicker' => __('Work with me', 'sage'),
    'title' => __('Need a WordPress theme or plugin before the catalog fills in?', 'sage'),
    'text' => __('Tell me what you run — tour, inn, shop, or restaurant — or what plugin you need. I usually reply within a day.', 'sage'),
    'label' => __('Say hello', 'sage'),
    'secondary' => __('See services', 'sage'),
    'secondaryHref' => home_url('/services/'),
  ])
@else
  <section class="pf-section work-guide" aria-labelledby="work-context-heading">
    <div class="container wide">
      <h2 id="work-context-heading" class="display-title is-section">
        {{ \App\field('work_context_h2', __('What you can buy or hire me to build.', 'sage')) }}
      </h2>
      <div class="work-guide__prose">
        <p>{{ \App\field('work_context_p1', __('Each card is a WordPress theme or plugin with screenshots, stack notes, and a live demo when available. Buy the pack from the shop, or hire me to adapt it for your shop.', 'sage')) }}</p>
        {!! \App\field_html('work_context_p2', __('Production client and in-house work stays private unless a shop asks to be featured. If one fits what you run, <a href="/contact/">write and say which</a>. Hiring managers can ask for a private walkthrough of constrained employer work under NDA.', 'sage')) !!}
      </div>
    </div>
  </section>

  <section class="pf-section pf-section--alt work-guide" aria-labelledby="work-fit-heading">
    <div class="container wide">
      <p class="eyebrow">{{ __('Browse by role', 'sage') }}</p>
      <h2 id="work-fit-heading" class="display-title is-section">
        {{ \App\field('work_fit_h2', __('Who this catalog is for.', 'sage')) }}
      </h2>
      <p class="lead work-guide__intro">{{ \App\field('work_fit_intro', __('Shops buying a ready theme, agencies needing a solid base, developers evaluating plugins, and hiring managers reviewing my public work.', 'sage')) }}</p>
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

  <div class="container wide page-block write-hub" data-work-hub aria-labelledby="work-gallery-heading">
    <h2 id="work-gallery-heading" class="display-title is-section">{{ __('Themes and plugins', 'sage') }}</h2>

    <div class="write-tools">
      <div class="search-wrap search-wrap--inline">
        <form role="search" class="search-form" action="{{ esc_url($pageUrl) }}" data-work-filter-form>
          <label for="work-site-search">
            <span class="visually-hidden">{{ __('Filter themes and plugins', 'sage') }}</span>
            <input
              id="work-site-search"
              type="search"
              class="js-mh-search"
              data-work-filter
              placeholder="{{ esc_attr(\App\field('work_search_ph', __('Search themes and plugins…', 'sage'))) }}"
              autocomplete="off"
            >
          </label>
        </form>
      </div>
      <div class="write-tool-actions">
        <p class="write-count" data-work-count aria-live="polite">{{ $countLabel }}</p>
        <div class="write-view" role="group" aria-label="{{ __('Layout', 'sage') }}">
          <button type="button" class="write-view-btn is-active" data-work-view="grid" aria-pressed="true">{{ __('Grid', 'sage') }}</button>
          <button type="button" class="write-view-btn" data-work-view="list" aria-pressed="false">{{ __('List', 'sage') }}</button>
        </div>
        <p class="write-kbd"><kbd>/</kbd> {{ __('to search', 'sage') }}</p>
      </div>
    </div>

    <nav class="filter-row" aria-label="{{ __('Filter by business type', 'sage') }}">
      <a class="filter-pill{{ $cat === '' ? ' is-active' : '' }}" href="{{ esc_url($pageUrl) }}" @if ($cat === '') aria-current="page" @endif>
        {{ __('All', 'sage') }} <span class="filter-count">{{ $total }}</span>
      </a>
      @foreach ($cats as $c)
        <a class="filter-pill{{ $cat === $c ? ' is-active' : '' }}" href="{{ esc_url(add_query_arg('cat', $c, $pageUrl)) }}" @if ($cat === $c) aria-current="page" @endif>
          {{ $c }} <span class="filter-count">{{ (int) $counts[$c] }}</span>
        </a>
      @endforeach
    </nav>

    @if ($shown === [])
      <div class="work-empty work-empty--compact" role="status">
        <h3 class="work-empty__title">{{ __('No products in this type yet.', 'sage') }}</h3>
        <p class="work-empty__text">{{ __('Try another filter, or browse the full catalog.', 'sage') }}</p>
        <div class="work-empty__actions">
          <a class="btn btn-outline" href="{{ esc_url($pageUrl) }}">{{ __('Show all products', 'sage') }}</a>
        </div>
      </div>
    @else
      <div class="work-grid" data-work-grid>
        @foreach ($shown as $i => $p)
          @include('partials.work-card', [
            'p' => $p,
            'pageUrl' => $pageUrl,
            'featured' => $cat === '' && $i === 0 && $shownCount >= 3,
          ])
        @endforeach
      </div>
      <p class="archive-desc" data-work-empty hidden role="status" aria-live="polite">{{ __('No products match that search.', 'sage') }}</p>
    @endif

    <div class="work-footer-links">
      {!! \App\field_html('work_foot', __('Checkout lives in the <a href="/shop/">shop</a>. Code and repos: <a href="/code/">Code page</a>. Live demos open from each product page when available.', 'sage')) !!}
    </div>
  </div>

  <section class="pf-section work-guide" aria-labelledby="work-how-heading">
    <div class="container wide">
      <p class="eyebrow">{{ __('From catalog to cart', 'sage') }}</p>
      <h2 id="work-how-heading" class="display-title is-section">
        {{ \App\field('work_how_h2', __('How to buy or start a build.', 'sage')) }}
      </h2>
      <p class="lead work-guide__intro">{{ \App\field('work_how_intro', __('You do not need the perfect match first. Open a product page, buy the pack, or send a short note about what you would change.', 'sage')) }}</p>
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

  @php
    $faqSchema = array_map(
      fn ($f) => ['@type' => 'Question', 'name' => $f['title'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['text']]],
      $workFaqs
    );
    $faqJsonLd = json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqSchema], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
  @endphp
  <script type="application/ld+json">{!! $faqJsonLd !!}</script>

  <section class="pf-section pf-section--alt work-guide" aria-labelledby="work-faq-heading" id="work-faq">
    <div class="container wide svc-faq-layout">
      <div class="svc-faq-aside">
        <p class="eyebrow">{{ __('Questions', 'sage') }}</p>
        <h2 id="work-faq-heading" class="display-title is-section">
          {{ \App\field('work_faq_h2', __('Questions about themes and plugins.', 'sage')) }}
        </h2>
        <p class="svc-faq-aside__intro">{{ \App\field('work_faq_intro', __('Straight answers about buying a pack, licensing, demos, and hiring me for a custom build.', 'sage')) }}</p>
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

  <div class="container wide page-block">
    <div class="work-cta-strip">
      <div class="work-cta-strip__copy">
        <h2>{{ \App\field('work_band_h2', __('Want one of these on your site?', 'sage')) }}</h2>
        <p>{{ \App\field('work_band_lede', __('Buy the theme or plugin when it is listed, or hire me to customize it. Tell me which one fits and what you would change.', 'sage')) }}</p>
      </div>
      <div class="work-cta-strip__actions">
        <a class="btn" href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 16) !!} {{ \App\field('work_hero_cta_primary', __('Say hello', 'sage')) }}</a>
        <a class="about-text-link" href="{{ esc_url($shopUrl) }}">{{ \App\field('work_hero_cta_secondary', __('Open shop', 'sage')) }} <span aria-hidden="true">→</span></a>
      </div>
    </div>
  </div>

  @include('partials.cta-band', [
    'kicker' => __('Work with me', 'sage'),
    'title' => __('Ready to buy or customize a pack?', 'sage'),
    'text' => __('Tell me which theme or plugin fits your shop and what you’d change. I usually reply within a day.', 'sage'),
    'label' => __('Say hello', 'sage'),
    'secondary' => __('Open shop', 'sage'),
    'secondaryHref' => $shopUrl,
  ])
@endif
@endsection
