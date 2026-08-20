{{--
  Template Name: Code
--}}
@extends('layouts.app')

@section('content')
@php
  $repos = \App\mh_code_page_repos();
  $snips = \App\mh_code_page_snips();
  $live  = \App\mh_github_live_repos(8);
@endphp

<header class="page-header container wide">
  <p class="eyebrow">{{ \App\field('code_kicker', __('Code', 'sage')) }}</p>
  <h1 class="display-title is-hero">{{ \App\field('code_h1', __('Code you can copy.', 'sage')) }}</h1>
  <p class="lead">{!! \App\field_html('code_lede', __('Repos and short snippets. If you’re new to WordPress or Sage, start with the snippets, then open a repo and read the README. Agencies and shops can treat this as a sample of how I write. Questions are welcome on the <a href="/contact/">contact</a> page.', 'sage')) !!}</p>
</header>

<section class="pf-section pf-section--alt">
  <div class="container wide">
    <h2 class="display-title is-section">{{ \App\field('code_feat_h2', __('Featured repos', 'sage')) }}</h2>
    <p>{{ \App\field('code_feat_intro', __('Open these on GitHub. Fork them if they help.', 'sage')) }}</p>
    <div class="pf-grid">
      @foreach ($repos as $r)
        @include('partials.repo-card', ['r' => $r])
      @endforeach
    </div>
  </div>
</section>

@if ($live)
<section class="pf-section">
  <div class="container wide">
    <h2 class="display-title is-section">{{ \App\field('code_live_h2', __('Live from GitHub', 'sage')) }}</h2>
    <div class="pf-grid">
      @foreach ($live as $r)
        @include('partials.repo-card', ['r' => $r])
      @endforeach
    </div>
    <p><a href="https://github.com/{{ \App\mh_github_login() }}?tab=repositories" rel="noopener" target="_blank">{{ \App\field('code_live_all', __('All public repos', 'sage')) }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a></p>
  </div>
</section>
@endif

<section class="pf-section pf-section--alt">
  <div class="container wide">
    <h2 class="display-title is-section">{{ \App\field('code_snip_h2', __('Snippets', 'sage')) }}</h2>
    <p class="lead">{{ \App\field('code_snip_intro', __('Tiny examples, the same style I drop into blog posts. Copy them into a post, a theme, or a gist. Change the names so they match your project. Sharing is the point.', 'sage')) }}</p>
    <div class="snippet-grid">
    @foreach ($snips as $s)
      <article class="snippet-card">
        <h3>{{ $s['title'] }}</h3>
        <p class="note">{{ $s['note'] }}</p>
        <pre class="snippet"><code>{{ $s['code'] }}</code></pre>
      </article>
    @endforeach
    </div>
  </div>
</section>
@endsection
