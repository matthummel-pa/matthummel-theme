@extends('layouts.app')

@section('content')
@php
  $devto    = \App\mh_devto_posts(6);
  $writeId  = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
  $rssUrl   = home_url('/feed/');

  $journalTopics = [
    [
      'icon'  => 'wordpress',
      'title' => __('WordPress', 'sage'),
      'desc'  => __('Custom themes, plugins, admin UI, and handoffs shops can keep using.', 'sage'),
    ],
    [
      'icon'  => 'php',
      'title' => __('PHP', 'sage'),
      'desc'  => __('Plugins, hooks, and clean code another developer can pick up without a scavenger hunt.', 'sage'),
    ],
    [
      'icon'  => 'javascript',
      'title' => __('JavaScript', 'sage'),
      'desc'  => __('Front-end behavior, forms, and small modules from real WordPress projects.', 'sage'),
    ],
    [
      'icon'  => 'tailwind',
      'title' => __('CSS & layout', 'sage'),
      'desc'  => __('Readable layouts, fluid type, and styles that hold up on phones and wide screens.', 'sage'),
    ],
    [
      'icon'  => 'vite',
      'title' => __('Build & deploy', 'sage'),
      'desc'  => __('Shipping themes safely — builds, updates, and notes so launch is not a fire drill.', 'sage'),
    ],
    [
      'icon'  => 'cursor-ai',
      'title' => __('AI-assisted work', 'sage'),
      'desc'  => __('Using AI on the repeatable parts, then reviewing every line before it ships.', 'sage'),
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
    'description' => \App\field('write_lede', __('Practical notes from WordPress themes, PHP plugins, and full-stack web work. Most posts ship with a working snippet you can paste and adapt.', 'sage'), $writeId),
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
      <p class="journal-topics__sub">{{ __('Mostly WordPress — themes, plugins, and the practical notes that help a build ship.', 'sage') }}</p>
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
    <div class="write-layout">
      <div class="write-main" id="journal-posts">
        <div class="post-list" data-post-list>
          @while(have_posts())
            @php(the_post())
            @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
          @endwhile
        </div>
        <div class="posts-nav">
          {!! get_the_posts_pagination([
            'mid_size' => 1,
            'prev_text' => __('← Older', 'sage'),
            'next_text' => __('Newer →', 'sage'),
          ]) !!}
        </div>
      </div>
      @include('partials.write-aside', ['writeId' => $writeId])
    </div>
  @endif

  @include('partials.write-subscribe', compact('writeId'))

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
  'text' => __('A note about a snippet, a WordPress question, or a role is welcome. I usually reply within one business day (ET).', 'sage'),
  'label' => __('Say hello', 'sage'),
])
@endsection
