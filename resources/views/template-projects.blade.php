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
  $total = count($all);
  $shownCount = count($shown);
  $countLabel = $cat === ''
    ? sprintf(_n('%d site', '%d sites', $total, 'sage'), $total)
    : sprintf(_n('%d site', '%d sites', $shownCount, 'sage'), $shownCount);
@endphp

@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('work_kicker', __('Work', 'sage')) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('work_h1', __('Example sites.', 'sage')) }}</h1>
  <p class="lead">{{ \App\field('work_lede', __('Studio concepts for Gettysburg and Adams County: tours, inns, shops, and restaurants. Business owners can picture a real WordPress shape. Developers can see how the pieces fit. Agencies can use them as a reference when a shop needs a local site.', 'sage')) }}
    @if (! empty($ghUser['public_repos']))
      Open-source code is on <a href="{{ esc_url($ghUser['url'] ?: 'https://github.com/'.\App\mh_github_login()) }}" rel="noopener" target="_blank">GitHub<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
      ({{ (int) $ghUser['public_repos'] }} public repos).
    @endif
  </p>
@endcomponent

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
        <button type="button" class="write-view-btn is-active" data-work-view="grid" aria-pressed="true">{{ __('Grid', 'sage') }}</button>
        <button type="button" class="write-view-btn" data-work-view="list" aria-pressed="false">{{ __('List', 'sage') }}</button>
      </div>
      <p class="write-kbd"><kbd>/</kbd> {{ __('to search', 'sage') }}</p>
    </div>
  </div>

  <nav class="filter-row" aria-label="{{ __('Filter by type', 'sage') }}">
    <a class="filter-pill{{ $cat === '' ? ' is-active' : '' }}" href="{{ esc_url($pageUrl) }}" @if ($cat === '') aria-current="page" @endif>
      {{ __('All', 'sage') }}
      <span class="filter-count">{{ $total }}</span>
    </a>
    @foreach ($cats as $c)
      <a class="filter-pill{{ $cat === $c ? ' is-active' : '' }}" href="{{ esc_url(add_query_arg('cat', $c, $pageUrl)) }}" @if ($cat === $c) aria-current="page" @endif>
        {{ $c }}
        <span class="filter-count">{{ (int) $counts[$c] }}</span>
      </a>
    @endforeach
  </nav>

  @if ($shown === [])
    <p>{{ __('No sites in this type yet.', 'sage') }}</p>
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
    <p class="archive-desc" data-work-empty hidden>{{ __('No sites match that search.', 'sage') }}</p>
  @endif

  <section class="write-subscribe" aria-labelledby="work-cta-h">
    <div>
      <h2 id="work-cta-h" class="display-title is-section">{{ \App\field('work_band_h2', __('Want a site in this shape?', 'sage')) }}</h2>
      <p class="sec-intro">{{ \App\field('work_band_lede', __('These are studio concepts, not a case-study deck. If one fits a tour, inn, shop, or restaurant you run, write and say which concept you want to start from.', 'sage')) }}</p>
    </div>
    <p class="btn-row">
      <a class="btn" href="{{ home_url('/contact/') }}">{{ __('Say hello', 'sage') }}</a>
      <a class="btn btn-outline btn-on-dark" href="{{ home_url('/services/') }}">{{ __('How I can help', 'sage') }}</a>
    </p>
  </section>

  <p class="write-follow">{!! \App\field_html('work_foot', __('Repos and snippets: <a href="/code/">Code</a>. Studio site: <a href="https://ridgesandvalleys.com" rel="noopener" target="_blank">ridgesandvalleys.com</a>.', 'sage')) !!}</p>
</div>
@endsection
