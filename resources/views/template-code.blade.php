{{--
  Template Name: Code
--}}
@extends('layouts.app')

@section('content')
@php
  $repos = \App\mh_featured_repos();
  $snips = \App\mh_code_snippets();
  $live  = \App\Github::fetchRepos('matthummel-pa', 8, 'updated');
@endphp

<header class="page-header container">
  <h1 class="display-title is-hero">Code you can copy.</h1>
  <p class="lead">Repos and short snippets. If you’re new to WordPress or Sage, start with the snippets, then open a repo and read the README. Questions are welcome on the <a href="{{ home_url('/contact/') }}">contact</a> page.</p>
</header>

<section class="pf-section">
  <div class="container">
    <h2 class="display-title is-section">Featured repos</h2>
    <p>Open these on GitHub. Fork them if they help.</p>
    <div class="pf-grid">
      @foreach ($repos as $r)
        <article class="pf-card">
          <h3><a href="{{ $r['url'] }}" rel="noopener" target="_blank">{{ $r['name'] }}</a></h3>
          <p>{{ $r['desc'] }} {{ implode(', ', $r['tags']) }}.</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

@if ($live)
<section class="pf-section">
  <div class="container">
    <h2 class="display-title is-section">Live from GitHub</h2>
    <div class="pf-grid">
      @foreach ($live as $r)
        <article class="pf-card">
          <h3><a href="{{ $r['url'] }}" rel="noopener" target="_blank">{{ $r['name'] }}</a></h3>
          @if (! empty($r['desc']))
            <p>{{ $r['desc'] }}</p>
          @endif
          <p class="pf-meta">
            {{ $r['lang'] !== '' ? $r['lang'] : 'Code' }}
            @if ($r['stars'])
              · {{ $r['stars'] }} stars
            @endif
          </p>
        </article>
      @endforeach
    </div>
    <p><a href="https://github.com/matthummel-pa?tab=repositories">All public repos</a></p>
  </div>
</section>
@endif

<section class="pf-section">
  <div class="container">
    <h2 class="display-title is-section">Snippets</h2>
    <p class="lead">Tiny examples, the same style I drop into blog posts. Copy them into a post, a theme, or a gist. Change the names so they match your project. Sharing is the point.</p>
    @foreach ($snips as $s)
      <article class="snippet-card">
        <h3>{{ $s['title'] }}</h3>
        <p class="note">{{ $s['note'] }}</p>
        <pre class="snippet"><code>{{ $s['code'] }}</code></pre>
      </article>
    @endforeach
  </div>
</section>
@endsection
