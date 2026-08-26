{{--
  Template Name: Now
--}}
@extends('layouts.app')

@php
  $gh      = \App\Github::fetchUser(\App\mh_github_login());
  $ghUrl   = $gh['url'] ?: 'https://github.com/'.\App\mh_github_login();
  $ghBlog  = \App\mh_github_blog_url($gh);
  $writing = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $updated = 'August 2026';
@endphp

@section('content')

{{-- ── HERO ─────────────────────────────────────────────── --}}
@component('partials.page-hero')
  <p class="eyebrow">Now</p>
  <h1 class="display-title is-hero">What I'm doing right now.</h1>
  <p class="lead">A snapshot of where my time and attention are going — updated {{ $updated }}. Inspired by <a href="https://nownownow.com" rel="noopener" target="_blank">nownownow.com</a>.</p>
  <div class="now-hero-meta">
    @if (\App\mh_is_hireable($gh))
      <span class="now-hero-status">
        @include('partials.avail-mark', ['gh' => $gh])
        {{ \App\mh_availability_label($gh, __('Open for new work', 'sage')) }}
      </span>
    @endif
    <span class="now-hero-location">
      {!! \App\mh_svg_icon('map', 14) !!} Gettysburg, PA · Eastern Time
    </span>
  </div>
  <p class="about-hero-links" style="margin-top:1rem">
    <a href="{{ home_url('/about/') }}">{!! \App\mh_svg_icon('user', 14) !!} Full background</a>
    <a href="{{ home_url('/hire/') }}">{!! \App\mh_svg_icon('briefcase', 14) !!} Hire me</a>
    <a href="{{ home_url('/uses/') }}">{!! \App\mh_svg_icon('code', 14) !!} Stack I use</a>
  </p>
@endcomponent

