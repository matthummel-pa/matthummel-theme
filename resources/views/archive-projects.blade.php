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
    @php($mhTerms = get_terms(['taxonomy' => 'project_categories', 'hide_empty' => false]))
    @if (! is_wp_error($mhTerms) && $mhTerms)
      <div class="project-filters">
        <button type="button" class="project-filter-btn is-active" data-filter="*">{{ __('All', 'matthummel') }}</button>
        @foreach ($mhTerms as $mhT)
          <button type="button" class="project-filter-btn" data-filter="{{ $mhT->slug }}">{{ $mhT->name }}</button>
        @endforeach
      </div>
    @endif

    @if (have_posts())
      <div class="project-grid" id="mh-project-grid">
        @while(have_posts()) @php(the_post())
          @php($mhPterms = wp_get_post_terms(get_the_ID(), 'project_categories', ['fields' => 'slugs']))
          <a class="project-card" href="{{ get_permalink() }}" data-terms="{{ is_array($mhPterms) ? implode(' ', $mhPterms) : '' }}">
            @if (has_post_thumbnail())
              {!! get_the_post_thumbnail(null, 'medium_large', ['loading' => 'lazy']) !!}
            @endif
            <h2>{!! get_the_title() !!}</h2>
            <p>{{ wp_trim_words(get_the_excerpt(), 16) }}</p>
          </a>
        @endwhile
      </div>
    @else
      <p class="archive-desc">{{ __('No projects yet — check back soon.', 'matthummel') }}</p>
    @endif
  </div>

  <script>
  (function(){
    var btns = document.querySelectorAll('.project-filter-btn');
    var cards = document.querySelectorAll('#mh-project-grid .project-card');
    btns.forEach(function(b){
      b.addEventListener('click', function(){
        var f = b.getAttribute('data-filter');
        btns.forEach(function(x){ x.classList.toggle('is-active', x === b); });
        cards.forEach(function(c){
          var t = (c.getAttribute('data-terms') || '').split(' ');
          c.style.display = (f === '*' || t.indexOf(f) > -1) ? '' : 'none';
        });
      });
    });
  })();
  </script>
@endsection
