@extends('layouts.app')

@section('content')
@while(have_posts())
@php the_post(); @endphp
@php
  $pid       = get_the_ID();
  $owner     = get_post_meta($pid, '_mh_gh_owner', true) ?: apply_filters('matthummel/github_owner', 'matthummel-pa');
  $repo      = get_post_meta($pid, '_mh_gh_repo', true) ?: get_post_field('post_name');
  $eyebrow   = get_post_meta($pid, '_mh_eyebrow', true);
  $demoUrl   = get_post_meta($pid, '_mh_demo_url', true);
  $techStack = get_post_meta($pid, '_mh_tech_stack', true);
  $techPills = $techStack ? array_map('trim', explode(',', $techStack)) : [];
  $hasGhRepo = (bool) get_post_meta($pid, '_mh_gh_repo', true);
  $ghLink    = "https://github.com/{$owner}/{$repo}";
  $ghData    = $hasGhRepo ? \App\Github::fetch($owner, $repo) : [];

  $projTerms    = get_the_terms($pid, 'project_categories');
  $catName      = ($projTerms && !is_wp_error($projTerms)) ? $projTerms[0]->name : '';
  $eyebrowLabel = $eyebrow ?: $catName ?: 'Project';
  $projDesc     = !empty($ghData['desc']) ? $ghData['desc'] : get_the_excerpt();

  $langColors = [
    'PHP' => '#4F5D95', 'JavaScript' => '#f1e05a', 'TypeScript' => '#3178c6',
    'CSS' => '#563d7c', 'HTML' => '#e34c26', 'Python' => '#3572A5',
    'Go' => '#00ADD8', 'Rust' => '#dea584', 'Shell' => '#89e051',
    'Java' => '#b07219', 'Ruby' => '#701516', 'Blade' => '#f7523f',
  ];
  $langColor = isset($ghData['lang']) ? (isset($langColors[$ghData['lang']]) ? $langColors[$ghData['lang']] : '#8b949e') : '#8b949e';
@endphp

{{-- ── HERO ─────────────────────────────────────────────────────────────── --}}
<header class="project-single-hero">
  <div class="container">

    <p class="project-single-eyebrow">
      @if ($hasGhRepo)
        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" style="vertical-align:text-bottom;margin-right:5px"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
      @endif
      {{ $eyebrowLabel }}
    </p>

    <h1 class="project-single-title">{{ get_the_title() }}</h1>

    @if ($projDesc)
      <p class="project-single-desc">{{ $projDesc }}</p>
    @endif

    @if (!empty($techPills))
      <div class="project-tech-pills">
        @foreach ($techPills as $pill)
          <span class="project-tech-pill">{{ $pill }}</span>
        @endforeach
      </div>
    @endif

    <div class="project-hero-actions">
      @if ($demoUrl)
        <a href="{{ esc_url($demoUrl) }}" class="project-hero-btn project-hero-btn--primary" target="_blank" rel="noopener">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Live site
        </a>
      @endif
      @if ($hasGhRepo)
        <a href="{{ esc_url($ghLink) }}" class="project-hero-btn project-hero-btn--outline" target="_blank" rel="noopener">
          <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
          GitHub
        </a>
        <a href="{{ esc_url($ghLink . '#readme') }}" class="project-hero-btn project-hero-btn--ghost" target="_blank" rel="noopener">
          README
        </a>
      @endif
    </div>

  </div>
</header>

{{-- ── GITHUB STATS BAND ─────────────────────────────────────────────────── --}}
@if ($hasGhRepo && !empty($ghData))
<div class="project-gh-stats">
  <div class="container">
    <div class="project-gh-stats-inner">
      @if (isset($ghData['stars']))
        <span class="gh-stat-item">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"/></svg>
          <strong>{{ number_format($ghData['stars']) }}</strong> Stars
        </span>
      @endif
      @if (isset($ghData['forks']))
        <span class="gh-stat-item">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5 3.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm0 2.122a2.25 2.25 0 10-1.5 0v.878A2.25 2.25 0 005.75 8.5h1.5v2.128a2.251 2.251 0 101.5 0V8.5h1.5a2.25 2.25 0 002.25-2.25v-.878a2.25 2.25 0 10-1.5 0v.878a.75.75 0 01-.75.75h-4.5A.75.75 0 015 6.25v-.878zm3.75 7.378a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm3-8.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
          <strong>{{ number_format($ghData['forks']) }}</strong> Forks
        </span>
      @endif
      @if (!empty($ghData['lang']))
        <span class="gh-stat-item">
          <span style="width:12px;height:12px;border-radius:50%;display:inline-block;background:{{ $langColor }}"></span>
          <strong>{{ $ghData['lang'] }}</strong>
        </span>
      @endif
      @if (!empty($ghData['license']))
        <span class="gh-stat-item">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8.75.75a.75.75 0 00-1.5 0V2h-.984c-.305 0-.604.08-.869.23l-1.288.737A.25.25 0 013.984 3H1.75a.75.75 0 000 1.5h.428L.066 9.192a.75.75 0 00.154.838l.53-.53-.53.53v.001l.002.002.002.003.006.006.016.015.045.04a3.514 3.514 0 00.686.45A4.492 4.492 0 003 11c.88 0 1.556-.22 2.023-.454a3.515 3.515 0 00.686-.45l.045-.04.016-.015.006-.006.002-.003.001-.002-.529-.531.53.53a.75.75 0 00.154-.838L3.822 4.5h.162c.305 0 .604-.08.869-.23l1.289-.737a.25.25 0 01.124-.033h.984V13h-2.5a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-2.5V3.5h.984a.25.25 0 01.124.033l1.29.736c.264.152.563.231.868.231h.162l-2.112 4.692a.75.75 0 00.154.838l.53-.53-.53.53v.001l.002.002.002.003.006.006.016.015.045.04a3.517 3.517 0 00.686.45A4.492 4.492 0 0013 11c.88 0 1.556-.22 2.023-.454a3.512 3.512 0 00.686-.45l.045-.04.01-.01.006-.005.006-.006.002-.003.001-.002-.529-.531.53.53a.75.75 0 00.154-.838L13.822 4.5H14.25a.75.75 0 000-1.5h-2.234a.25.25 0 01-.124-.033l-1.29-.736A1.75 1.75 0 009.735 2H8.75V.75z"/></svg>
          <strong>{{ $ghData['license'] }}</strong>
        </span>
      @endif
      @if (!empty($ghData['release']))
        <span class="gh-stat-item">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2.5 7.775V2.75a.25.25 0 01.25-.25h5.025a.25.25 0 01.177.073l6.25 6.25a.25.25 0 010 .354l-5.025 5.025a.25.25 0 01-.354 0l-6.25-6.25a.25.25 0 01-.073-.177zm-1.5 0V2.75C1 1.784 1.784 1 2.75 1h5.025c.464 0 .91.184 1.238.513l6.25 6.25a1.75 1.75 0 010 2.474l-5.026 5.026a1.75 1.75 0 01-2.474 0l-6.25-6.25A1.75 1.75 0 011 7.775zM6 5a1 1 0 100 2 1 1 0 000-2z"/></svg>
          <strong>{{ $ghData['release'] }}</strong>
        </span>
      @endif
      <a href="{{ esc_url($ghLink) }}" class="gh-stat-repo-link" target="_blank" rel="noopener">
        {{ $owner }}/{{ $repo }} →
      </a>
    </div>
  </div>
