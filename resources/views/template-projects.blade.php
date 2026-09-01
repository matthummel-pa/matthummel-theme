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
    ? sprintf(_n('%d site', '%d sites', $total, 'sage'), $total)
    : sprintf(_n('%d site', '%d sites', $shownCount, 'sage'), $shownCount);
  $fitCards = \App\mh_work_page_fit();
  $howSteps = \App\mh_work_page_how();
  $workFaqs = \App\mh_work_page_faq();
@endphp

{{-- HERO --}}
@component('partials.page-hero', ['split' => true, 'asideLabel' => __('Work snapshot', 'sage')])
  <p class="eyebrow">{{ \App\field('work_kicker', __('Work', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('work_h1', __('Example WordPress sites.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('work_lede', __('I publish example WordPress sites here — live demos for shops, tours, and inns. Hire me on this site for a real build.', 'sage')) }}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="{{ home_url('/contact/') }}">
      {!! \App\mh_svg_icon('mail', 16) !!} {{ \App\field('work_hero_cta_primary', __('Say hello', 'sage')) }}
    </a>
    <a class="h-text-arrow" href="{{ home_url('/services/') }}">
      {{ \App\field('work_hero_cta_secondary', __('How I can help', 'sage')) }} <span aria-hidden="true">→</span>
    </a>
  </div>
  @slot('aside')
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/projects',
      'icon' => 'globe',
      'title' => __('Example sites', 'sage'),
      'meta' => __('Live WordPress demos', 'sage'),
      'stats' => [
        ['value' => number_format_i18n($total), 'label' => __('Published sites', 'sage')],
        ['value' => number_format_i18n(max(1, count($cats))), 'label' => __('Business types', 'sage')],
        ['value' => 'Sage', 'label' => __('Tailwind · Vite', 'sage')],
        ['value' => 'WordPress', 'label' => __('Every project', 'sage')],
      ],
      'link' => [
        'label' => __('View code and repos', 'sage'),
        'href' => home_url('/code/'),
      ],
    ])
  @endslot
@endcomponent

@if ($isEmpty)
  <div class="container wide page-block">
    <div class="work-empty" role="status">
      <div class="work-empty__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 28) !!}</div>
      <h2 class="work-empty__title">
        {{ \App\field('work_empty_h2', __('Example sites are on the way.', 'sage')) }}
      </h2>
      <p class="work-empty__text">
        {{ \App\field('work_empty_text', __('I\'m choosing which example sites to publish here first. Write and tell me what kind of shop you run.', 'sage')) }}
      </p>
      <div class="work-empty__actions">
        <a class="btn" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 16) !!}
          {{ \App\field('work_empty_cta', __('Say hello', 'sage')) }}
        </a>
        <a class="btn btn-outline" href="{{ home_url('/services/') }}">
          {!! \App\mh_svg_icon('briefcase', 15) !!}
          {{ \App\field('work_hero_cta_secondary', __('How I can help', 'sage')) }}
        </a>
      </div>
    </div>
  </div>

  @include('partials.cta-band', [
    'kicker' => __('Work with me', 'sage'),
    'title' => __('Need a WordPress site before the gallery fills in?', 'sage'),
    'text' => __('Tell me what you run — tour, inn, shop, or restaurant. I usually reply within a day.', 'sage'),
    'label' => __('Say hello', 'sage'),
    'secondary' => __('See services', 'sage'),
    'secondaryHref' => home_url('/services/'),
  ])
@else
  <section class="pf-section work-guide" aria-labelledby="work-context-heading">
    <div class="container wide">
      <h2 id="work-context-heading" class="display-title is-section">
        {{ \App\field('work_context_h2', __('What these example sites show.', 'sage')) }}
      </h2>
      <div class="work-guide__prose">
        <p>{{ \App\field('work_context_p1', __('These are WordPress projects on this site — the same Sage, Tailwind, and Vite stack I use for client work. Each one is built for a specific type of local business so you can see layout, speed, and wp-admin editing in context.', 'sage')) }}</p>
        {!! \App\field_html('work_context_p2', __('Real client work stays private unless the shop asks to be featured. If one fits what you run, <a href="/contact/">write and say which</a>.', 'sage')) !!}
      </div>
    </div>
  </section>

  <section class="pf-section pf-section--alt work-guide" aria-labelledby="work-fit-heading">
    <div class="container wide">
      <p class="eyebrow">{{ __('Browse by role', 'sage') }}</p>
      <h2 id="work-fit-heading" class="display-title is-section">
        {{ \App\field('work_fit_h2', __('Who these demos are for.', 'sage')) }}
      </h2>
      <p class="lead work-guide__intro">{{ \App\field('work_fit_intro', __('Shops, agencies, and developers browse Work for different reasons. All three are welcome.', 'sage')) }}</p>
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
    <h2 id="work-gallery-heading" class="display-title is-section">{{ __('Example site gallery', 'sage') }}</h2>

    <div class="write-tools">
      <div class="search-wrap search-wrap--inline">
        <form role="search" class="search-form" action="{{ esc_url($pageUrl) }}" data-work-filter-form>
          <label for="work-site-search">
            <span class="visually-hidden">{{ __('Filter example sites', 'sage') }}</span>
            <input
              id="work-site-search"
              type="search"
              class="js-mh-search"
              data-work-filter
              placeholder="{{ esc_attr(\App\field('work_search_ph', __('Search sites…', 'sage'))) }}"
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
        <h3 class="work-empty__title">{{ __('No sites in this type yet.', 'sage') }}</h3>
        <p class="work-empty__text">{{ __('Try another filter, or browse all projects.', 'sage') }}</p>
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
      <p class="archive-desc" data-work-empty hidden role="status" aria-live="polite">{{ __('No sites match that search.', 'sage') }}</p>
    @endif

    <div class="work-footer-links">
      {!! \App\field_html('work_foot', __('Code and repos: <a href="/code/">Code page</a>. Live clickable demos open from each project page when available.', 'sage')) !!}
    </div>
  </div>

  <section class="pf-section work-guide" aria-labelledby="work-how-heading">
    <div class="container wide">
      <p class="eyebrow">{{ __('From demo to build', 'sage') }}</p>
      <h2 id="work-how-heading" class="display-title is-section">
        {{ \App\field('work_how_h2', __('How to start from an example site.', 'sage')) }}
      </h2>
      <p class="lead work-guide__intro">{{ \App\field('work_how_intro', __('You do not need to pick the perfect demo first. A short note about your shop and what you would change is enough.', 'sage')) }}</p>
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
          {{ \App\field('work_faq_h2', __('Questions about Work.', 'sage')) }}
        </h2>
        <p class="svc-faq-aside__intro">{{ \App\field('work_faq_intro', __('Straight answers about concept sites, themes for sale, and hiring me for a production build.', 'sage')) }}</p>
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
        <h2>{{ \App\field('work_band_h2', __('Want to start from one of these?', 'sage')) }}</h2>
        <p>{{ \App\field('work_band_lede', __('These projects are available as a starting point for a real build. Tell me which one fits your business and what you\'d change.', 'sage')) }}</p>
      </div>
      <div class="work-cta-strip__actions">
        <a class="btn" href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 16) !!} {{ \App\field('work_hero_cta_primary', __('Say hello', 'sage')) }}</a>
        <a class="about-text-link" href="{{ home_url('/services/') }}">{{ \App\field('work_hero_cta_secondary', __('How I can help', 'sage')) }} <span aria-hidden="true">→</span></a>
      </div>
    </div>
  </div>

  @include('partials.cta-band', [
    'kicker' => __('Work with me', 'sage'),
    'title' => __('Like one of these projects?', 'sage'),
    'text' => __('Tell me which site fits your shop and what you’d change. I usually reply within a day.', 'sage'),
    'label' => __('Say hello', 'sage'),
    'secondary' => __('See services', 'sage'),
    'secondaryHref' => home_url('/services/'),
  ])
@endif
@endsection
