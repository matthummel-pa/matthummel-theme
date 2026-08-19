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

<header class="page-header container">
  <h1 class="display-title is-hero">{{ \App\field('work_h1', __('Example sites.', 'sage')) }}</h1>
    <p class="lead">{{ \App\field('work_lede', __('These are studio concepts for Gettysburg and Adams County: tours, inns, shops, and restaurants. They show a real WordPress shape — menus, bookings, maps — so a business owner can picture a site, and a new developer can see how the pieces fit.', 'sage')) }}
      @if (! empty($ghUser['public_repos']))
        Open-source code is on <a href="{{ esc_url($ghUser['url'] ?: 'https://github.com/'.\App\mh_github_login()) }}">GitHub</a>@if (! empty($ghUser['public_repos'])) ({{ (int) $ghUser['public_repos'] }} public repos)@endif.
      @endif
    </p>
</header>

<div class="container wide" style="padding-bottom:3rem">
  <nav class="filter-row" aria-label="{{ __('Filter by type', 'matthummel') }}">
    <a class="filter-pill{{ $cat === '' ? ' is-active' : '' }}" href="{{ esc_url($pageUrl) }}">All</a>
    @foreach ($cats as $c)
      <a class="filter-pill{{ $cat === $c ? ' is-active' : '' }}" href="{{ esc_url(add_query_arg('cat', $c, $pageUrl)) }}">{{ $c }}</a>
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

  <p style="margin-top:2rem">{!! \App\field_html('work_foot', __('Repos and snippets: <a href="/code/">Code</a>. Studio site: <a href="https://ridgesandvalleys.com">ridgesandvalleys.com</a>.', 'sage')) !!}</p>
</div>
@endsection
