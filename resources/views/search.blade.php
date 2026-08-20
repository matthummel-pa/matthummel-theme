@extends('layouts.app')

@section('content')
  <div class="page-header container wide">
    <p class="eyebrow">{{ __('Search', 'sage') }}</p>
    <h1 class="display-title is-hero">{{ __('Search results', 'sage') }}</h1>
    <p class="lead">{{ sprintf(__('Showing results for “%s”', 'sage'), get_search_query()) }}</p>
  </div>

  <div class="container" style="padding-bottom:3rem">
    @if (! have_posts())
      <p class="archive-desc">{{ __('Nothing matched. Try another search, or browse Writing and Code.', 'sage') }}</p>
      <div class="search-wrap">{!! get_search_form(false) !!}</div>
      <p class="btn-row">
        <a class="btn" href="{{ get_permalink(get_option('page_for_posts')) ?: home_url('/blog/') }}">{{ __('Writing', 'sage') }}</a>
        <a class="btn btn-outline" href="{{ home_url('/code/') }}">{{ __('Code', 'sage') }}</a>
      </p>
    @else
      <div class="post-list">
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
