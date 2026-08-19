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
  <p class="eyebrow">Code</p>
  <h1 class="display-title is-hero">Repos and snippets.</h1>
  <p class="lead">I keep the useful bits public. Start with the four repos I care about most. Then browse live GitHub. Copy the snippets if they help.</p>
</header>

<section class="pf-section pf-section--alt">
  <div class="container">
    <h2 class="display-title is-section">Featured repos</h2>
    <div class="pf-grid">
      @foreach ($repos as $r)
        <article class="pf-card">
          <h3><a href="{{ $r['url'] }}" rel="noopener" target="_blank">{{ $r['name'] }}</a></h3>
          <p>{{ $r['desc'] }}</p>
          <div class="pill-row">
            @foreach ($r['tags'] as $t)
              <span class="pill">{{ $t }}</span>
            @endforeach
          </div>
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
    <p style="margin-top:1rem"><a href="https://github.com/matthummel-pa?tab=repositories">All public repos</a></p>
  </div>
</section>
@endif

<section class="pf-section pf-section--alt">
  <div class="container">
    <h2 class="display-title is-section">Snippets</h2>
    <p class="lead">Short bits I reuse. Grade-school comments. Production habits.</p>
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
