@extends('layouts.app')

@section('content')
@php
  $writeId = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
@endphp
  @component('partials.page-hero')
    <p class="eyebrow">{{ \App\field('write_kicker', __('Writing', 'sage'), $writeId) }}</p>
    <h1 class="display-title is-hero">{!! get_the_archive_title() !!}</h1>
    @if (get_the_archive_description())
      <div class="lead">{!! get_the_archive_description() !!}</div>
    @endif
  @endcomponent
  <div class="container wide page-block write-hub">
    @include('partials.write-toolbar', compact('writeId', 'writeUrl'))
    @include('partials.write-topics', compact('writeId', 'writeUrl'))
    @if (! have_posts())
      <p>{{ __('No posts in this topic yet.', 'sage') }}</p>
    @else
      <div class="post-list" data-post-list>
        @while(have_posts()) @php(the_post())
          @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
        @endwhile
      </div>
      <nav class="posts-nav" aria-label="{{ __('Posts', 'sage') }}">
        {!! get_the_posts_navigation() !!}
      </nav>
    @endif
    @include('partials.write-subscribe', compact('writeId'))
  </div>
@endsection
