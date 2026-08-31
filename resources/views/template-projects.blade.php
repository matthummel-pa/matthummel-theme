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
@endphp

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('work_kicker', __('Work', 'sage')) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('work_h1', __('WordPress websites for Gettysburg businesses.', 'sage')) }}
  </h1>
  <p class="lead">
    {{ \App\field('work_lede', __('I publish Gettysburg WordPress projects here — live demos for shops, tours, and inns. Hire me on this site for a real build.', 'sage')) }}
  </p>
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="{{ home_url('/code/') }}">
      {!! \App\mh_svg_icon('code', 15) !!} {{ __('Code and repos', 'sage') }}
    </a>
    <a href="{{ home_url('/services/') }}">{!! \App\mh_svg_icon('briefcase', 15) !!} How I can help</a>
    <a href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 15) !!} Start a project</a>
  </p>
@endcomponent

@if ($isEmpty)
  {{-- EMPTY: no live / published projects yet --}}
  <div class="container wide page-block">
    <div class="work-empty" role="status">
      <div class="work-empty__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 28) !!}</div>
      <h2 class="work-empty__title">
        {{ \App\field('work_empty_h2', __('Example sites are on the way.', 'sage')) }}
      </h2>
      <p class="work-empty__text">
        {{ \App\field('work_empty_text', __('I\'m choosing which Gettysburg projects to publish here first. Write and tell me what kind of shop you run.', 'sage')) }}
      </p>
      <div class="work-empty__actions">
        <a class="btn" href="{{ home_url('/contact/') }}">
          {!! \App\mh_svg_icon('mail', 16) !!}
          {{ \App\field('work_empty_cta', __('Say hello', 'sage')) }}
        </a>
        <a class="btn btn-outline" href="{{ home_url('/services/') }}">
          {!! \App\mh_svg_icon('briefcase', 15) !!}
          {{ __('How I can help', 'sage') }}
        </a>
        <a class="h-text-arrow" href="{{ home_url('/services/') }}">{{ __('How I can help', 'sage') }} →</a>
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
  {{-- ABOUT THESE PROJECTS --}}
  <div class="work-context">
    <div class="container wide work-context__inner">
      <div class="work-context__stat">
        <strong>{{ $total }}</strong>
        <span>projects</span>
      </div>
      <div class="work-context__stat">
        <strong>{{ max(1, count($cats)) }}</strong>
        <span>business types</span>
      </div>
      <div class="work-context__stat">
        <strong>Gettysburg</strong>
        <span>Adams County, PA</span>
      </div>
      <div class="work-context__stat">
        <strong>WordPress</strong>
        <span>every project</span>
      </div>
      <p class="work-context__note">
        These are WordPress projects on this site — the same stack I use for client work, showing what a finished build looks like for a specific type of local business. Real client work stays private unless the shop asks to be featured. If one fits what you run, <a href="{{ home_url('/contact/') }}">write and say which</a>.
      </p>
    </div>
  </div>

  {{-- FILTER + GRID --}}
  <div class="container wide page-block write-hub" data-work-hub>

    <div class="write-tools">
      <div class="search-wrap search-wrap--inline">
        <form role="search" class="search-form" action="{{ esc_url($pageUrl) }}" data-work-filter-form>
          <label>
            <span class="visually-hidden">{{ __('Filter example sites', 'sage') }}</span>
            <input
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
          <button type="button" class="write-view-btn is-active" data-work-view="grid" aria-pressed="true">Grid</button>
          <button type="button" class="write-view-btn" data-work-view="list" aria-pressed="false">List</button>
        </div>
        <p class="write-kbd"><kbd>/</kbd> to search</p>
      </div>
    </div>

    <nav class="filter-row" aria-label="Filter by type">
      <a class="filter-pill{{ $cat === '' ? ' is-active' : '' }}" href="{{ esc_url($pageUrl) }}" @if ($cat === '') aria-current="page" @endif>
        All <span class="filter-count">{{ $total }}</span>
      </a>
      @foreach ($cats as $c)
        <a class="filter-pill{{ $cat === $c ? ' is-active' : '' }}" href="{{ esc_url(add_query_arg('cat', $c, $pageUrl)) }}" @if ($cat === $c) aria-current="page" @endif>
          {{ $c }} <span class="filter-count">{{ (int) $counts[$c] }}</span>
        </a>
      @endforeach
    </nav>

    @if ($shown === [])
      <div class="work-empty work-empty--compact" role="status">
        <h2 class="work-empty__title">{{ __('No sites in this type yet.', 'sage') }}</h2>
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
      <p class="archive-desc" data-work-empty hidden>No sites match that search.</p>
    @endif

    {{-- Bottom CTA --}}
    <div class="work-cta-strip">
      <div class="work-cta-strip__copy">
        <h2>{{ \App\field('work_band_h2', __('Want to start from one of these?', 'sage')) }}</h2>
        <p>{{ \App\field('work_band_lede', __('These projects are available as a starting point for a real build. Tell me which one fits your business and what you\'d change.', 'sage')) }}</p>
      </div>
      <div class="work-cta-strip__actions">
        <a class="btn" href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 16) !!} Say hello</a>
        <a class="about-text-link" href="{{ home_url('/services/') }}">How I can help →</a>
      </div>
    </div>

    <div class="work-footer-links">
      {!! \App\field_html('work_foot', __('Code and repos: <a href="/code/">Code page</a>. Live clickable demos open from each project page when available.', 'sage')) !!}
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
