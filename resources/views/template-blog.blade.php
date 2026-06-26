{{--
  Template Name: Blog
--}}

@extends('layouts.app')

@section('content')

{{-- ── PAGE HEADER ─────────────────────────────────────────────────── --}}
<header class="page-header container" data-anim="fade-up">
  <h1 class="display-title is-hero">{{ get_the_title() }}</h1>
  @if (get_the_excerpt())
    <p class="lead">{{ get_the_excerpt() }}</p>
  @else
    <p class="lead">{{ __('Lessons, tutorials, and real-world examples from building for the web and Microsoft 365.', 'matthummel') }}</p>
  @endif
</header>

<hr class="rule">

{{-- ── CATEGORY FILTER ─────────────────────────────────────────────── --}}
@php
  $activeCat = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
  $cats = get_categories(['hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 12]);
@endphp
@if ($cats)
  <nav class="filter-bar container" aria-label="{{ __('Filter by category', 'matthummel') }}">
    <a class="filter-pill{{ !$activeCat ? ' is-active' : '' }}" href="{{ get_permalink() }}">{{ __('All', 'matthummel') }}</a>
    @foreach ($cats as $cat)
      <a class="filter-pill{{ $activeCat === $cat->term_id ? ' is-active' : '' }}"
         href="{{ esc_url(get_category_link($cat->term_id)) }}">{{ $cat->name }}</a>
    @endforeach
  </nav>
@endif

{{-- ── POST GRID ────────────────────────────────────────────────────── --}}
@php
  $paged = get_query_var('paged') ?: 1;
  $posts = new \WP_Query([
    'post_type'      => 'post',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'cat'            => $activeCat ?: 0,
    'ignore_sticky_posts' => false,
  ]);
@endphp

<section class="blog-section container">
  @if ($posts->have_posts())
    <div class="blog-grid">
      @while ($posts->have_posts()) @php($posts->the_post())
        <article class="blog-card" data-anim="fade-up">
          <a href="{{ get_permalink() }}" class="blog-card-link">
            @if (has_post_thumbnail())
              <div class="blog-card-thumb">
                {!! get_the_post_thumbnail(null, 'medium_large', ['loading' => 'lazy', 'decoding' => 'async']) !!}
              </div>
            @endif
            <div class="blog-card-body">
              @php($category = get_the_category())
              @if ($category)
                <span class="blog-card-cat">{{ $category[0]->name }}</span>
              @endif
              <h2 class="blog-card-title">{!! get_the_title() !!}</h2>
              <p class="blog-card-excerpt">{{ wp_trim_words(get_the_excerpt(), 22) }}</p>
              <footer class="blog-card-meta">
                <time datetime="{{ get_the_date('c') }}">{{ get_the_date() }}</time>
                <span class="blog-card-read">{{ __('Read →', 'matthummel') }}</span>
              </footer>
            </div>
          </a>
        </article>
      @endwhile
    </div>

    {{-- Pagination --}}
    @if ($posts->max_num_pages > 1)
      <nav class="pagination" aria-label="{{ __('Blog pagination', 'matthummel') }}">
        {!! paginate_links([
          'total'     => $posts->max_num_pages,
          'current'   => $paged,
          'prev_text' => '← ' . __('Newer', 'matthummel'),
          'next_text' => __('Older', 'matthummel') . ' →',
        ]) !!}
      </nav>
    @endif
  @else
    <p>{{ __('No posts found.', 'matthummel') }}</p>
  @endif
</section>
@php(wp_reset_postdata())

@endsection
