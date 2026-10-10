@extends('layouts.app')

@section('content')
@php
  $writeId  = \App\mh_writing_id();
  $writeUrl = $writeId ? get_permalink($writeId) : home_url('/blog/');
  $rssUrl   = home_url('/feed/');

  $latestJournal = get_posts([
    'post_type'      => 'post',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
    'no_found_rows'  => true,
    'orderby'        => 'date',
    'order'          => 'DESC',
  ]);
  $journalShot = $latestJournal !== [] ? \App\mh_post_card_image((int) $latestJournal[0]->ID) : '';

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
@component('partials.page-hero', ['image' => $journalShot])
  <p class="eyebrow">{{ \App\field('write_kicker', __('Journal', 'sage'), $writeId) }}</p>
  <h1 class="display-title is-hero">
    {{ \App\field('write_h1', __('WordPress development notes.', 'sage'), $writeId) }}
  </h1>
  <p class="lead">
    {{ \App\field('write_lede', __('Practical WordPress, PHP, and front-end notes from real projects. Most posts include code you can adapt.', 'sage'), $writeId) }}
  </p>
  <div class="page-header-split__actions">
    <a class="btn" href="#journal-posts">
      {{ __('Browse posts', 'sage') }} <span aria-hidden="true">↓</span>
    </a>
    <a class="h-text-arrow" href="{{ esc_url($rssUrl) }}">
      {{ __('Subscribe by RSS', 'sage') }} <span aria-hidden="true">→</span>
    </a>
  </div>
@endcomponent

@include('partials.page-nav', [
  'pills' => [
    ['journal-posts', __('Posts', 'sage')],
    ['topics', __('Topics', 'sage')],
  ],
])

{{-- POSTS --}}
<div id="journal-posts" class="container wide page-block write-hub write-hub--home" aria-labelledby="journal-posts-heading">
  <h2 id="journal-posts-heading" class="display-title is-section">{{ __('Browse the posts', 'sage') }}</h2>
  <p class="lead work-guide__intro">{{ __('Search by keyword, pick a topic, or switch between grid and list. Most posts include code you can adapt.', 'sage') }}</p>

  @include('partials.write-toolbar', ['writeId' => $writeId, 'writeUrl' => $writeUrl])
  @include('partials.write-topics', compact('writeId', 'writeUrl'))

  @if (! have_posts())
    <p>No posts yet.</p>
  @else
    <div class="post-list post-list--roomy" data-post-list>
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
  @endif

  @include('partials.write-subscribe', compact('writeId'))

  {{-- Elsewhere --}}
  <div class="journal-elsewhere">
    <p class="write-follow">{{ \App\field('write_follow', __('More of my writing', 'sage'), $writeId) }}</p>
    @include('partials.social', ['labeled' => true])
  </div>
</div>

{{-- ── What I write about — topic coverage grid ───────────────────── --}}
<section class="pf-section pf-section--alt journal-topics-section" id="topics" aria-labelledby="journal-topics-heading">
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

@include('partials.cta-band', [
  'kicker' => __('Get in touch', 'sage'),
  'title' => __('Questions about a post?', 'sage'),
  'text' => __('A note about a snippet, a WordPress question, or a role is welcome. I usually reply within one business day (ET).', 'sage'),
  'label' => __('Say hello', 'sage'),
])
@endsection