</div>
@endif

{{-- ── FEATURED IMAGE ────────────────────────────────────────────────────── --}}
@if (has_post_thumbnail())
<div class="project-featured-img">
  <div class="container">
    {!! get_the_post_thumbnail($pid, 'large', ['class' => 'project-featured-img-el', 'loading' => 'eager', 'decoding' => 'async']) !!}
  </div>
</div>
@endif

{{-- ── PROJECT BODY ──────────────────────────────────────────────────────── --}}
<div class="project-body">
  <div class="container">
    <div class="project-body-inner">

      @php $mhBody = trim(get_the_content()) @endphp
      @if ($mhBody)
        <div class="project-content entry-content">
          @php the_content(); @endphp
        </div>
      @endif

      @if ($hasGhRepo && !empty($ghData['intro']))
        <div class="project-content project-readme">
          <h2>About this project</h2>
          {!! \App\Github::render($owner, $repo, ['intro']) !!}
        </div>
      @endif

      @php $mhImages = get_attached_media('image', $pid) @endphp
      @if ($mhImages)
        <div class="project-shot-grid">
          @foreach ($mhImages as $mhImg)
            {!! wp_get_attachment_image($mhImg->ID, 'large', false, ['loading' => 'lazy', 'decoding' => 'async']) !!}
          @endforeach
        </div>
      @endif

    </div>
  </div>
</div>

{{-- ── RELATED PROJECTS ──────────────────────────────────────────────────── --}}
@php
  $relArgs = [
    'post_type'      => 'projects',
    'posts_per_page' => 3,
    'post__not_in'   => [$pid],
  ];
  if ($projTerms && !is_wp_error($projTerms)) {
    $relArgs['tax_query'] = [['taxonomy' => 'project_categories', 'field' => 'term_id', 'terms' => wp_list_pluck($projTerms, 'term_id')]];
  }
  $mhRelated = new \WP_Query($relArgs);
@endphp
@if ($mhRelated->have_posts())
<section class="project-related">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">More projects</h2>
      @php $projArchive = get_page_by_path('projects') @endphp
      <a class="section-view-all" href="{{ $projArchive ? get_permalink($projArchive) : '/projects/' }}">All projects →</a>
    </div>
    <div class="project-related-grid">
      @while ($mhRelated->have_posts())
      @php $mhRelated->the_post(); @endphp
      @php
        $rTech  = get_post_meta(get_the_ID(), '_mh_tech_stack', true);
        $rPills = $rTech ? array_slice(array_map('trim', explode(',', $rTech)), 0, 3) : [];
        $rTerms = get_the_terms(get_the_ID(), 'project_categories');
        $rLabel = ($rTerms && !is_wp_error($rTerms)) ? $rTerms[0]->name : '';
      @endphp
      <article class="project-card" data-anim="fade-up">
        <a href="{{ get_permalink() }}" class="project-card-link">
          @if (has_post_thumbnail())
            <div class="project-card-thumb">
              {!! get_the_post_thumbnail(null, 'medium', ['loading' => 'lazy', 'decoding' => 'async']) !!}
            </div>
          @else
            <div class="project-card-thumb project-card-thumb--placeholder">
              <div class="project-thumb-inner"></div>
            </div>
          @endif
          <div class="project-card-body">
            @if ($rLabel)
              <p class="project-card-eyebrow">{{ $rLabel }}</p>
            @endif
            <h3 class="project-card-title">{!! get_the_title() !!}</h3>
            @if (!empty($rPills))
              <div class="project-card-tech">
                @foreach ($rPills as $rp)
                  <span class="tech-pill">{{ $rp }}</span>
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
@php wp_reset_postdata(); @endphp
@endif

@endwhile
@endsection
