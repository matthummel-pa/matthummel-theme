@extends('layouts.app')

@section('content')
@php
  $devto = \App\mh_devto_posts(5);
  $writeId = \App\mh_writing_id();
@endphp
  <header class="page-header container">
    <h1 class="display-title is-hero">{{ \App\field('write_h1', __('Writing, with snippets when they help.', 'sage'), $writeId) }}</h1>
    <p class="lead">{{ \App\field('write_lede', __('Notes on WordPress, Power Platform, and shipping as one person. Look for copy-paste examples in the posts. Categories stay put. I also write on DEV.to.', 'sage'), $writeId) }}</p>
  </header>

  <div class="container" style="padding-bottom:3rem">
    @if (! have_posts())
      <p>{{ __('No posts yet.', 'sage') }}</p>
      {!! get_search_form(false) !!}
    @endif

    <div class="post-list">
      @while(have_posts()) @php(the_post())
        @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
      @endwhile
    </div>

    <nav class="posts-nav" aria-label="{{ __('Posts', 'sage') }}">
      {!! get_the_posts_navigation() !!}
    </nav>

    @if ($devto)
      <h2 class="display-title is-section" style="margin-top:2.5rem">{{ \App\field('write_devto_h2', __('Also on DEV.to', 'sage'), $writeId) }}</h2>
      <ul>
        @foreach ($devto as $d)
          <li><a href="{{ esc_url($d['url']) }}" rel="noopener" target="_blank">{{ $d['title'] }}</a></li>
        @endforeach
      </ul>
    @endif

    <p style="margin-top:1.5rem">{{ \App\field('write_follow', __('Follow along:', 'sage'), $writeId) }}
      <a href="https://dev.to/matthummel" rel="me">DEV.to</a> ·
      <a href="https://bsky.app/profile/matthummel.bsky.social" rel="me">Bluesky</a> ·
      <a href="https://www.reddit.com/user/matt-hummel" rel="me">Reddit</a> ·
      <a href="{{ home_url('/feed/') }}">RSS</a>
    </p>
  </div>
@endsection
