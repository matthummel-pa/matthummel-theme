@extends('layouts.app')

@section('content')
@php
  $devto = \App\mh_devto_posts(5);
@endphp
  <header class="page-header container">
    <p class="eyebrow">Writing</p>
    <h1 class="display-title is-hero">{{ $title ?? __('Notes I can stand behind.', 'matthummel') }}</h1>
    <p class="lead">Tutorials and lessons on WordPress, Power Platform, and shipping as one person. Categories stay the same. I also post on DEV.to and talk about the work on Bluesky and Reddit.</p>
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
      <h2 class="display-title is-section" style="margin-top:2.5rem">Also on DEV.to</h2>
      <ul>
        @foreach ($devto as $d)
          <li><a href="{{ esc_url($d['url']) }}" rel="noopener" target="_blank">{{ $d['title'] }}</a></li>
        @endforeach
      </ul>
    @endif

    <p style="margin-top:1.5rem">Follow along:
      <a href="https://dev.to/matthummel" rel="me">DEV.to</a> ·
      <a href="https://bsky.app/profile/matthummel.bsky.social" rel="me">Bluesky</a> ·
      <a href="https://www.reddit.com/user/matt-hummel" rel="me">Reddit</a> ·
      <a href="{{ home_url('/feed/') }}">RSS</a>
    </p>
  </div>
@endsection
