@extends('layouts.app')

@section('content')
@php
  $devto = \App\mh_devto_posts(5);
  $writeId = \App\mh_writing_id();
@endphp
  <header class="page-header container wide">
    <p class="eyebrow">{{ \App\field('write_kicker', __('Writing', 'sage'), $writeId) }}</p>
    <h1 class="display-title is-hero">{{ \App\field('write_h1', __('Writing, with snippets when they help.', 'sage'), $writeId) }}</h1>
    <p class="lead">{{ \App\field('write_lede', __('Notes on WordPress, plugins, and other web apps. Developers can copy the examples. Shops and agencies can see how I explain a build. I also write on DEV.to.', 'sage'), $writeId) }}</p>
  </header>

  <div class="container wide" style="padding-bottom:3rem">
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
          <li><a href="{{ esc_url($d['url']) }}" rel="noopener" target="_blank">{{ $d['title'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a></li>
        @endforeach
      </ul>
    @endif

    <p class="write-follow">{{ \App\field('write_follow', __('Follow along:', 'sage'), $writeId) }}</p>
    @include('partials.social', ['labeled' => true])
  </div>
@endsection
