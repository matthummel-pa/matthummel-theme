{{--
  Template Name: Blog
--}}

@extends('layouts.app')

@section('content')

{{-- ── Blog index page header ─────────────────────────────────────────────── --}}
<div class="blog-page-header">
  <div class="container">
    <span class="eyebrow">The Blog</span>
    <h1 class="display-xl blog-index-title">{{ get_the_title() ?: 'Blog' }}</h1>
    @php $excerpt = strip_tags(get_the_excerpt()); @endphp
    <p class="lead">{{ $excerpt ?: 'WordPress tutorials, Power Platform guides, and dev notes from Gettysburg, PA.' }}</p>
  </div>
</div>

{{-- ── Posts ────────────────────────────────────────────────────────────────── --}}
@php
  $activeCat = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
  $paged = get_query_var('paged') ?: 1;
  $thePosts = new \WP_Query([
    'post_type'           => 'post',
    'posts_per_page'      => 12,
    'paged'               => $paged,
    'cat'                 => $activeCat ?: 0,
    'ignore_sticky_posts' => false,
  ]);
@endphp

<div class="container blog-index-body">
  @if ($thePosts->have_posts())
    @php $postCount = 0; @endphp

    @while ($thePosts->have_posts())
      @php
        $thePosts->the_post();
        $postCount++;
        $catList = get_the_category();
        $words   = str_word_count(strip_tags(get_the_content()));
        $mins    = max(1, round($words / 200));
      @endphp

      @if ($postCount === 1)
        {{-- Featured / first post: large hero card --}}
        <article class="blog-hero-card" id="post-{{ get_the_ID() }}">
          @if (has_post_thumbnail())
            <a class="blog-hero-img" href="{{ get_permalink() }}" aria-hidden="true" tabindex="-1">
              {!! get_the_post_thumbnail(get_the_ID(), 'large') !!}
            </a>
          @else
            <div class="blog-hero-img blog-hero-img--placeholder" aria-hidden="true">
              <div class="blog-placeholder-inner"></div>
            </div>
          @endif
          <div class="blog-hero-body">
            @if ($catList)
              <span class="blog-post-tag">{{ $catList[0]->name }}</span>
            @endif
            <h2 class="blog-hero-title">
              <a href="{{ get_permalink() }}">{!! get_the_title() !!}</a>
            </h2>
            <p class="blog-post-meta">
              <time datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
              &middot; {{ $mins }} min read
            </p>
            <div class="blog-hero-excerpt">{!! get_the_excerpt() !!}</div>
            <a class="btn blog-hero-cta" href="{{ get_permalink() }}">Read article →</a>
          </div>
        </article>

        {{-- Start the grid --}}
        <div class="blog-card-grid">

      @else
        {{-- Regular grid card --}}
        <article class="blog-grid-card" id="post-{{ get_the_ID() }}">
          @if (has_post_thumbnail())
            <a class="blog-grid-img" href="{{ get_permalink() }}" aria-hidden="true" tabindex="-1">
              {!! get_the_post_thumbnail(get_the_ID(), 'medium') !!}
            </a>
          @else
            <div class="blog-grid-img blog-grid-img--placeholder" aria-hidden="true">
              <div class="blog-placeholder-inner"></div>
            </div>
          @endif
          <div class="blog-grid-body">
            @if ($catList)
              <span class="blog-post-tag">{{ $catList[0]->name }}</span>
            @endif
            <h2 class="blog-grid-title">
              <a href="{{ get_permalink() }}">{!! get_the_title() !!}</a>
            </h2>
            <p class="blog-post-meta">
              <time datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
              &middot; {{ $mins }} min read
            </p>
            <div class="blog-grid-excerpt">{!! get_the_excerpt() !!}</div>
            <a class="blog-read-more" href="{{ get_permalink() }}">Read more →</a>
          </div>
        </article>
      @endif
    @endwhile

    @if ($postCount > 1)
      </div>{{-- close .blog-card-grid --}}
    @endif

    @if ($thePosts->max_num_pages > 1)
      <nav class="blog-pagination" aria-label="Posts navigation">
        {!! paginate_links([
          'total'     => $thePosts->max_num_pages,
          'current'   => $paged,
          'prev_text' => '← Older posts',
          'next_text' => 'Newer posts →',
        ]) !!}
      </nav>
    @endif

  @else
    <div class="blog-empty">
      <p class="lead">No posts yet — check back soon.</p>
      <a class="btn" href="{{ home_url('/') }}">← Back home</a>
    </div>
  @endif
</div>
@php wp_reset_postdata(); @endphp

@endsection
