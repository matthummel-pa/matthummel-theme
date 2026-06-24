@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  <div class="container">
    @if (! have_posts())
      <x-alert type="warning">
        {!! __('Sorry, no results were found.', 'sage') !!}
      </x-alert>

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
  </div>
@endsection
