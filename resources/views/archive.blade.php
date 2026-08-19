@extends('layouts.app')

@section('content')
  <header class="page-header container">
    <p class="eyebrow">{{ __('Writing', 'matthummel') }}</p>
    <h1 class="display-title is-hero">{!! get_the_archive_title() !!}</h1>
  </header>
  <div class="container" style="padding-bottom:3rem">
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
