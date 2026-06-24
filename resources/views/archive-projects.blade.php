@extends('layouts.app')

@section('content')
  <div class="page-header container">
    <h1 class="display-title is-hero">{{ __('Projects', 'matthummel') }}</h1>
  </div>

  <div class="container">
    @if (have_posts())
      <div class="project-grid">
        @while(have_posts()) @php(the_post())
          <a class="project-card" href="{{ get_permalink() }}">
            @if (has_post_thumbnail())
              {!! get_the_post_thumbnail(null, 'medium_large', ['loading' => 'lazy']) !!}
            @endif
            <h2>{{ get_the_title() }}</h2>
            <p>{{ get_the_excerpt() }}</p>
          </a>
        @endwhile
      </div>
      {!! get_the_posts_navigation() !!}
    @else
      <x-alert type="warning">{!! __('No projects yet.', 'matthummel') !!}</x-alert>
    @endif
  </div>
@endsection
