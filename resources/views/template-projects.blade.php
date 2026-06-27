{{--
  Template Name: Projects
--}}

@extends('layouts.app')

@section('content')

@php
  $catSlug     = isset($_GET['cat']) ? sanitize_key($_GET['cat']) : '';
  $pageUrl     = get_permalink();
  $showGithub  = ($catSlug === '' || $catSlug === 'github-projects');
  $showManual  = ($catSlug !== 'github-projects');
  $ghUser      = \App\Github::fetchUser('matthummel-pa');
  $langColors  = [
    'PHP'        => '#4F5D95', 'JavaScript' => '#f1e05a', 'TypeScript' => '#3178c6',
    'CSS'        => '#563d7c', 'HTML'       => '#e34c26', 'Python'     => '#3572A5',
    'Go'         => '#00ADD8', 'Rust'       => '#dea584', 'Shell'      => '#89e051',
    'Java'       => '#b07219', 'Ruby'       => '#701516', 'Blade'      => '#f7523f',
  ];
  $manualCats  = get_terms(['taxonomy' => 'project_categories', 'hide_empty' => true]);
  $manualCats  = ($manualCats && !is_wp_error($manualCats)) ? $manualCats : [];
@endphp

{{-- ── HERO ────────────────────────────────────────────────────────────── --}}
<header class="project-page-hero">
  <div class="container">
    <span class="eyebrow">Work</span>
    <h1>Projects.</h1>
    <p class="lead">
      Open-source tools, client builds, and Power Platform solutions —<br class="hide-mobile">
      things I've designed, shipped, and maintained.
    </p>

    @if (!empty($ghUser))
    <div class="gh-live-strip">
      <span class="gh-live-stat">
        <strong>{{ $ghUser['public_repos'] }}</strong> public repos
      </span>
      <a href="https://github.com/matthummel-pa" class="gh-live-link" target="_blank" rel="noopener">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
        matthummel-pa
      </a>
    </div>
    @endif
  </div>
</header>

{{-- ── CATEGORY FILTER ─────────────────────────────────────────────────── --}}
<nav class="projects-filter container" aria-label="{{ __('Filter projects', 'matthummel') }}">
  <a class="filter-pill{{ $catSlug === '' ? ' is-active' : '' }}" href="{{ $pageUrl }}">All</a>
  <a class="filter-pill{{ $catSlug === 'github-projects' ? ' is-active' : '' }}"
     href="{{ add_query_arg('cat', 'github-projects', $pageUrl) }}">
    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" style="vertical-align:text-bottom;margin-right:4px" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
    GitHub
  </a>
  @foreach ($manualCats as $mhCat)
    @if ($mhCat->slug !== 'github-projects')
      <a class="filter-pill{{ $catSlug === $mhCat->slug ? ' is-active' : '' }}"
         href="{{ add_query_arg('cat', $mhCat->slug, $pageUrl) }}">{{ $mhCat->name }}</a>
    @endif
  @endforeach
</nav>

{{-- ── LIVE GITHUB REPOS ───────────────────────────────────────────────── --}}
@if ($showGithub)
@php
  $ghRepos = \App\Github::fetchRepos('matthummel-pa', 12, 'updated');
@endphp
@if (!empty($ghRepos))
<section class="projects-github">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
        Live from GitHub
      </h2>
      <a href="https://github.com/matthummel-pa?tab=repositories" class="section-view-all"
         target="_blank" rel="noopener">View all repos →</a>
    </div>

    <div class="github-repos-grid">
      @foreach ($ghRepos as $r)
      @php
        $rLangColor = isset($langColors[$r['lang']]) ? $langColors[$r['lang']] : '#8b949e';
      @endphp
      <a href="{{ $r['url'] }}" class="github-repo-card" target="_blank" rel="noopener" data-anim="fade-up">
        <div class="github-repo-card-header">
          <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M2 2.5A2.5 2.5 0 014.5 0h8.75a.75.75 0 01.75.75v12.5a.75.75 0 01-.75.75h-2.5a.75.75 0 010-1.5h1.75v-2h-8a1 1 0 00-.714 1.7.75.75 0 01-1.072 1.05A2.495 2.495 0 012 11.5v-9zm10.5-1V9h-8c-.356 0-.694.074-1 .208V2.5a1 1 0 011-1h8z" fill="currentColor"/></svg>
          <span class="github-repo-name">{{ $r['name'] }}</span>
        </div>
        @if (!empty($r['desc']))
          <p class="github-repo-desc">{{ wp_trim_words($r['desc'], 16) }}</p>
        @endif
        <div class="github-repo-meta">
          @if (!empty($r['lang']))
            <span class="repo-lang">
              <span class="repo-lang-dot" style="background:{{ $rLangColor }}"></span>
              {{ $r['lang'] }}
            </span>
          @endif
          @if ($r['stars'])
            <span class="repo-stars">
              <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"/></svg>
              {{ number_format($r['stars']) }}
            </span>
          @endif
          @if ($r['forks'])
            <span class="repo-forks">
              <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5 3.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm0 2.122a2.25 2.25 0 10-1.5 0v.878A2.25 2.25 0 005.75 8.5h1.5v2.128a2.251 2.251 0 101.5 0V8.5h1.5a2.25 2.25 0 002.25-2.25v-.878a2.25 2.25 0 10-1.5 0v.878a.75.75 0 01-.75.75h-4.5A.75.75 0 015 6.25v-.878zm3.75 7.378a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm3-8.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
              {{ number_format($r['forks']) }}
            </span>
          @endif
        </div>
      </a>
      @endforeach
    </div>
  </div>
