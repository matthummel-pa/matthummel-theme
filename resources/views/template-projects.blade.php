{{--
  Template Name: Projects
--}}
@extends('layouts.app')

@section('content')
@php
  $cat = isset($_GET['cat']) ? sanitize_text_field(wp_unslash($_GET['cat'])) : '';
  $all = \App\mh_studio_projects();
  $cats = \App\mh_studio_project_categories();
  $pageUrl = get_permalink();
  $shown = $cat === '' ? $all : array_values(array_filter($all, fn ($p) => $p['cat'] === $cat));
  $ghUser = \App\Github::fetchUser('matthummel-pa');
@endphp

<header class="page-header container">
  <h1 class="display-title is-hero">Example sites.</h1>
    <p class="lead">These are studio concepts for Gettysburg and Adams County: tours, inns, shops, and restaurants. They show a real WordPress shape — menus, bookings, maps — so a business owner can picture a site, and a new developer can see how the pieces fit.
      @if (! empty($ghUser['public_repos']))
        Open-source code is on <a href="https://github.com/matthummel-pa">GitHub</a> ({{ (int) $ghUser['public_repos'] }} public repos).
      @endif
    </p>
</header>

<div class="container" style="padding-bottom:3rem">
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
        <p>{{ $p['blurb'] }} {{ implode(', ', $p['tech']) }}.</p>
      </article>
    @endforeach
  </div>

  <p style="margin-top:2rem">Repos and snippets: <a href="{{ home_url('/code/') }}">Code</a>. Studio site: <a href="https://ridgesandvalleys.com">ridgesandvalleys.com</a>.</p>
</div>
@endsection
