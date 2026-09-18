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
    ? sprintf(_n('%d project', '%d projects', $total, 'sage'), $total)
    : sprintf(_n('%d project', '%d projects', $shownCount, 'sage'), $shownCount);
  $howSteps = \App\mh_work_page_how();
  $workFaqs = \App\mh_work_page_faq();
  $heroShot = '';
  if ($all !== []) {
    $first = $all[0];
    $heroShot = ! empty($first['image'])
      ? (string) $first['image']
      : \App\mh_studio_project_image_url($first);
    if ($heroShot === '' && ! empty($first['post_id'])) {
      $heroShot = \App\mh_project_card_image_url((int) $first['post_id']);
    }
  }
@endphp

@component('partials.page-hero', ['split' => true, 'asideLabel' => __('Project snapshot', 'sage')])
  <p class="eyebrow">{{ \App\field('work_kicker', \App\mh_projects_listing_default('kicker')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('work_h1', \App\mh_projects_listing_default('h1')) }}
  </h1>
  <p class="lead">
    {{ \App\field('work_lede', \App\mh_projects_listing_default('lede')) }}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} {{ \App\field('work_hero_cta_primary', \App\mh_projects_listing_default('hero_cta_primary')) }}
    </a>
    <a class="h-text-arrow" href="{{ home_url('/hire/') }}">
      {{ \App\field('work_hero_cta_secondary', \App\mh_projects_listing_default('hero_cta_secondary')) }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/projects',
      'icon' => 'briefcase',
      'title' => __('Sample work', 'sage'),
      'meta' => __('Live demos and public code', 'sage'),
      'image' => $heroShot,
      'imageAlt' => '',
      'link' => [
        'label' => \App\mh_projects_listing_default('hero_cta_primary'),
        'href' => home_url('/contact/'),
      ],
    ])
  @endslot
@endcomponent

@if (! $isEmpty)
  @include('partials.page-nav', [
    'pills' => [
      ['gallery', __('Projects', 'sage')],
      ['how', __('How it works', 'sage')],
      ['work-faq', __('FAQ', 'sage')],
    ],
  ])
@endif

@if ($isEmpty)
  <div class="container wide page-block">
    <div class="work-empty" role="status">
      <div class="work-empty__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 28) !!}</div>
      <h2 class="work-empty__title">
        {{ \App\field('work_empty_h2', \App\mh_projects_listing_default('empty_h2')) }}
      </h2>
      <p class="work-empty__text">
        {{ \App\field('work_empty_text', \App\mh_projects_listing_default('empty_text')) }}
      </p>
      <div class="work-empty__actions">
        <a class="btn" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 16) !!}
          {{ \App\field('work_empty_cta', \App\mh_projects_listing_default('empty_cta')) }}
        </a>
      </div>
    </div>
  </div>

  @include('partials.cta-band', [
    'kicker' => __('Work with me', 'sage'),
    'title' => __('Need a WordPress site?', 'sage'),
    'text' => __('Tell me what you run, or the role you are filling. I usually reply within a day.', 'sage'),
    'label' => __('Say hello', 'sage'),
    'secondary' => __('Hire me', 'sage'),
    'secondaryHref' => home_url('/hire/'),
  ])
@else
  <div id="gallery" class="container wide page-block write-hub" data-work-hub aria-labelledby="work-gallery-heading">
    <h2 id="work-gallery-heading" class="display-title is-section">{{ \App\mh_projects_listing_default('gallery_h2') }}</h2>
    <p class="lead work-guide__intro">{{ \App\field('work_fit_intro', \App\mh_projects_listing_default('fit_intro')) }}</p>

    <div class="write-tools">
      <div class="search-wrap search-wrap--inline">
        <form role="search" class="search-form" action="{{ esc_url($pageUrl) }}" data-work-filter-form>
          <label for="work-site-search">
            <span class="visually-hidden">{{ __('Filter projects', 'sage') }}</span>
            <input
              id="work-site-search"
              type="search"
              class="js-mh-search"
              data-work-filter
              placeholder="{{ esc_attr(\App\field('work_search_ph', \App\mh_projects_listing_default('search_ph'))) }}"
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

    <nav class="filter-row" aria-label="{{ __('Filter by type', 'sage') }}">
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
        <h3 class="work-empty__title">{{ __('No projects in this type yet.', 'sage') }}</h3>
        <p class="work-empty__text">{{ __('Try another filter, or browse the full list.', 'sage') }}</p>
        <div class="work-empty__actions">
          <a class="btn btn-outline" href="{{ esc_url($pageUrl) }}">{{ __('Show all projects', 'sage') }}</a>
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
      <p class="archive-desc" data-work-empty hidden role="status" aria-live="polite">{{ __('No projects match that search.', 'sage') }}</p>
    @endif

    <div class="work-footer-links">
      {!! \App\field_html('work_foot', \App\mh_projects_listing_default('foot')) !!}
    </div>
  </div>

  <section class="pf-section work-guide" id="how" aria-labelledby="work-how-heading">
    <div class="container wide">
      <p class="eyebrow">{{ __('Three steps', 'sage') }}</p>
      <h2 id="work-how-heading" class="display-title is-section">
        {{ \App\field('work_how_h2', \App\mh_projects_listing_default('how_h2')) }}
      </h2>
      <p class="lead work-guide__intro">{{ \App\field('work_how_intro', \App\mh_projects_listing_default('how_intro')) }}</p>
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
          {{ \App\field('work_faq_h2', \App\mh_projects_listing_default('faq_h2')) }}
        </h2>
        <p class="svc-faq-aside__intro">{{ \App\field('work_faq_intro', \App\mh_projects_listing_default('faq_intro')) }}</p>
        <div class="svc-faq-aside__cta">
          <p>{{ __('Still have a question?', 'sage') }}</p>
          <a class="btn btn--sm" href="{{ home_url('/contact/') }}">
            {!! \App\mh_svg_icon('mail', 14) !!} {{ __('Ask me', 'sage') }}
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

  @include('partials.cta-band', [
    'kicker' => __('Work with me', 'sage'),
    'title' => \App\field('work_band_h2', \App\mh_projects_listing_default('band_h2')),
    'text' => \App\field('work_band_lede', \App\mh_projects_listing_default('band_lede')),
    'label' => \App\field('work_hero_cta_primary', \App\mh_projects_listing_default('hero_cta_primary')),
    'secondary' => \App\field('work_hero_cta_secondary', \App\mh_projects_listing_default('hero_cta_secondary')),
    'secondaryHref' => home_url('/hire/'),
  ])
@endif
@endsection
