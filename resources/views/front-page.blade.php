@extends('layouts.app')

@section('content')
  @php
    $heroCols = max(1, min(3, (int) get_theme_mod('mh_hero_cols', 1)));
    $heroImg  = get_theme_mod('mh_hero_img', '');
    $heroImg2 = get_theme_mod('mh_hero_img2', '');
    $heroBg   = get_theme_mod('mh_hero_bg', '');
    $heroAnim = get_theme_mod('mh_hero_anim', 'zoom-in');
    $heroAnimAttr = $heroAnim !== 'none' ? ' data-anim="' . esc_attr($heroAnim) . '"' : '';
    $heroEyebrow = get_theme_mod('mh_hero_eyebrow', __('Web · WordPress · Power Platform', 'matthummel'));
    $heroTitle   = get_theme_mod('mh_hero_title', __('Clean, fast software for the web and Microsoft 365.', 'matthummel'));
    $heroSub     = get_theme_mod('mh_hero_subtext', __("I'm Matt Hummel, a full-stack developer. I write about web development, WordPress, and the Power Platform, and share the tools I build on GitHub.", 'matthummel'));
  @endphp
  <section class="home-hero container mh-hero">
    @if ($heroBg)
      <span class="mh-hero-overlay" aria-hidden="true"></span>
    @endif
    <div class="mh-hero-inner">
      <div class="mh-hero-content"{!! $heroAnimAttr !!}>
        @if ($heroEyebrow)<p class="eyebrow">{{ $heroEyebrow }}</p>@endif
        @if ($heroTitle)<h1 class="display-title is-hero">{{ $heroTitle }}</h1>@endif
        @if ($heroSub)<p class="lead">{!! nl2br(e($heroSub)) !!}</p>@endif
        <div class="btn-row">
          @php($projectsLink = get_post_type_archive_link('projects'))
          @if ($projectsLink)
            <a class="btn" href="{{ esc_url($projectsLink) }}">{{ __('View projects', 'matthummel') }}</a>
          @endif
          @php($blogLink = get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : home_url('/blog/'))
          <a class="btn btn-outline" href="{{ esc_url($blogLink) }}">{{ __('Read the blog', 'matthummel') }}</a>
        </div>
      </div>
      @if ($heroCols >= 2 && $heroImg)
        <div class="mh-hero-media"{!! $heroAnimAttr !!}>
          <img src="{{ esc_url($heroImg) }}" alt="" loading="eager" decoding="async">
        </div>
      @endif
      @if ($heroCols >= 3 && $heroImg2)
        <div class="mh-hero-media"{!! $heroAnimAttr !!}>
          <img src="{{ esc_url($heroImg2) }}" alt="" loading="eager" decoding="async">
        </div>
      @endif
    </div>
  </section>

  <hr class="rule">

  <section class="home-section container">
    <div class="section-head">
      <h2 class="display-title is-section">{{ __('Latest writing', 'matthummel') }}</h2>
    </div>
    @php($recent = new \WP_Query(['post_type' => 'post', 'posts_per_page' => 3, 'ignore_sticky_posts' => true]))
    @if ($recent->have_posts())
      <div class="card-grid">
        @while ($recent->have_posts()) @php($recent->the_post())
          <a class="mini-card" href="{{ get_permalink() }}">
            <p class="post-meta">{{ get_the_date() }}</p>
            <h3 class="mini-card-title">{!! get_the_title() !!}</h3>
            <p class="mini-card-excerpt">{{ wp_trim_words(get_the_excerpt(), 18) }}</p>
          </a>
        @endwhile
      </div>
      <p class="section-link"><a href="{{ esc_url($blogLink) }}">{{ __('All posts →', 'matthummel') }}</a></p>
    @endif
    @php(wp_reset_postdata())
  </section>

  @php($projects = new \WP_Query(['post_type' => 'projects', 'posts_per_page' => 3]))
  @if ($projects->have_posts())
    <hr class="rule">
    <section class="home-section container">
      <div class="section-head">
        <h2 class="display-title is-section">{{ __('Projects', 'matthummel') }}</h2>
      </div>
      <div class="project-grid">
        @while ($projects->have_posts()) @php($projects->the_post())
          <a class="project-card" href="{{ get_permalink() }}">
            @if (has_post_thumbnail())
              {!! get_the_post_thumbnail(null, 'medium_large', ['loading' => 'lazy']) !!}
            @endif
            <h2>{!! get_the_title() !!}</h2>
            <p>{{ wp_trim_words(get_the_excerpt(), 16) }}</p>
          </a>
        @endwhile
      </div>
      @if ($projectsLink ?? get_post_type_archive_link('projects'))
        <p class="section-link"><a href="{{ esc_url(get_post_type_archive_link('projects')) }}">{{ __('All projects →', 'matthummel') }}</a></p>
      @endif
    </section>
  @endif
  @php(wp_reset_postdata())

  @include('partials.cta')
@endsection
