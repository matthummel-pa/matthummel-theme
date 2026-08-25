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
  $ghUser = \App\Github::fetchUser(\App\mh_github_login());
  $ghBlog = \App\mh_github_blog_url($ghUser);
  $total = count($all);
  $shownCount = count($shown);
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
    {{ \App\field('work_lede', __('Studio concepts from Ridges & Valleys — working demonstrations of what a WordPress site can look like for a specific type of shop, tour, inn, or restaurant in Adams County, PA. Every concept is built on a real stack you can take and run.', 'sage')) }}
  </p>
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="{{ esc_url($ghBlog) }}" rel="noopener" target="_blank">
      {!! \App\mh_svg_icon('globe', 15) !!} Ridges &amp; Valleys ↗
    </a>
    <a href="{{ home_url('/services/') }}">{!! \App\mh_svg_icon('briefcase', 15) !!} How I can help</a>
    <a href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 15) !!} Start with your concept</a>
  </p>
@endcomponent

{{-- ABOUT THESE CONCEPTS --}}
<div class="work-context">
  <div class="container wide work-context__inner">
    <div class="work-context__stat">
      <strong>{{ $total }}</strong>
      <span>concept sites</span>
    </div>
    <div class="work-context__stat">
      <strong>5</strong>
      <span>business types</span>
    </div>
    <div class="work-context__stat">
      <strong>Gettysburg</strong>
      <span>Adams County, PA</span>
    </div>
    <div class="work-context__stat">
      <strong>WordPress</strong>
      <span>every concept</span>
    </div>
    <p class="work-context__note">
      These are studio concepts built by Ridges &amp; Valleys — real WordPress sites, running on the same stack I use for client work, showing what a finished build looks like for a specific type of local business. Real client work stays private unless the client asks to be featured. These concepts are here so you can see the quality of work without waiting for that. If one fits what you run, <a href="{{ home_url('/contact/') }}">write and say which</a>.
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
    <p>No sites in this type yet.</p>
  @else
    @if ($cat === '')
      @include('partials.work-card', ['p' => $shown[0], 'pageUrl' => $pageUrl, 'featured' => true])
    @endif
    <div class="work-grid" data-work-grid>
      @foreach ($shown as $i => $p)
        @if ($cat === '' && $i === 0)
          @continue
        @endif
        @include('partials.work-card', ['p' => $p, 'pageUrl' => $pageUrl])
      @endforeach
    </div>
    <p class="archive-desc" data-work-empty hidden>No sites match that search.</p>
  @endif

  {{-- Bottom CTA --}}
  <div class="work-cta-strip">
    <div class="work-cta-strip__copy">
      <h2>{{ \App\field('work_band_h2', __('Want to start from one of these?', 'sage')) }}</h2>
      <p>{{ \App\field('work_band_lede', __('These concepts are available as a starting point for a real build. Tell me which one fits your business and what you\'d change.', 'sage')) }}</p>
    </div>
    <div class="work-cta-strip__actions">
      <a class="btn" href="{{ home_url('/start/') }}">{!! \App\mh_svg_icon('mail', 16) !!} Start a brief</a>
      <a class="btn btn-outline" href="{{ home_url('/contact/') }}">Say hello</a>
      <a class="about-text-link" href="{{ home_url('/services/') }}">How I can help →</a>
    </div>
  </div>

  <div class="work-footer-links">
    {!! \App\field_html('work_foot', __('Repos and snippets: <a href="/code/">Code</a>. Live studio demos: <a href="https://ridgesandvalleys.com" rel="noopener" target="_blank">ridgesandvalleys.com</a> (proof only — start here with <a href="/start/">a brief</a> or <a href="/contact/">Say hello</a>).', 'sage')) !!}
  </div>

</div>
@endsection
