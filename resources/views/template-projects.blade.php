{{--
  Template Name: Projects
--}}

@extends('layouts.app')

@section('content')

{{-- ── PAGE HEADER ─────────────────────────────────────────────────── --}}
<header class="page-header container" data-anim="fade-up">
  <h1 class="display-title is-hero">{{ get_the_title() }}</h1>
  @if (get_the_excerpt())
    <p class="lead">{{ get_the_excerpt() }}</p>
  @else
    <p class="lead">{{ __('A selection of things I have designed and built — from client web design to open-source WordPress plugins.', 'matthummel') }}</p>
  @endif
</header>

<hr class="rule">

{{-- ── PROJECT GRID ─────────────────────────────────────────────────── --}}
@php
  $catSlug   = isset($_GET['cat']) ? sanitize_key($_GET['cat']) : '';
  $paged     = get_query_var('paged') ?: 1;
  $taxQuery  = $catSlug ? [['taxonomy' => 'project_categories', 'field' => 'slug', 'terms' => $catSlug]] : [];
  $projects  = new \WP_Query([
    'post_type'      => 'projects',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'tax_query'      => $taxQuery,
  ]);
@endphp

{{-- Category filter --}}
@php
  $cats = get_terms(['taxonomy' => 'project_categories', 'hide_empty' => true]);
@endphp
@if ($cats && !is_wp_error($cats))
  <nav class="filter-bar container" aria-label="{{ __('Filter projects', 'matthummel') }}">
    <a class="filter-pill{{ !$catSlug ? ' is-active' : '' }}" href="{{ get_permalink() }}">{{ __('All', 'matthummel') }}</a>
    @foreach ($cats as $cat)
      <a class="filter-pill{{ $catSlug === $cat->slug ? ' is-active' : '' }}"
         href="{{ add_query_arg('cat', $cat->slug, get_permalink()) }}">{{ $cat->name }}</a>
    @endforeach
  </nav>
@endif

<section class="projects-section container">
  @if ($projects->have_posts())
    <div class="project-grid">
      @while ($projects->have_posts()) @php($projects->the_post())
        <article class="project-card" data-anim="fade-up">
          <a href="{{ get_permalink() }}" class="project-card-link">
            @if (has_post_thumbnail())
              <div class="project-card-thumb">
                {!! get_the_post_thumbnail(null, 'medium_large', ['loading' => 'lazy', 'decoding' => 'async']) !!}
              </div>
            @endif
            <div class="project-card-body">
              <h2 class="project-card-title">{!! get_the_title() !!}</h2>
              @php($excerpt = get_the_excerpt())
              @if ($excerpt)
                <p class="project-card-excerpt">{{ wp_trim_words($excerpt, 20) }}</p>
              @endif
              @php($techs = get_the_terms(get_the_ID(), 'project_tags'))
              @if ($techs && !is_wp_error($techs))
                <ul class="tag-list" aria-label="{{ __('Technologies', 'matthummel') }}">
                  @foreach (array_slice($techs, 0, 5) as $tech)
                    <li class="tag-pill">{{ $tech->name }}</li>
                  @endforeach
                </ul>
              @endif
              <span class="project-card-cta">{{ __('View project →', 'matthummel') }}</span>
            </div>
          </a>
        </article>
      @endwhile
    </div>

    {{-- Pagination --}}
    @if ($projects->max_num_pages > 1)
      <nav class="pagination" aria-label="{{ __('Projects pagination', 'matthummel') }}">
        {!! paginate_links([
          'total'   => $projects->max_num_pages,
          'current' => $paged,
          'prev_text' => '← ' . __('Prev', 'matthummel'),
          'next_text' => __('Next', 'matthummel') . ' →',
        ]) !!}
      </nav>
    @endif
  @else
    <p>{{ __('No projects found.', 'matthummel') }}</p>
  @endif
</section>
@php(wp_reset_postdata())

<hr class="rule">

{{-- ── CTA ────────────────────────────────────────────────────────── --}}
<section class="projects-cta container" data-anim="fade-up">
  <div class="cta-card">
    <h2>{{ __('Have a project in mind?', 'matthummel') }}</h2>
    <p>{{ __("I'm open to select freelance and side projects. Let's talk.", 'matthummel') }}</p>
    <a class="btn" href="{{ get_permalink(get_page_by_path('contact')) }}">{{ __('Get in touch', 'matthummel') }}</a>
  </div>
</section>

@endsection
