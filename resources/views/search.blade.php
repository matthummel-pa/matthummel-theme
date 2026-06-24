@extends('layouts.app')

@section('content')
  <div class="page-header container">
    <h1 class="display-title is-hero">{{ __('Search results', 'sage') }}</h1>
    <p class="lead">{{ sprintf(__('Showing results for “%s”', 'sage'), get_search_query()) }}</p>
  </div>

  <div class="container">
    @if (! have_posts())
      <p class="archive-desc">{{ __('Nothing matched. Try another search.', 'sage') }}</p>
      <div class="search-wrap">{!! get_search_form(false) !!}</div>
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
