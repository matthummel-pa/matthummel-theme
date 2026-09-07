@extends('layouts.app')

@section('content')
@php
  $devto    = \App\mh_devto_posts(6);
  $writeId  = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
  $showFeatured = is_home() && ! is_paged() && have_posts() && ! \App\mh_journal_is_oldest();
  $featuredId   = $showFeatured ? \App\mh_journal_featured_post_id() : 0;
  $showFeatured = $showFeatured && $featuredId > 0;
  $rssUrl       = home_url('/feed/');

  $journalTopics = [
    [
      'icon'  => 'wordpress',
      'title' => __('WordPress', 'sage'),
      'desc'  => __('Custom theme architecture, Sage 11, Blade templates, the_loop, WP-CLI, and real admin UI patterns.', 'sage'),
    ],
    [
      'icon'  => 'php',
      'title' => __('PHP', 'sage'),
      'desc'  => __('Plugin development, hooks, filters, typed functions, REST endpoints, and clean handoff code.', 'sage'),
    ],
    [
      'icon'  => 'javascript',
      'title' => __('JavaScript', 'sage'),
      'desc'  => __('Vanilla JS, ES modules, async patterns, fetch, and TypeScript notes from real projects.', 'sage'),
    ],
    [
      'icon'  => 'tailwind',
      'title' => __('CSS & Tailwind', 'sage'),
      'desc'  => __('Tailwind v4, CSS custom properties, container queries, fluid type, and component patterns.', 'sage'),
    ],
    [
      'icon'  => 'vite',
      'title' => __('Build & Deploy', 'sage'),
      'desc'  => __('Vite, GitHub Actions, SSH rsync, WP-CLI, asset pipelines, and CI/CD for WordPress themes.', 'sage'),
    ],
    [
      'icon'  => 'cursor-ai',
      'title' => __('AI-assisted dev', 'sage'),
      'desc'  => __('Using Cursor, Claude, and ChatGPT in a reviewed, production-safe WordPress workflow.', 'sage'),
    ],
  ];
@endphp

{{-- ── JSON-LD: Blog schema for Google / AI search ───────────────── --}}
@php
  $postCount = (int) wp_count_posts('post')->publish;
  $blogUrl   = $writeUrl;
  $blogLd = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Blog',
    'name'        => \App\field('write_h1', __('WordPress, PHP, and JavaScript — in practice.', 'sage'), $writeId),
    'description' => \App\field('write_lede', __('Practical code notes from WordPress theme development, PHP plugins, Tailwind, Vite, and full-stack web work. Most posts ship with a working snippet you can paste and adapt on your own projects.', 'sage'), $writeId),
    'url'         => esc_url($blogUrl),
    'inLanguage'  => 'en-US',
    'author'      => [
      '@type' => 'Person',
      'name'  => 'Matt Hummel',
      'url'   => home_url('/'),
    ],
    'publisher' => [
      '@type' => 'Person',
      'name'  => 'Matt Hummel',
      'url'   => home_url('/'),
    ],
    'potentialAction' => [
      '@type'       => 'SearchAction',
      'target'      => [
        '@type'       => 'EntryPoint',
        'urlTemplate' => home_url('/?s={search_term_string}'),
      ],
      'query-input' => 'required name=search_term_string',
    ],
  ];
