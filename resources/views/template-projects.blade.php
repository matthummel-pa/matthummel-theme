{{--
  Template Name: Projects
--}}
@extends('layouts.app')

@section('content')
@php
  $cat = isset($_GET['cat']) ? sanitize_text_field(wp_unslash($_GET['cat'])) : '';
  $all = \App\mh_work_page_items();
  $cats = array_values(array_unique(array_filter(array_map(fn ($p) => $p['cat'] ?? '', $all))));
  sort($cats);
  $pageUrl = get_permalink();
  $shown = $cat === '' ? $all : array_values(array_filter($all, fn ($p) => ($p['cat'] ?? '') === $cat));
  $ghUser = \App\Github::fetchUser(\App\mh_github_login());
@endphp

<header class="page-header container wide">
  <p class="eyebrow">{{ \App\field('work_kicker', __('Work', 'sage')) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('work_h1', __('Example sites.', 'sage')) }}</h1>
  <p class="lead">{{ \App\field('work_lede', __('Studio concepts for Gettysburg and Adams County: tours, inns, shops, and restaurants. Business owners can picture a real WordPress shape. Developers can see how the pieces fit. Agencies can use them as a reference when a client needs a local site.', 'sage')) }}
    @if (! empty($ghUser['public_repos']))
      Open-source code is on <a href="{{ esc_url($ghUser['url'] ?: 'https://github.com/'.\App\mh_github_login()) }}" rel="noopener" target="_blank">GitHub<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
      ({{ (int) $ghUser['public_repos'] }} public repos).
    @endif
  </p>
</header>

<div class="container wide" style="padding-bottom:3rem">
  <nav class="filter-row" aria-label="{{ __('Filter by type', 'matthummel') }}">
    <a class="filter-pill{{ $cat === '' ? ' is-active' : '' }}" href="{{ esc_url($pageUrl) }}" @if ($cat === '') aria-current="page" @endif>{{ __('All', 'sage') }}</a>
    @foreach ($cats as $c)
      <a class="filter-pill{{ $cat === $c ? ' is-active' : '' }}" href="{{ esc_url(add_query_arg('cat', $c, $pageUrl)) }}" @if ($cat === $c) aria-current="true" @endif>{{ $c }}</a>
    @endforeach
  </nav>

  <div class="work-grid">
    @foreach ($shown as $p)
      <article class="work-card" id="{{ $p['slug'] }}">
        <p class="pf-meta">{{ $p['cat'] }} · {{ $p['place'] }}</p>
        <h2>{{ $p['title'] }}</h2>
        <p>{{ $p['blurb'] }}</p>
        @if (! empty($p['tech']))
          <p class="pill-row">
            @foreach ($p['tech'] as $t)
              <span class="pill">{{ $t }}</span>
            @endforeach
          </p>
        @endif
      </article>
    @endforeach
  </div>

  <p style="margin-top:2rem">{!! \App\field_html('work_foot', __('Repos and snippets: <a href="/code/">Code</a>. Studio site: <a href="https://ridgesandvalleys.com" rel="noopener" target="_blank">ridgesandvalleys.com</a>.', 'sage')) !!}</p>
</div>
@endsection
