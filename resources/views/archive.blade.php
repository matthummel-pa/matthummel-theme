@extends('layouts.app')

@section('content')
  <div class="page-header container">
    <h1 class="display-title is-hero">{!! get_the_archive_title() !!}</h1>
    @if (get_the_archive_description())
      <div class="archive-desc">{!! get_the_archive_description() !!}</div>
    @endif
  </div>

  <div class="container">
    @if (have_posts())
      <div class="post-list">
        @while(have_posts()) @php(the_post())
          @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
        @endwhile
      </div>
      <nav class="posts-nav" aria-label="{{ __('Posts', 'sage') }}">
        {!! get_the_posts_navigation() !!}
      </nav>
    @else
      <p class="post-prose">{{ __('Nothing here yet.', 'matthummel') }}</p>
    @endif
  </div>
@endsection
