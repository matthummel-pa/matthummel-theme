@extends('layouts.app')

@section('content')
@php
  $devto = \App\mh_devto_posts(6);
  $writeId = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
  $showFeatured = is_home() && ! is_paged() && have_posts() && ! \App\mh_journal_is_oldest();
  $featuredId = 0;
@endphp
  @component('partials.page-hero')
    <p class="eyebrow">{{ \App\field('write_kicker', __('Journal', 'sage'), $writeId) }}</p>
    <h1 class="display-title is-hero">{{ \App\field('write_h1', __('Journal', 'sage'), $writeId) }}</h1>
    <p class="lead">{{ \App\field('write_lede', __('I write about WordPress, plugins, and other web apps I build. Posts walk through the problem and, when it helps, the code. Developers can copy the examples; shops and agencies can see how I explain a build.', 'sage'), $writeId) }}</p>
    <div class="search-wrap write-hero-search">
      @include('forms.search', ['placeholder' => \App\field('write_search_ph', __('Search posts', 'sage'), $writeId)])
    </div>
    <p class="write-hero-jump">
      <a href="#journal-posts">{{ \App\field('write_browse', __('Browse posts', 'sage'), $writeId) }}</a>
    </p>
  @endcomponent

  <div class="container wide page-block write-hub write-hub--home">
    @include('partials.write-toolbar', ['writeId' => $writeId, 'writeUrl' => $writeUrl, 'hideSearch' => true])
    @include('partials.write-topics', compact('writeId', 'writeUrl'))

    @if (! have_posts())
      <p>{{ __('No posts yet.', 'sage') }}</p>
    @else
      @if ($showFeatured)
        @php(the_post())
        @php($featuredId = (int) get_the_ID())
        @includeFirst(['partials.content-' . get_post_type(), 'partials.content'], ['featured' => true])
      @endif

      <div class="write-layout">
        <div class="write-main" id="journal-posts">
          @if ($showFeatured && have_posts())
            <h2 class="write-list-h">{{ \App\field('write_recent_h2', __('Recent posts', 'sage'), $writeId) }}</h2>
          @endif
          <div class="post-stack" data-post-list>
            <div class="post-list">
              @while(have_posts())
                @php(the_post())
                @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
              @endwhile
            </div>
          </div>
          <div class="posts-nav">
            {!! get_the_posts_pagination([
              'mid_size' => 1,
              'prev_text' => __('Previous', 'sage'),
              'next_text' => __('Next', 'sage'),
            ]) !!}
          </div>
        </div>
        @include('partials.write-aside', ['writeId' => $writeId, 'exclude' => $featuredId])
      </div>
    @endif

    @include('partials.write-subscribe', compact('writeId'))

    @if ($devto)
      <h2 class="display-title is-section write-devto-h">{{ \App\field('write_devto_h2', __('Also on DEV.to', 'sage'), $writeId) }}</h2>
      <div class="dev-cards">
        @foreach ($devto as $d)
          <article class="dev-card">
            <p class="eyebrow">DEV.to</p>
            <h3>{{ $d['title'] }}</h3>
            @if (! empty($d['ex']))
              <p>{{ $d['ex'] }}</p>
            @endif
            @if (! empty($d['date']) && strtotime($d['date']))
              <p class="post-meta"><time datetime="{{ esc_attr(gmdate('c', strtotime($d['date']))) }}">{{ wp_date(get_option('date_format'), strtotime($d['date'])) }}</time></p>
            @endif
            @include('partials.read-more', [
              'url' => $d['url'],
              'name' => $d['title'],
              'label' => __('Read on DEV.to', 'sage'),
              'external' => true,
            ])
          </article>
        @endforeach
      </div>
    @endif

    <p class="write-follow">{{ \App\field('write_follow', __('More of my notes', 'sage'), $writeId) }}</p>
    @include('partials.social', ['labeled' => true])
  </div>
@endsection