@endphp
<script type="application/ld+json">{!! wp_json_encode($blogLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

{{-- HERO --}}
@component('partials.page-hero', ['split' => true, 'asideLabel' => __('Journal snapshot', 'sage')])
  <p class="eyebrow">{{ \App\field('write_kicker', __('Journal', 'sage'), $writeId) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('write_h1', __('WordPress development notes.', 'sage'), $writeId) }}
  </h1>
  <p class="lead">
    {{ \App\field('write_lede', __('Practical WordPress, PHP, and front-end notes from real projects. Most posts include code you can adapt.', 'sage'), $writeId) }}
  </p>
  <div class="journal-hero-actions">
    <div class="search-wrap write-hero-search">
      @include('forms.search', ['placeholder' => \App\field('write_search_ph', __('Search posts', 'sage'), $writeId)])
    </div>
    <a class="h-text-arrow" href="#journal-posts">
      {{ __('Browse posts', 'sage') }} <span aria-hidden="true">↓</span>
    </a>
  </div>
  @slot('aside')
    @php
      $postCount = (int) wp_count_posts('post')->publish;
      $catCount = count(get_categories(['hide_empty' => true]));
    @endphp
    @include('partials.hero-panel', [
      'chrome' => 'matthummel.com/blog',
      'icon' => 'pen',
      'title' => __('Writing', 'sage'),
      'meta' => __('Code-friendly notes', 'sage'),
      'stats' => [
        ['value' => number_format_i18n($postCount), 'label' => __('Published posts', 'sage')],
        ['value' => number_format_i18n(max(1, $catCount)), 'label' => __('Topics', 'sage')],
        ['value' => 'RSS', 'label' => __('Calm follow', 'sage')],
        ['value' => __('Open', 'sage'), 'label' => __('Fork the code', 'sage')],
      ],
      'link' => [
        'label' => __('RSS feed', 'sage'),
        'href' => $rssUrl,
        'external' => true,
      ],
    ])
  @endslot
@endcomponent

{{-- ── What I write about — topic coverage grid ───────────────────── --}}
<section class="journal-topics-section" aria-labelledby="journal-topics-heading">
  <div class="container wide">
    <div class="journal-topics-head">
      <h2 id="journal-topics-heading" class="journal-topics__title">{{ __('What I write about', 'sage') }}</h2>
      <p class="journal-topics__sub">{{ __('Mostly WordPress and its surrounding stack — from theme architecture to deploy pipelines.', 'sage') }}</p>
    </div>
    <div class="journal-topics-grid">
      @foreach ($journalTopics as $topic)
        <div class="journal-topic-card">
          <span class="journal-topic-card__icon" aria-hidden="true">{!! \App\mh_svg_icon($topic['icon'], 22) !!}</span>
          <h3 class="journal-topic-card__title">{{ $topic['title'] }}</h3>
          <p class="journal-topic-card__desc">{{ $topic['desc'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- POSTS --}}
<div class="container wide page-block write-hub write-hub--home">
  @include('partials.write-toolbar', ['writeId' => $writeId, 'writeUrl' => $writeUrl, 'hideSearch' => true])
  @include('partials.write-topics', compact('writeId', 'writeUrl'))

  @if (! have_posts())
    <p>No posts yet.</p>
  @else
    @if ($showFeatured)
      @php($featuredPost = get_post($featuredId))
      @if ($featuredPost instanceof \WP_Post)
        @php($GLOBALS['post'] = $featuredPost)
        @php(setup_postdata($featuredPost))
        @includeFirst(['partials.content-' . get_post_type(), 'partials.content'], ['featured' => true])
        @php(wp_reset_postdata())
      @endif
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
              @if ((int) get_the_ID() === $featuredId)
                @continue
              @endif
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
      <h2>{{ \App\field('write_subscribe_h2', __('Get new posts by RSS.', 'sage'), $writeId) }}</h2>
      <p>{{ \App\field('write_subscribe_lede', __('No email list. Paste the feed URL into Feedly, NetNewsWire, or any reader you already use — posts land there as they publish.', 'sage'), $writeId) }}</p>
    </div>
    <div class="journal-subscribe__rss">
      <a class="journal-rss-btn" href="{{ esc_url($rssUrl) }}" rel="alternate" type="application/rss+xml" aria-label="{{ __('Subscribe to RSS feed', 'sage') }}">
        {!! \App\mh_svg_icon('rss', 20) !!}
        <span>
          <strong>{{ __('RSS feed', 'sage') }}</strong>
          <small>{{ esc_url($rssUrl) }}</small>
        </span>
      </a>
      <p class="journal-subscribe__note">
        {!! \App\mh_svg_icon('book-open', 13) !!}
        {{ __('Works in Feedly, NetNewsWire, Reeder, Inoreader, and any Atom-compatible reader.', 'sage') }}
      </p>
    </div>
  </div>

  {{-- DEV.to mirror --}}
  @if ($devto)
    <div class="journal-devto">
      <div class="journal-devto__head">
        <div>
          <h2 class="journal-devto__heading">
            {{ \App\field('write_devto_h2', __('Cross-posted to DEV.to', 'sage'), $writeId) }}
          </h2>
          <p class="journal-devto__note">{{ __('Selected posts are mirrored to DEV.to for broader reach. Comment threads on both.', 'sage') }}</p>
        </div>
        <a class="h-text-arrow" href="https://dev.to/matthummel" rel="noopener" target="_blank">{{ __('Follow on DEV.to', 'sage') }} →</a>
      </div>
      <div class="dev-cards">
        @foreach ($devto as $d)
          <article class="dev-card">
            <span class="dev-card__source" aria-label="Source">DEV.to</span>
            <h3 class="dev-card__title"><a href="{{ esc_url($d['url']) }}" rel="noopener" target="_blank">{{ $d['title'] }}</a></h3>
            @if (! empty($d['ex']))
              <p class="dev-card__ex">{{ $d['ex'] }}</p>
            @endif
            @if (! empty($d['date']) && strtotime($d['date']))
              <time class="dev-card__date" datetime="{{ esc_attr(gmdate('c', strtotime($d['date']))) }}">
                {{ wp_date(get_option('date_format'), strtotime($d['date'])) }}
              </time>
            @endif
            <a class="dev-card__read" href="{{ esc_url($d['url']) }}" rel="noopener" target="_blank">
              {{ __('Read on DEV.to', 'sage') }} <span aria-hidden="true">→</span>
            </a>
          </article>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Elsewhere --}}
  <div class="journal-elsewhere">
    <p class="write-follow">{{ \App\field('write_follow', __('More of my writing', 'sage'), $writeId) }}</p>
    @include('partials.social', ['labeled' => true])
  </div>
</div>

@include('partials.cta-band', [
  'kicker' => __('Get in touch', 'sage'),
  'title' => __('Questions about a post?', 'sage'),
  'text' => __('A note about a snippet, a WordPress question, or a role is welcome. I usually reply within a day.', 'sage'),
  'label' => __('Say hello', 'sage'),
])
@endsection
