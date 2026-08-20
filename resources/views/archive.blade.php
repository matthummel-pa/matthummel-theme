@extends('layouts.app')

@section('content')
@php
  $writeId = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
  $cats = get_categories(['hide_empty' => true]);
  $current = is_category() ? get_queried_object_id() : 0;
@endphp
  <header class="page-header container wide">
    <h1 class="display-title is-hero">{!! get_the_archive_title() !!}</h1>
  </header>
  <div class="container wide page-block">
    @if ($cats)
      <nav class="filter-row" aria-label="{{ __('Filter by topic', 'sage') }}">
        <a class="filter-pill{{ $current === 0 ? ' is-active' : '' }}" href="{{ esc_url($writeUrl) }}" @if ($current === 0) aria-current="page" @endif>{{ __('All', 'sage') }}</a>
        @foreach ($cats as $c)
          <a class="filter-pill{{ $current === (int) $c->term_id ? ' is-active' : '' }}" href="{{ esc_url(get_category_link($c)) }}" @if ($current === (int) $c->term_id) aria-current="page" @endif>{{ $c->name }}</a>
        @endforeach
      </nav>
    @endif
    @if (! have_posts())
      <p>{{ __('No posts yet.', 'sage') }}</p>
    @endif
    <div class="post-list">
      @while(have_posts()) @php(the_post())
        @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
      @endwhile
    </div>
    <nav class="posts-nav" aria-label="{{ __('Posts', 'sage') }}">
      {!! get_the_posts_navigation() !!}
    </nav>
  </div>
@endsection
