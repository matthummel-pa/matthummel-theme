@extends('layouts.app')

@section('content')
@php
  $writeId = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
@endphp
  @component('partials.page-hero', ['tag' => 'div'])
    <p class="eyebrow">{{ __('Search', 'sage') }}</p>
    <h1 class="display-title is-hero">{{ __('Search results', 'sage') }}</h1>
    <p class="lead">{{ sprintf(__('Showing results for “%s”', 'sage'), get_search_query()) }}</p>
  @endcomponent

  <div class="container wide page-block write-hub">
    @include('partials.write-toolbar', compact('writeId', 'writeUrl'))
    @include('partials.write-topics', compact('writeId', 'writeUrl'))
    @if (! have_posts())
      <p class="archive-desc">{{ __('Nothing matched. Try another search, or browse Writing and Code.', 'sage') }}</p>
      <p class="btn-row">
        <a class="btn" href="{{ esc_url($writeUrl) }}">{{ __('Writing', 'sage') }}</a>
        <a class="btn btn-outline" href="{{ home_url('/code/') }}">{{ __('Code', 'sage') }}</a>
      </p>
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
  </div>
@endsection