{{-- ── MAIN CONTENT + SIDEBAR ─────────────────────────── --}}
<section class="pf-section" aria-label="Current focus">
  <div class="container wide now-layout">

    <div class="now-main">

      {{-- Building Ridges & Valleys --}}
      <article class="now-block">
        <div class="now-block__head">
          <div class="now-block__icon">{!! \App\mh_svg_icon('briefcase', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">Studio work</p>
            <h2 class="now-block__title">Building Ridges &amp; Valleys</h2>
          </div>
        </div>
        <p>{{ \App\field('now_studio_p1', __('I recently started Ridges & Valleys, a WordPress studio for shops, tours, and inns in Gettysburg and Adams County, PA. I\'m building concept sites that show what a real WordPress site can look like for a specific type of local business — not wireframes or screenshots, but live, working demonstrations.', 'sage')) }}</p>
        <p>{{ \App\field('now_studio_p2', __('The studio is early. The portfolio is growing. If you run a Gettysburg-area business and want to see what an editable WordPress site looks like for your category, that\'s exactly what I\'m building.', 'sage')) }}</p>
        <a class="h-text-arrow" href="{{ esc_url($ghBlog) }}" rel="noopener" target="_blank">
          Visit ridgesandvalleys.com →
        </a>
      </article>

      {{-- Open for work (GitHub hireable) --}}
      @if (\App\mh_is_hireable($gh))
      <article class="now-block">
        <div class="now-block__head">
          <div class="now-block__icon">{!! \App\mh_svg_icon('users', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">Availability</p>
            <h2 class="now-block__title">{{ \App\mh_availability_label($gh, __('Open for new work', 'sage')) }}</h2>
          </div>
        </div>
        <p>{{ \App\field('now_work_p1', __('Alongside the studio I\'m actively looking for full-time roles, contract work, freelance projects, and agency partnerships. My focus is full-stack web development, especially WordPress, PHP, JavaScript, React, and API integrations.', 'sage')) }}</p>
        <p>{{ \App\field('now_work_p2', __('If you\'re hiring a full-stack developer, need WordPress expertise, or want a dependable development partner for overflow work, a short note is enough to start.', 'sage')) }}</p>
        <div style="display:flex;flex-wrap:wrap;gap:.65rem;align-items:center">
          <a class="btn" href="{{ home_url('/contact/') }}">{!! \App\mh_svg_icon('mail', 15) !!} Say hello</a>
          <a class="h-text-arrow" href="{{ home_url('/hire/') }}">See hire details →</a>
        </div>
      </article>
      @endif

      {{-- Writing --}}
      <article class="now-block">
        <div class="now-block__head">
          <div class="now-block__icon">{!! \App\mh_svg_icon('pen', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">Writing</p>
            <h2 class="now-block__title">Notes from real builds</h2>
          </div>
        </div>
        <p>{{ \App\field('now_write_p1', __('I write short posts on WordPress, PHP, and the tools I actually use on projects. Most posts include code you can paste into a theme or plugin. I write for developers who want something working, not a tutorial that ends at "and so on."', 'sage')) }}</p>
        <p>{{ \App\field('now_write_p2', __('Posts go on the journal first. Some get cross-posted to DEV.to. Nothing is paywalled.', 'sage')) }}</p>
        <a class="h-text-arrow" href="{{ $writing }}">Read the journal →</a>
      </article>

      {{-- AI and tooling --}}
      <article class="now-block">
        <div class="now-block__head">
          <div class="now-block__icon">{!! \App\mh_svg_icon('cursor-ai', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">How I work</p>
            <h2 class="now-block__title">Building with AI, reviewing every line</h2>
          </div>
        </div>
        <p>{{ \App\field('now_ai_p1', __('I use Cursor AI, Claude, and ChatGPT as part of my development workflow. AI makes the first pass faster — I review everything before it ships. The final code is something I can explain and maintain.', 'sage')) }}</p>
        <p>{{ \App\field('now_ai_p2', __('I\'m honest about this because I think it matters: if you hire me, you\'re getting real engineering judgment, not just generated output. This site was planned and built with Cursor AI.', 'sage')) }}</p>
        <a class="h-text-arrow" href="{{ home_url('/uses/') }}">See the full stack →</a>
      </article>

      {{-- Life --}}
      <article class="now-block">
        <div class="now-block__head">
          <div class="now-block__icon">{!! \App\mh_svg_icon('map', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">Life</p>
            <h2 class="now-block__title">Gettysburg, Pennsylvania</h2>
          </div>
        </div>
        <p>{{ \App\field('now_life_p1', __('I live in Gettysburg, Pennsylvania with my family. Nights and weekends belong to people, not projects. I keep work well-scoped, which is why I only take on a handful of extra projects at a time. I work Eastern Time hours.', 'sage')) }}</p>
      </article>

      {{-- The short list --}}
      <article class="now-block now-block--list">
        <div class="now-block__head">
          <div class="now-block__icon">{!! \App\mh_svg_icon('check', 18) !!}</div>
          <div>
            <p class="now-block__eyebrow">The short version</p>
            <h2 class="now-block__title">Right now, in one list</h2>
          </div>
        </div>
        <ul class="now-checklist">
          @foreach (\App\field_lines('now_items', [
            __('Building WordPress concept sites for Gettysburg businesses at Ridges & Valleys', 'sage'),
            __('Actively looking for full-time, contract, and freelance WordPress work', 'sage'),
            __('Writing short posts on WordPress development — code you can paste in', 'sage'),
            __('Using Cursor AI and Claude to build faster, reviewing every line before it ships', 'sage'),
            __('Keeping extra projects small — family time is non-negotiable in Gettysburg', 'sage'),
            __('Working Eastern Time, available for remote and local clients', 'sage'),
          ]) as $item)
            <li>{!! \App\mh_svg_icon('check', 14) !!}<span>{{ $item }}</span></li>
          @endforeach
        </ul>
      </article>

    </div>

    {{-- ── SIDEBAR ─────────────────────────────────────── --}}
    <aside class="now-sidebar" aria-label="Status and details">

      @if (\App\mh_is_hireable($gh))
      <div class="now-sidebar-card now-sidebar-card--status">
        <div class="now-sidebar-status-dot">
          @include('partials.avail-mark', ['gh' => $gh])
          <span class="now-sidebar-card__label">Status</span>
        </div>
        <p class="now-sidebar-card__value">{{ \App\mh_availability_label($gh, __('Open for work', 'sage')) }}</p>
        <ul class="now-sidebar-card__list">
          <li>{!! \App\mh_svg_icon('check', 12) !!} Full-time roles</li>
          <li>{!! \App\mh_svg_icon('check', 12) !!} Contract / freelance</li>
          <li>{!! \App\mh_svg_icon('check', 12) !!} Agency overflow</li>
          <li>{!! \App\mh_svg_icon('check', 12) !!} Remote anywhere</li>
        </ul>
        <a class="btn" href="{{ home_url('/contact/') }}" style="width:100%;justify-content:center;margin-top:.75rem">
          {!! \App\mh_svg_icon('mail', 14) !!} Say hello
        </a>
      </div>
      @endif

      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">Last updated</p>
        <p class="now-sidebar-card__value">{{ $updated }}</p>
      </div>

      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">Location</p>
        <p class="now-sidebar-card__value">Gettysburg, PA</p>
        <p class="now-sidebar-card__sub">Eastern Time (ET) · Remote friendly</p>
      </div>

      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">Primary stack</p>
        <p class="now-sidebar-card__value">WordPress + PHP</p>
        <p class="now-sidebar-card__sub">PHP, JavaScript, React, APIs</p>
        <a class="now-sidebar-card__link" href="{{ home_url('/uses/') }}">Full stack →</a>
      </div>

      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">Studio</p>
        <p class="now-sidebar-card__value">Ridges &amp; Valleys</p>
        <p class="now-sidebar-card__sub">WordPress studio for Gettysburg businesses</p>
        <a class="now-sidebar-card__link" href="{{ esc_url($ghBlog) }}" rel="noopener" target="_blank">ridgesandvalleys.com →</a>
      </div>

      @if (! empty($gh['public_repos']))
      <div class="now-sidebar-card">
        <p class="now-sidebar-card__label">GitHub</p>
        <p class="now-sidebar-card__value">{{ $gh['public_repos'] }} public repos</p>
        @if (! empty($gh['followers']))
          <p class="now-sidebar-card__sub">{{ $gh['followers'] }} followers</p>
        @endif
        <a class="now-sidebar-card__link" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">View profile →</a>
      </div>
      @endif

    </aside>

  </div>
</section>

{{-- ── CTA ─────────────────────────────────────────────── --}}
<section class="cta-band" aria-labelledby="now-cta-heading" data-reveal>
  <div class="container wide cta-band-inner">
    <div class="cta-band__copy">
      <p class="eyebrow eyebrow--on-dark">{{ __('Let’s work together', 'sage') }}</p>
      <h2 id="now-cta-heading" class="display-title is-section">{{ __('Something I can help with?', 'sage') }}</h2>
      <p>{{ __('A short note is enough to start. I usually reply within a day.', 'sage') }}</p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn-on-dark" href="{{ home_url('/contact/') }}">
        {!! \App\mh_svg_icon('mail', 16) !!} {{ __('Say hello', 'sage') }}
      </a>
      <a class="btn btn-ghost" href="{{ home_url('/hire/') }}">{{ __('Hire me', 'sage') }}</a>
      <p class="cta-band__note">{{ __('Gettysburg · remote · usually within a day', 'sage') }}</p>
    </div>
  </div>
</section>

@endsection
