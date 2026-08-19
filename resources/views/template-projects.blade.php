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
  <p class="eyebrow">Work</p>
  <h1 class="display-title is-hero">Ridges &amp; Valleys work.</h1>
  <p class="lead">These are studio concepts for Gettysburg and Adams County: tours, inns, shops, and restaurants. Each one is a real WordPress shape — menus, bookings, maps, and local search — not a slide deck.</p>
  @if (!empty($ghUser['public_repos']))
    <p class="pf-meta">Also {{ (int) $ghUser['public_repos'] }} public repos on <a href="https://github.com/matthummel-pa">GitHub</a>.</p>
  @endif
</header>

<div class="container" style="padding-bottom:3rem">
  <nav class="filter-row" aria-label="{{ __('Filter by type', 'matthummel') }}">
    <a class="filter-pill{{ $cat === '' ? ' is-active' : '' }}" href="{{ esc_url($pageUrl) }}">All</a>
    @foreach ($cats as $c)
      <a class="filter-pill{{ $cat === $c ? ' is-active' : '' }}" href="{{ esc_url(add_query_arg('cat', $c, $pageUrl)) }}">{{ $c }}</a>
    @endforeach
  </nav>

  <div class="pf-grid">
    @foreach ($shown as $p)
      <article class="pf-card" id="{{ $p['slug'] }}">
        <p class="pf-meta">{{ $p['cat'] }} · {{ $p['place'] }}</p>
        <h2>{{ $p['title'] }}</h2>
        <p>{{ $p['blurb'] }}</p>
        <div class="pill-row">
          @foreach ($p['tech'] as $t)
            <span class="pill">{{ $t }}</span>
          @endforeach
        </div>
      </article>
    @endforeach
  </div>

  <p style="margin-top:2rem">Open-source code lives on the <a href="{{ home_url('/code/') }}">Code</a> page. Studio site: <a href="https://ridgesandvalleys.com">ridgesandvalleys.com</a>.</p>
</div>
@endsection
