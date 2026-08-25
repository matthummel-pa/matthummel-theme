@extends('layouts.app')

@section('content')
@php
  $devto    = \App\mh_devto_posts(6);
  $writeId  = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
  $showFeatured = is_home() && ! is_paged() && have_posts() && ! \App\mh_journal_is_oldest();
  $featuredId   = 0;
  $rssUrl       = home_url('/feed/');
@endphp

{{-- HERO --}}
@component('partials.page-hero')
  <p class="eyebrow">{{ \App\field('write_kicker', __('Journal', 'sage'), $writeId) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('write_h1', __('WordPress development notes.', 'sage'), $writeId) }}
  </h1>
  <p class="lead">
    {{ \App\field('write_lede', __('Short posts on WordPress, PHP, Sage, and the tools I actually use on projects. Most include code you can copy and drop in. No padding, no filler.', 'sage'), $writeId) }}
  </p>
  <div class="journal-hero-actions">
    <div class="search-wrap write-hero-search">
      @include('forms.search', ['placeholder' => \App\field('write_search_ph', __('Search posts', 'sage'), $writeId)])
    </div>
    <div class="journal-hero-links">
      <a class="journal-hero-link" href="{{ esc_url($rssUrl) }}" rel="alternate" type="application/rss+xml">
        {!! \App\mh_svg_icon('rss', 14) !!} RSS feed
      </a>
      <a class="journal-hero-link" href="#journal-posts">
        {!! \App\mh_svg_icon('book-open', 14) !!} Browse posts ↓
      </a>
    </div>
  </div>
@endcomponent

{{-- POSTS --}}
<div class="container wide page-block write-hub write-hub--home">
  @include('partials.write-toolbar', ['writeId' => $writeId, 'writeUrl' => $writeUrl, 'hideSearch' => true])
  @include('partials.write-topics', compact('writeId', 'writeUrl'))

  @if (! have_posts())
    <p>No posts yet.</p>
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
            'prev_text' => __('← Older', 'sage'),
            'next_text' => __('Newer →', 'sage'),
          ]) !!}
        </div>
      </div>
      @include('partials.write-aside', ['writeId' => $writeId, 'exclude' => $featuredId])
    </div>
  @endif

  {{-- Subscribe / RSS --}}
  <div class="journal-subscribe">
    <div class="journal-subscribe__copy">
      <h2>{{ \App\field('write_subscribe_h2', __('Follow along.', 'sage'), $writeId) }}</h2>
      <p>{{ \App\field('write_subscribe_lede', __('There\'s no email list. The RSS feed is the most reliable way to read new posts as they come out — paste the URL into any reader.', 'sage'), $writeId) }}</p>
    </div>
    <div class="journal-subscribe__rss">
      <a class="journal-rss-btn" href="{{ esc_url($rssUrl) }}" rel="alternate" type="application/rss+xml">
        {!! \App\mh_svg_icon('rss', 18) !!}
        <span>
          <strong>RSS feed</strong>
          <small>{{ esc_url($rssUrl) }}</small>
        </span>
      </a>
    </div>
  </div>

  {{-- DEV.to mirror --}}
  @if ($devto)
    <div class="journal-devto">
      <h2 class="display-title is-section journal-devto__heading">
        {{ \App\field('write_devto_h2', __('Also on DEV.to', 'sage'), $writeId) }}
      </h2>
      <p class="journal-devto__note">Some posts are cross-posted to DEV.to for the broader developer community.</p>
      <div class="dev-cards">
        @foreach ($devto as $d)
          <article class="dev-card">
            <p class="eyebrow">DEV.to</p>
            <h3>{{ $d['title'] }}</h3>
            @if (! empty($d['ex']))
              <p>{{ $d['ex'] }}</p>
            @endif
            @if (! empty($d['date']) && strtotime($d['date']))
              <p class="post-meta">
                <time datetime="{{ esc_attr(gmdate('c', strtotime($d['date']))) }}">
                  {{ wp_date(get_option('date_format'), strtotime($d['date'])) }}
                </time>
              </p>
            @endif
            @include('partials.read-more', [
              'url'      => $d['url'],
              'name'     => $d['title'],
              'label'    => __('Read on DEV.to', 'sage'),
              'external' => true,
            ])
          </article>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Elsewhere --}}
  <div class="journal-elsewhere">
    <p class="write-follow">{{ \App\field('write_follow', __('Find me elsewhere', 'sage'), $writeId) }}</p>
    @include('partials.social', ['labeled' => true])
  </div>
</div>
@endsection
