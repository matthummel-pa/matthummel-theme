@extends('layouts.app')

@section('content')
  <div class="page-header container">
    <h1 class="display-title is-hero">{{ __('Projects', 'matthummel') }}</h1>
    @php($mhProjIntro = get_theme_mod('mh_projects_intro', ''))
    @if ($mhProjIntro)
      <div class="archive-desc">{!! wp_kses_post($mhProjIntro) !!}</div>
    @endif
  </div>

  <div class="container">
    @if (have_posts())
      <div class="project-grid">
        @while(have_posts()) @php(the_post())
          <a class="project-card" href="{{ get_permalink() }}">
            @if (has_post_thumbnail())
              {!! get_the_post_thumbnail(null, 'medium_large', ['loading' => 'lazy']) !!}
            @endif
            <h2>{!! get_the_title() !!}</h2>
            <p>{{ wp_trim_words(get_the_excerpt(), 18) }}</p>
          </a>
        @endwhile
      </div>
      <nav class="posts-nav" aria-label="{{ __('Projects', 'matthummel') }}">{!! get_the_posts_navigation() !!}</nav>
    @else
      <p class="archive-desc">{{ __('No projects yet — check back soon.', 'matthummel') }}</p>
    @endif
  </div>
@endsection