</section>
@endif
@endif

{{-- ── MANUAL CPT PROJECTS ─────────────────────────────────────────────── --}}
@if ($showManual)
@php
  $mhTaxQuery = ($catSlug && $catSlug !== 'github-projects')
    ? [['taxonomy' => 'project_categories', 'field' => 'slug', 'terms' => $catSlug]]
    : [];
  $mhProjects = new \WP_Query([
    'post_type'      => 'projects',
    'posts_per_page' => 12,
    'tax_query'      => $mhTaxQuery,
  ]);
@endphp
@if ($mhProjects->have_posts())
<section class="projects-manual">
  <div class="container">
    @php
      $activeCatName = '';
      if ($catSlug && $catSlug !== 'github-projects') {
        foreach ($manualCats as $c) {
          if ($c->slug === $catSlug) { $activeCatName = $c->name; break; }
        }
      }
    @endphp
    <div class="section-header">
      <h2 class="section-title">{{ $activeCatName ?: 'All Projects' }}</h2>
    </div>

    <div class="project-card-grid">
      @while ($mhProjects->have_posts())
      @php $mhProjects->the_post(); @endphp
      @php
        $isFeatured = (bool) get_post_meta(get_the_ID(), '_mh_featured', true);
        $techStack  = get_post_meta(get_the_ID(), '_mh_tech_stack', true);
        $techPills  = $techStack ? array_map('trim', explode(',', $techStack)) : [];
        $eyebrow    = get_post_meta(get_the_ID(), '_mh_eyebrow', true);
        $projTerms  = get_the_terms(get_the_ID(), 'project_categories');
        $projLabel  = ($projTerms && !is_wp_error($projTerms)) ? $projTerms[0]->name : ($eyebrow ?: '');
      @endphp
      <article class="project-card{{ $isFeatured ? ' project-card--featured' : '' }}" data-anim="fade-up">
        <a href="{{ get_permalink() }}" class="project-card-link">
          @if (has_post_thumbnail())
            <div class="project-card-thumb">
              {!! get_the_post_thumbnail(null, 'medium_large', ['loading' => 'lazy', 'decoding' => 'async']) !!}
            </div>
          @else
            <div class="project-card-thumb project-card-thumb--placeholder">
              <div class="project-thumb-inner"></div>
            </div>
          @endif
          <div class="project-card-body">
            @if ($projLabel)
              <p class="project-card-eyebrow">{{ $projLabel }}</p>
            @endif
            <h2 class="project-card-title">{!! get_the_title() !!}</h2>
            @php $mhEx = get_the_excerpt() @endphp
            @if ($mhEx)
              <p class="project-card-excerpt">{{ wp_trim_words($mhEx, 18) }}</p>
            @endif
            @if (!empty($techPills))
              <div class="project-card-tech">
                @foreach (array_slice($techPills, 0, 4) as $pill)
                  <span class="tech-pill">{{ $pill }}</span>
                @endforeach
              </div>
            @endif
            <span class="project-card-cta">View project →</span>
          </div>
        </a>
      </article>
      @endwhile
    </div>
  </div>
</section>
@endif
@php wp_reset_postdata(); @endphp
@endif

{{-- ── CTA ────────────────────────────────────────────────────────────── --}}
<section class="projects-cta-section">
  <div class="container">
    <div class="cta-card" data-anim="fade-up">
      <h2>Have a project in mind?</h2>
      <p>I'm open to select freelance and consulting work — Power Platform apps, WordPress builds, and M365 solutions. Let's talk.</p>
      @php $mhContactPage = get_page_by_path('contact') @endphp
      <a class="btn" href="{{ $mhContactPage ? get_permalink($mhContactPage) : '/contact/' }}">Get in touch →</a>
    </div>
  </div>
</section>

@endsection
